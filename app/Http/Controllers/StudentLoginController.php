<?php

namespace App\Http\Controllers;

use App\Models\Student;
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

        $email = strtolower(
            trim(
                (string) $request->input('email', '')
            )
        );

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

        $student = Student::whereRaw(
            'LOWER(email) = ?',
            [$email]
        )->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'This email is not registered. Please use your university email.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK UNIVERSITY EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            !str_ends_with(
                strtolower($student->email),
                '@ssct.edu.ph'
            )
        ) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'Only SSCT emails are allowed.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFY PASSWORD
        |--------------------------------------------------------------------------
        */

        if (
            !Hash::check(
                $request->password,
                $student->password
            )
        ) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'Invalid password.',
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

            'message' =>
                'Login successful!',

            'token' => $token,

            'student_id' =>
                $student->student_id ??
                $student->student_id_number ??
                $student->id,

            'student' => [
                'id' =>
                    $student->id,

                'student_id' =>
                    $student->student_id ??
                    $student->student_id_number ??
                    null,

                'name' =>
                    $student->name,

                'first_name' =>
                    $student->first_name,

                'middle_name' =>
                    $student->middle_name,

                'last_name' =>
                    $student->last_name,

                'email' =>
                    $student->email,

                'course' =>
                    $student->course,

                'year_level' =>
                    $student->year_level,

                'department' =>
                    $student->department,

                'profile_photo' =>
                    $student->profile_photo,

                'profile_picture' =>
                    $student->profile_photo,
            ],
        ], 200);
    }

    /**
     * Google student login.
     */
    public function googleLogin(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALIZE INPUT
        |--------------------------------------------------------------------------
        */

        $email = strtolower(
            trim(
                (string) $request->input('email', '')
            )
        );

        $name = trim(
            (string) $request->input('name', '')
        );

        $request->merge([
            'email' => $email,
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDATE
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | CHECK SSCT EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            !str_ends_with(
                $email,
                '@ssct.edu.ph'
            )
        ) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'Only SSCT Google accounts are allowed for student login.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | FIND STUDENT
        |--------------------------------------------------------------------------
        */

        $student = Student::whereRaw(
            'LOWER(email) = ?',
            [$email]
        )->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'No student account was found for this Google account. Please contact the administrator to link your school email.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE NAME IF GOOGLE PROVIDED ONE
        |--------------------------------------------------------------------------
        */

        if ($name !== '') {
            $nameParts = preg_split(
                '/\s+/',
                $name
            );

            $firstName =
                $nameParts[0] ??
                $student->first_name;

            $lastName =
                count($nameParts) > 1
                    ? implode(
                        ' ',
                        array_slice(
                            $nameParts,
                            1
                        )
                    )
                    : $student->last_name;

            $student->update([
                'first_name' =>
                    trim($firstName),

                'last_name' =>
                    trim($lastName),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | REMOVE OLD GOOGLE TOKENS
        |--------------------------------------------------------------------------
        */

        $student->tokens()
            ->where(
                'name',
                'student-google-token'
            )
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | CREATE SANCTUM TOKEN
        |--------------------------------------------------------------------------
        */

        $token = $student
            ->createToken(
                'student-google-token'
            )
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'success',

            'message' =>
                'Google login successful!',

            'token' => $token,

            'student_id' =>
                $student->student_id ??
                $student->student_id_number ??
                $student->id,

            'student' => [
                'id' =>
                    $student->id,

                'student_id' =>
                    $student->student_id ??
                    $student->student_id_number ??
                    null,

                'name' =>
                    $student->name,

                'first_name' =>
                    $student->first_name,

                'middle_name' =>
                    $student->middle_name,

                'last_name' =>
                    $student->last_name,

                'email' =>
                    $student->email,

                'course' =>
                    $student->course,

                'year_level' =>
                    $student->year_level,

                'department' =>
                    $student->department,

                'profile_photo' =>
                    $student->profile_photo,

                'profile_picture' =>
                    $student->profile_photo,

                'login_method' =>
                    'google',
            ],
        ], 200);
    }

    /**
     * Logout current mobile student.
     */
    public function logout(Request $request)
    {
        $student =
            $request->user();

        if (
            $student &&
            $student->currentAccessToken()
        ) {
            $student
                ->currentAccessToken()
                ->delete();
        }

        return response()->json([
            'status' => 'success',

            'message' =>
                'Logged out successfully.',
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
            'email' => strtolower(
                trim(
                    (string) $request->input(
                        'email',
                        ''
                    )
                )
            ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validatedData =
            $request->validate(
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
                    'password.min' =>
                        'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .',

                    'password.regex' =>
                        'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of these symbols: !, _, -, .',
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | NORMALIZE EMAIL
        |--------------------------------------------------------------------------
        */

        $validatedData['email'] =
            strtolower(
                trim(
                    (string)
                    $validatedData['email']
                )
            );

        /*
        |--------------------------------------------------------------------------
        | CHECK UNIVERSITY EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            !str_ends_with(
                $validatedData['email'],
                '@ssct.edu.ph'
            )
        ) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'Only SSCT emails are allowed.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE STUDENT
        |--------------------------------------------------------------------------
        */

        $student =
            Student::create([
                'student_id_number' =>
                    $validatedData[
                        'student_id'
                    ],

                'first_name' =>
                    trim(
                        $validatedData[
                            'first_name'
                        ]
                    ),

                'middle_name' =>
                    isset(
                        $validatedData[
                            'middle_name'
                        ]
                    ) &&
                    trim(
                        $validatedData[
                            'middle_name'
                        ]
                    ) !== ''
                        ? trim(
                            $validatedData[
                                'middle_name'
                            ]
                        )
                        : null,

                'last_name' =>
                    trim(
                        $validatedData[
                            'last_name'
                        ]
                    ),

                'course' =>
                    $validatedData[
                        'course'
                    ],

                'email' =>
                    $validatedData[
                        'email'
                    ],

                'year_level' =>
                    $validatedData[
                        'year_level'
                    ],

                'department' =>
                    $validatedData[
                        'department'
                    ],

                'password' =>
                    Hash::make(
                        $validatedData[
                            'password'
                        ]
                    ),

                'profile_photo' =>
                    '/assets/logo.png',
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
            ->createToken(
                'student-token'
            )
            ->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'success',

            'message' =>
                'Registration successful!',

            'token' =>
                $token,

            'student_id' =>
                $student->student_id ??
                $student->student_id_number ??
                $student->id,

            'student' => [
                'id' =>
                    $student->id,

                'student_id' =>
                    $student->student_id ??
                    $student->student_id_number ??
                    null,

                'name' =>
                    $student->name,

                'first_name' =>
                    $student->first_name,

                'middle_name' =>
                    $student->middle_name,

                'last_name' =>
                    $student->last_name,

                'email' =>
                    $student->email,

                'course' =>
                    $student->course,

                'year_level' =>
                    $student->year_level,

                'department' =>
                    $student->department,

                'profile_photo' =>
                    $student->profile_photo,

                'profile_picture' =>
                    $student->profile_photo,
            ],
        ], 201);
    }
}