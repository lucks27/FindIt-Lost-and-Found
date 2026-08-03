<?php

namespace App\Http\Controllers;

use App\Models\User;  // Assuming you're working with the User model for profile management
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Show the profile details of the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        // Return the authenticated user's profile data
        return response()->json([
            'status' => 'success',
            'data' => Auth::user()
        ]);
    }

    /**
     * Update the profile of the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // Validate the incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),  // Ensure email is unique except for the current user
            'password' => 'nullable|string|min:8|confirmed',  // Optional password field
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Update the user's profile
        $user->name = $request->name;
        $user->email = $request->email;

        // If password is provided, hash it and update
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        // Save the updated user data
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'data' => $user
        ]);
    }
}
