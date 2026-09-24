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

    public function storeWebPushSubscription(Request $request)
    {
        $user = $request->user();

        $subscription = $request->input('subscription');
        if (!is_array($subscription) || empty($subscription['endpoint'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'A valid browser push subscription is required.',
            ], 422);
        }

        $token = $subscription['endpoint'];

        $deviceToken = DeviceToken::updateOrCreate([
            'student_id' => $user->id,
            'token' => $token,
        ], [
            'platform' => 'web',
            'last_used_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Web push subscription saved.',
            'data' => $deviceToken,
        ]);
    }

    public function destroyWebPushSubscription(Request $request)
    {
        $user = $request->user();

        $subscription = $request->input('subscription');
        $endpoint = is_array($subscription) ? ($subscription['endpoint'] ?? null) : null;

        if (empty($endpoint)) {
            return response()->json([
                'status' => 'error',
                'message' => 'A valid browser push subscription is required.',
            ], 422);
        }

        DeviceToken::where('student_id', $user->id)
            ->where('token', $endpoint)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Web push subscription removed.',
        ]);
    }
}
