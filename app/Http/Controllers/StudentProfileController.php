<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\Notification;
use Laravel\Sanctum\PersonalAccessToken;

class StudentProfileController extends Controller
{
    /**
     * Get the authenticated student from bearer token or student guard.
     */
    private function getStudent()
    {
        // We support both Sanctum bearer-token auth and the web student guard.
        // For the mobile app, the bearer token path is the primary one.
        $bearerToken = request()->bearerToken();
        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);

            if (!$accessToken) {
                abort(401, 'Unauthorized: Invalid bearer token.');
            }

            if ($accessToken->tokenable instanceof Student) {
                return $accessToken->tokenable;
            }

            abort(403, 'Forbidden: Bearer token does not belong to a student.');
        }

        $student = Auth::guard('student')->user();
        if (!$student) {
            abort(401, 'Unauthorized: No authenticated student found');
        }

        return $student;
    }

    // =====================
    // VIEW PAGES
    // =====================

    public function profile()
    {
        return view('students.profile', ['student' => $this->getStudent()]);
    }

    public function profileEditPage()
    {
        return view('students.profile-edit', ['student' => $this->getStudent()]);
    }

    public function profileChangePassPage()
    {
        return view('students.profile-change-pass');
    }

    // =====================
    // API & ACTIONS
    // =====================

    public function apiProfile()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->getStudent(),
        ]);
    }

    public function profileEdit(Request $request)
    {
        $student = $this->getStudent();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email,' . $student->id,
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'department' => 'required|string|max:255',
        ]);

        // Capture original data for the notification
        $before = $student->only(['name', 'email', 'course', 'year_level', 'department']);

        $student->update($request->only(['name', 'email', 'course', 'year_level', 'department']));

        // Notification Log
        Notification::create([
            'student_id' => $student->id,
            'type' => 'profile_update',
            'title' => 'Student profile updated',
            'message' => "{$student->name} updated profile details.",
            'action' => 'PROFILE_UPDATED',
            'recipient_role' => 'super_admin',
            'subject_type' => 'student_profile',
            'subject_id' => $student->id,
            'data' => [
                'changes' => collect($before)->mapWithKeys(fn($value, $key) => [
                    $key => ['before' => $value, 'after' => $student->$key]
                ])->toArray(),
            ],
            'is_read' => false,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'data' => $student->fresh(),
            ]);
        }

        return redirect()->route('student.profile')->with('success', 'Profile updated successfully.');
    }

    // API alias for profile update
    public function apiUpdateProfile(Request $request)
    {
        return $this->profileEdit($request);
    }

    public function changePass(Request $request)
    {
        $student = $this->getStudent();

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->old_password, $student->password)) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Current password incorrect.'], 422)
                : back()->with('error', 'Current password is incorrect.');
        }

        $student->update(['password' => Hash::make($request->new_password)]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
        }

        return redirect()->route('student.profile')->with('success', 'Password changed successfully.');
    }

    // Alias for API route
    public function apiChangePassword(Request $request)
    {
        $student = $this->getStudent();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $student->password)) {
            return response()->json(['status' => 'error', 'message' => 'Current password is incorrect.'], 422);
        }

        $student->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['status' => 'success', 'message' => 'Password changed successfully.']);
    }

    public function updateProfilePicture(Request $request)
    {
        $student = $this->getStudent();

        $request->validate([
            'profile_picture' => 'required|image',
        ]);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profile_pictures'), $fileName);

            $student->update([
                'profile_photo' => 'uploads/profile_pictures/' . $fileName
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile picture updated successfully.',
                'data' => [
                    'profile_photo' => $student->profile_photo,
                    'profile_picture' => $student->profile_photo,
                ],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No file uploaded.',
        ], 422);

    }
}
