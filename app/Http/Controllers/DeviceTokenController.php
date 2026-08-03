<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|max:64',
        ]);

        $deviceToken = DeviceToken::updateOrCreate([
            'student_id' => $user->id,
            'token' => $validated['token'],
        ], [
            'platform' => $validated['platform'] ?? 'unknown',
            'last_used_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'data' => $deviceToken]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        DeviceToken::where('student_id', $user->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Device token removed']);
    }
}
