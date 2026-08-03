<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function loginRequest(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email is required',
            'password.required' => 'Password is required'
        ]);

        $admin = Admin::where('email', $validated['email'])->first();
        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'No admin account found with that email. Please contact support.'
            ], 401);
        }

        if (!Hash::check($validated['password'], $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password. Please try again.'
            ], 401);
        }

        if ($admin->role !== 'super_admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'This account does not have Super Admin access. Please use the correct login portal.'
            ], 403);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful! Redirecting to dashboard...',
            'admin_id' => $admin->id,
            'role' => $admin->role,
            'facility_id' => $admin->facility_id,
            'dashboard_route' => '/superadmin/list',
        ], 200);
    }

    public function storeStudentRecord(Request $request)
    {
        $admin = $this->adminUser();
        if (!$this->isSuperAdmin($admin)) {
            return $this->forbiddenResponse($request, 'Only the super admin can create student accounts.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_id' => 'required|string|max:20|unique:students,student_id',
            'email' => 'required|email|unique:students,email',
            'department' => 'required|string|max:255',
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $student = Student::create([
            'name' => $validated['name'],
            'student_id' => $validated['student_id'],
            'email' => $validated['email'],
            'department' => $validated['department'],
            'course' => $validated['course'],
            'year_level' => $validated['year_level'],
            'password' => Hash::make($validated['password']),
            'profile_picture' => '/assets/logo.png',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Student account created successfully.',
            'data' => $student,
        ]);
    }

    public function updateStudentRecord(Request $request, $id)
    {
        $admin = $this->adminUser();
        if (!$this->isSuperAdmin($admin)) {
            return $this->forbiddenResponse($request, 'Only the super admin can update student records.');
        }

        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_id' => 'required|string|max:20|unique:students,student_id,' . $student->id,
            'course' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email,' . $student->id,
        ]);

        $student->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Student record updated successfully.',
            'data' => $student->fresh(),
        ]);
    }

    public function deleteStudentRecord(Request $request, $id)
    {
        $admin = $this->adminUser();
        if (!$this->isSuperAdmin($admin)) {
            return $this->forbiddenResponse($request, 'Only the super admin can delete student records.');
        }

        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Student record deleted successfully.',
        ]);
    }

    private function forbiddenResponse(Request $request, string $message = 'Forbidden')
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $message
            ], 403);
        }

        abort(403, $message);
    }

    private function adminUser(): Admin
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            abort(401, 'Unauthorized.');
        }

        return $admin;
    }

    private function isSuperAdmin(Admin $admin): bool
    {
        return $admin->role === 'super_admin';
    }
}
