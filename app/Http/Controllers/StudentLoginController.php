<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;

class StudentLoginController extends Controller
{
    private function strongPasswordRule(): string
    {
        return 'required|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{6,}$/';
    }

    /**
     * Show login page (Vue entry)
     */
    public function showLoginForm()
    {
        return view('welcome');
    }

    /**
     * Handle student login
     */
    public function login(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $request->merge(['email' => $email]);

        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if student exists using a case-insensitive email comparison.
        $student = Student::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email is not registered. Please use your university email.'
            ], 401);
        }

        // Check university email domain
        if (!str_ends_with(strtolower($student->email), '@ssct.edu.ph')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only SSCT emails are allowed.'
            ], 401);
        }

        // Verify password
        if (!Hash::check($request->password, $student->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid password.'
            ], 401);
        }

        // Create a Sanctum API token for the student
        $token = $student->createToken('student-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful!',
            'token' => $token,
            'student_id' => $student->student_id,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'course' => $student->course,
                'year_level' => $student->year_level,
                'department' => $student->department,
                'profile_photo' => $student->profile_photo,
                'profile_picture' => $student->profile_photo,
            ]
        ], 200);
    }

    /**
     * Handle student logout for API token auth.
     * This is used by the mobile app when the user taps Logout.
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
     * Handle student registration. (not in use laong si bryll gikan here to there)
     */
    public function register(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        // Validate input
        $validatedData = $request->validate([
            'student_id' => 'required|string|unique:students,student_id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email',
            'year_level' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'password' => ['required', 'min:6', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{6,}$/'],
        ], [
            'password.min' => 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .',
            'password.regex' => 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .',
        ]);

        $validatedData['email'] = strtolower(trim((string) $validatedData['email']));

        // Ensure SSCT email
        if (!str_ends_with($validatedData['email'], '@ssct.edu.ph')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only SSCT emails are allowed.'
            ], 422);
        }

        $student = Student::create([
            'student_id_number' => $validatedData['student_id'],
            'first_name' => trim($validatedData['first_name']),
            'middle_name' => trim((string) ($validatedData['middle_name'] ?? '')) !== '' ? trim($validatedData['middle_name']) : null,
            'last_name' => trim($validatedData['last_name']),
            'course' => $validatedData['course'],
            'email' => $validatedData['email'],
            'year_level' => $validatedData['year_level'],
            'department' => $validatedData['department'],
            'password' => Hash::make($validatedData['password']),
            'profile_photo' => '/assets/logo.png',
        ]);

        // Auto login after register
        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful and logged in!',
            'student_id' => $student->student_id,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'course' => $student->course,
                'year_level' => $student->year_level,
                'department' => $student->department,
                'profile_photo' => $student->profile_photo,
                'profile_picture' => $student->profile_photo,
            ]
        ], 201);
    }
}
