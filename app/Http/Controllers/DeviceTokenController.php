<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceTokenController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER MOBILE PUSH TOKEN
    |--------------------------------------------------------------------------
    |
    | Expected mobile payload:
    |
    | {
    |     "token": "ExponentPushToken[...]",
    |     "platform": "android"
    | }
    |
    | or platform = ios.
    |
    */

    public function store(
        Request $request
    ) {
        $student =
            $request->user();

        $validated =
            $request->validate([
                'token' =>
                    'required|string|max:2048',

                'platform' =>
                    'required|in:android,ios',
            ]);

        $token =
            trim(
                $validated['token']
            );

        $pushService =
            app(
                PushNotificationService::class
            );

        if (
            !$pushService
                ->isExpoPushToken(
                    $token
                )
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'The provided token is not a valid Expo push token.',
            ], 422);
        }

        $deviceToken =
            DB::transaction(
                function () use (
                    $student,
                    $token,
                    $validated
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | ONE TOKEN BELONGS TO THE CURRENT LOGGED-IN STUDENT
                    |--------------------------------------------------------------------------
                    |
                    | If the same physical app installation signs into another
                    | account, ownership of the token moves to that account.
                    |
                    */

                    $record =
                        DeviceToken::query()
                            ->where(
                                'token',
                                $token
                            )
                            ->first();

                    if (!$record) {
                        $record =
                            new DeviceToken();
                    }

                    $record->student_id =
                        $student->id;

                    $record->token =
                        $token;

                    $record->platform =
                        $validated[
                            'platform'
                        ];

                    $record->save();

                    return $record;
                }
            );

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Push notification token registered.',

            'data' =>
                $deviceToken,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE MOBILE PUSH TOKEN
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request
    ) {
        $student =
            $request->user();

        $validated =
            $request->validate([
                'token' =>
                    'required|string|max:2048',
            ]);

        $deleted =
            DeviceToken::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'token',
                    trim(
                        $validated['token']
                    )
                )
                ->delete();

        return response()->json([
            'status' =>
                'success',

            'message' =>
                $deleted
                    ? 'Push token removed.'
                    : 'Push token was already absent.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONAL EXISTING WEB PUSH SUPPORT
    |--------------------------------------------------------------------------
    |
    | These endpoints are preserved so the current API routes do not break.
    | Mobile Expo push remains the main Phase 8 implementation.
    |
    */

    public function storeWebPushSubscription(
        Request $request
    ) {
        $student =
            $request->user();

        $validated =
            $request->validate([
                'subscription' =>
                    'required|array',

                'subscription.endpoint' =>
                    'required|string',

                'subscription.keys' =>
                    'required|array',

                'subscription.keys.p256dh' =>
                    'required|string',

                'subscription.keys.auth' =>
                    'required|string',
            ]);

        $encoded =
            json_encode(
                $validated[
                    'subscription'
                ],
                JSON_UNESCAPED_SLASHES
            );

        $record =
            DeviceToken::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'platform',
                    'web'
                )
                ->where(
                    'token',
                    $encoded
                )
                ->first();

        if (!$record) {
            $record =
                new DeviceToken();

            $record->student_id =
                $student->id;

            $record->token =
                $encoded;

            $record->platform =
                'web';

            $record->save();
        }

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Web push subscription saved.',

            'data' =>
                $record,
        ]);
    }


    public function destroyWebPushSubscription(
        Request $request
    ) {
        $student =
            $request->user();

        $subscription =
            $request->input(
                'subscription'
            );

        $endpoint =
            $request->input(
                'endpoint'
            );

        $query =
            DeviceToken::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'platform',
                    'web'
                );

        if (
            is_array(
                $subscription
            )
        ) {
            $encoded =
                json_encode(
                    $subscription,
                    JSON_UNESCAPED_SLASHES
                );

            $query->where(
                'token',
                $encoded
            );
        } elseif (
            is_string(
                $endpoint
            )
            &&
            trim(
                $endpoint
            ) !== ''
        ) {
            $query->where(
                'token',
                'like',
                '%'
                .
                trim(
                    $endpoint
                )
                .
                '%'
            );
        } else {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'A web push subscription or endpoint is required.',
            ], 422);
        }

        $query->delete();

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Web push subscription removed.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED TEST PUSH
    |--------------------------------------------------------------------------
    |
    | This lets us verify Laravel -> Expo -> phone independently from the
    | Lost & Found workflow.
    |
    */

    public function test(
        Request $request,
        PushNotificationService $pushService
    ) {
        $student =
            $request->user();

        $result =
            $pushService
                ->sendToStudent(
                    (int)
                    $student->id,

                    'FindIt Push Test',

                    'Push notifications are connected successfully.',

                    [
                        'type' =>
                            'push_test',

                        'action' =>
                            'open_alerts',
                    ]
                );

        return response()->json([
            'status' =>
                $result[
                    'success'
                ]
                    ? 'success'
                    : 'warning',

            'message' =>
                $result[
                    'attempted'
                ] > 0
                    ? 'Push test sent to Expo Push Service.'
                    : 'No Expo push token is currently registered for this account.',

            'data' =>
                $result,
        ]);
    }
}
