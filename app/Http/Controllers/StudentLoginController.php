<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentLoginController extends Controller
{
    /**
     * Password validation rule.
     *
     * Requirements:
     * - Minimum 6 characters
     * - At least 1 uppercase letter
     * - At least 1 number
     * - At least 1 of: ! _ - .
     */
    private function strongPasswordRule(): string
    {
        return 'required|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.-]).{6,}$/';
    }

    /**
     * Show student login page.
     */
    public function showLoginForm()
    {
        return view('welcome');
    }

    /**
     * Mobile / API student login.
     */
    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALIZE EMAIL
        |--------------------------------------------------------------------------
        */
        $email = strtolower(trim((string) $request->input('email', '')));

        $request->merge([
            'email' => $email,
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDATE REQUEST
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | FIND STUDENT
        |--------------------------------------------------------------------------
        */
        $student = Student::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email is not registered. Please use your university email.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK UNIVERSITY EMAIL
        |--------------------------------------------------------------------------
        */
        if (!str_ends_with(strtolower($student->email), '@ssct.edu.ph')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only SSCT emails are allowed.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY PASSWORD
        |--------------------------------------------------------------------------
        */
        if (!Hash::check($request->password, $student->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid password.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE OLD MOBILE TOKENS - OPTIONAL
        |--------------------------------------------------------------------------
        |
        | This prevents many unused tokens from accumulating every time
        | the student logs in.
        |
        */
        $student->tokens()
            ->where('name', 'student-token')
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | CREATE SANCTUM TOKEN
        |--------------------------------------------------------------------------
        */
        $token = $student
            ->createToken('student-token')
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | LOGIN RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful!',
            'token' => $token,
            'student_id' => $student->student_id
                ?? $student->student_id_number
                ?? $student->id,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id
                    ?? $student->student_id_number
                    ?? null,
                'name' => $student->name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'course' => $student->course,
                'year_level' => $student->year_level,
                'department' => $student->department,
                'profile_photo' => $student->profile_photo,
                'profile_picture' => $student->profile_photo,
            ],
        ], 200);
    }

    /**
     * Google student login.
     *
     * Security flow:
     * - React Native sends the Google ID token.
     * - Laravel verifies the signed token with Google.
     * - Verified @gmail.com and @ssct.edu.ph Google accounts are accepted.
     * - The Google email address must already exist in the students table.
     */
    public function googleLogin(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATE REQUEST
        |--------------------------------------------------------------------------
        */
        $validated = $request->validate([
            'id_token' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | GOOGLE WEB CLIENT ID
        |--------------------------------------------------------------------------
        */
        $googleClientId = trim((string) config('services.google.client_id', ''));

        if ($googleClientId === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Google login is not configured on the server. GOOGLE_CLIENT_ID is missing.',
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY GOOGLE ID TOKEN
        |--------------------------------------------------------------------------
        */
        try {
            $googleClient = new GoogleClient([
                'client_id' => $googleClientId,
            ]);

            $payload = $googleClient->verifyIdToken($validated['id_token']);
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to verify the Google account. Please try again.',
            ], 401);
        }

        if (!$payload || !is_array($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired Google sign-in token.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFIED GOOGLE IDENTITY
        |--------------------------------------------------------------------------
        */
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $name = trim((string) ($payload['name'] ?? ''));
        $emailVerified = filter_var(
            $payload['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if ($email === '' || !$emailVerified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Google did not return a verified email address.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | ALLOWED GOOGLE EMAIL DOMAINS
        |--------------------------------------------------------------------------
        */
        $isPersonalGmail = str_ends_with($email, '@gmail.com');
        $isSsctGoogleAccount = str_ends_with($email, '@ssct.edu.ph');

        if (!$isPersonalGmail && !$isSsctGoogleAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only personal Gmail accounts (@gmail.com) and SSCT school Google accounts (@ssct.edu.ph) are allowed for Google login.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | FIND EXISTING STUDENT
        |--------------------------------------------------------------------------
        */
        $student = Student::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'No FindIt student account is registered with this Google email address. Please ask the administrator to register or link this email first.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE NAME IF GOOGLE PROVIDED ONE
        |--------------------------------------------------------------------------
        */
        if ($name !== '') {
            $nameParts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);

            if (!empty($nameParts)) {
                $firstName = $nameParts[0];
                $lastName = count($nameParts) > 1
                    ? implode(' ', array_slice($nameParts, 1))
                    : $student->last_name;

                $student->update([
                    'first_name' => trim((string) $firstName),
                    'last_name' => trim((string) $lastName),
                ]);

                $student->refresh();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | REMOVE OLD GOOGLE TOKENS
        |--------------------------------------------------------------------------
        */
        $student->tokens()
            ->where('name', 'student-google-token')
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | CREATE SANCTUM TOKEN
        |--------------------------------------------------------------------------
        */
        $token = $student
            ->createToken('student-google-token')
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => 'success',
            'message' => 'Google login successful!',
            'token' => $token,
            'student_id' => $student->student_id
                ?? $student->student_id_number
                ?? $student->id,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id
                    ?? $student->student_id_number
                    ?? null,
                'name' => $student->name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'course' => $student->course,
                'year_level' => $student->year_level,
                'department' => $student->department,
                'profile_photo' => $student->profile_photo,
                'profile_picture' => $student->profile_photo,
                'login_method' => 'google',
            ],
        ], 200);
    }

    /**
     * Logout current mobile student.
     */
    public function logout(Request $request)
    {
        $student = $request->user();

        if ($student && $student->currentAccessToken()) {
            $student->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Student registration.
     */
    public function register(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALIZE EMAIL
        |--------------------------------------------------------------------------
        */
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */
        $validatedData = $request->validate(
            [
                'student_id' => [
                    'required',
                    'string',
                    'unique:students,student_id_number',
                ],
                'first_name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'middle_name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'course' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'email' => [
                    'required',
                    'email',
                    'unique:students,email',
                ],
                'year_level' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'department' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'password' => [
                    'required',
                    'min:6',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.-]).{6,}$/',
                ],
            ],
            [
                'password.min' => 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .',
                'password.regex' => 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | NORMALIZE EMAIL
        |--------------------------------------------------------------------------
        */
        $validatedData['email'] = strtolower(trim((string) $validatedData['email']));

        /*
        |--------------------------------------------------------------------------
        | CHECK UNIVERSITY EMAIL
        |--------------------------------------------------------------------------
        */
        if (!str_ends_with($validatedData['email'], '@ssct.edu.ph')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only SSCT emails are allowed.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE STUDENT
        |--------------------------------------------------------------------------
        */
        $student = Student::create([
            'student_id_number' => $validatedData['student_id'],
            'first_name' => trim($validatedData['first_name']),
            'middle_name' => isset($validatedData['middle_name'])
                && trim($validatedData['middle_name']) !== ''
                    ? trim($validatedData['middle_name'])
                    : null,
            'last_name' => trim($validatedData['last_name']),
            'course' => $validatedData['course'],
            'email' => $validatedData['email'],
            'year_level' => $validatedData['year_level'],
            'department' => $validatedData['department'],
            'password' => Hash::make($validatedData['password']),
            'profile_photo' => '/assets/logo.png',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CREATE API TOKEN
        |--------------------------------------------------------------------------
        |
        | This is better for your React Native app than using a PHP session.
        |
        */
        $token = $student
            ->createToken('student-token')
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful!',
            'token' => $token,
            'student_id' => $student->student_id
                ?? $student->student_id_number
                ?? $student->id,
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id
                    ?? $student->student_id_number
                    ?? null,
                'name' => $student->name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'course' => $student->course,
                'year_level' => $student->year_level,
                'department' => $student->department,
                'profile_photo' => $student->profile_photo,
                'profile_picture' => $student->profile_photo,
            ],
        ], 201);
    }
}
