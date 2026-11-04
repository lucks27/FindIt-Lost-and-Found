<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use App\Models\Notification;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\StudentRecognitionRecord;
use Laravel\Sanctum\PersonalAccessToken;

class StudentProfileController extends Controller
{
    /** Keep existing Sanctum bearer and student-guard auth behavior. */
    private function getStudent()
    {
        $bearerToken = request()->bearerToken();
        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            if (!$accessToken) abort(401, 'Unauthorized: Invalid bearer token.');
            if ($accessToken->tokenable instanceof Student) return $accessToken->tokenable;
            abort(403, 'Forbidden: Bearer token does not belong to a student.');
        }

        $student = Auth::guard('student')->user();
        if (!$student) abort(401, 'Unauthorized: No authenticated student found');
        return $student;
    }

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

    public function apiProfile()
    {
        return response()->json(['status' => 'success', 'data' => $this->getStudent()]);
    }

    public function apiRecognition()
    {
        $student = $this->getStudent();
        $record = StudentRecognitionRecord::firstOrCreate(
            ['student_id' => $student->id],
            ['recognition_points' => 0, 'peso_value' => 0, 'reward_count' => 0, 'report_count' => 0]
        );

        // Signed point ledger is authoritative for conversions and available
        // points. Refresh only the serialized response, not historical records.
        $availablePoints = max(0, (int) PointTransaction::where('student_id', $student->id)->sum('points'));
        $record->setAttribute('recognition_points', $availablePoints);
        $record->setAttribute('peso_value', round($availablePoints / 100, 2));

        $transactions = PointTransaction::where('student_id', $student->id)
            ->latest()->limit(20)->get();

        // The actual current schema has is_active; querying a nonexistent
        // `active` column caused MySQL 1054 in some installations.
        $pointRules = PointRule::query();
        if (Schema::hasColumn('point_rules', 'is_active')) {
            $pointRules->where('is_active', true);
        } elseif (Schema::hasColumn('point_rules', 'active')) {
            $pointRules->where('active', true);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'record' => $record,
                'transactions' => $transactions,
                'point_rules' => $pointRules->get(),
                'conversion_rate' => 100,
            ],
        ])->header('Cache-Control', 'no-store, private');
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

        $before = $student->only(['name', 'email', 'course', 'year_level', 'department']);
        $student->update($request->only(['name', 'email', 'course', 'year_level', 'department']));

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
                    $key => ['before' => $value, 'after' => $student->$key],
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

    public function apiUpdateProfile(Request $request)
    {
        return $this->profileEdit($request);
    }

    public function changePass(Request $request)
    {
        $student = $this->getStudent();
        $request->validate([
            'old_password' => 'required',
            'new_password' => ['required', 'min:6', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{6,}$/'],
        ], [
            'new_password.min' => 'Password must be at least 6 characters and include one uppercase letter, one number, and one of !, _, -, .',
            'new_password.regex' => 'Password must be at least 6 characters and include one uppercase letter, one number, and one of !, _, -, .',
        ]);

        if (!Hash::check($request->old_password, $student->password)) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Current password incorrect.'], 422)
                : back()->with('error', 'Current password is incorrect.');
        }

        $student->update(['password' => Hash::make($request->new_password)]);
        return $request->expectsJson()
            ? response()->json(['status' => 'success', 'message' => 'Password changed successfully.'])
            : redirect()->route('student.profile')->with('success', 'Password changed successfully.');
    }

    public function apiChangePassword(Request $request)
    {
        $student = $this->getStudent();
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{8,}$/'],
        ], [
            'new_password.regex' => 'Use at least 8 characters, one uppercase letter, one number, and one of !, _, -, .',
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
            'profile_picture' => 'required|file|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);
        if (!$request->hasFile('profile_picture')) {
            return response()->json(['status' => 'error', 'message' => 'No file uploaded.'], 422);
        }

        $path = $request->file('profile_picture')->store('profile-pictures', 'public');
        $student->update(['profile_photo' => 'storage/' . $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile picture updated successfully.',
            'data' => [
                'profile_photo' => $student->profile_photo,
                'profile_picture' => $student->profile_photo,
            ],
        ]);
    }
}
