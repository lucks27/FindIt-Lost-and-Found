<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET PROFILE
    |--------------------------------------------------------------------------
    */
    public function apiProfile()
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => $admin,
                'update_profile_url' => '/api/admin/profile-info',
                'change_password_url' => '/api/admin/profile-change-pass',
                'change_picture_url' => '/api/admin/profile-picture',
                'logout_url' => '/api/admin/logout',
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE
    |--------------------------------------------------------------------------
    */
    public function adminEditProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $originalName = $admin->name;

        $admin->update([
            'name' => $request->name,
        ]);

        // Force facility name sync when super admin changes profile name.
        if ($admin->role === 'super_admin' && $originalName !== $request->name) {
            \App\Models\Facility::query()->update(['administrator_name' => $request->name]);
        }

        // Notify if branch admin
        if ($admin->role !== 'super_admin' && $originalName !== $request->name) {
            Notification::create([
                'type' => 'profile_update',
                'title' => 'Branch admin updated profile',
                'message' => "{$admin->name} updated profile.",
                'action' => 'PROFILE_UPDATED',
                'facility_id' => $admin->facility_id,
                'actor_admin_id' => $admin->id,
                'recipient_role' => 'super_admin',
                'subject_type' => 'admin_profile',
                'subject_id' => $admin->id,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => $admin
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */
    public function adminChangePass(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->old_password, $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Old password is incorrect.'
            ], 422);
        }

        $admin->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE PICTURE (FIXED)
    |--------------------------------------------------------------------------
    */
    public function adminChangeProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {

            $file = $request->file('profile_picture');
            $fileName = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/admin_profiles'), $fileName);

            $admin->update([
                'profile_picture' => 'uploads/admin_profiles/' . $fileName
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile picture updated successfully.',
            'data' => $admin
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    public function adminLogout(Request $request)
    {
        Auth::guard('admin')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.'
        ]);
    }
}
