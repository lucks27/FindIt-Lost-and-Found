<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PushNotificationService
{
    private const EXPO_PUSH_URL =
        'https://exp.host/--/api/v2/push/send';


    /*
    |--------------------------------------------------------------------------
    | SEND PUSH TO ONE STUDENT
    |--------------------------------------------------------------------------
    |
    | The database/in-app Notification record remains the source of truth.
    | Push delivery is best-effort and must never break the Lost & Found
    | workflow.
    |
    */

    public function sendToStudent(
        int $studentId,
        string $title,
        string $body,
        array $data = []
    ): array {
        if ($studentId <= 0) {
            return [
                'success' => false,
                'attempted' => 0,
                'accepted' => 0,
                'message' => 'Invalid student id.',
            ];
        }

        $deviceTokens =
            DeviceToken::query()
                ->where(
                    'student_id',
                    $studentId
                )
                ->get();

        if ($deviceTokens->isEmpty()) {
            return [
                'success' => true,
                'attempted' => 0,
                'accepted' => 0,
                'message' =>
                    'No registered push token for this student.',
            ];
        }

        $expoTokens =
            $deviceTokens
                ->filter(
                    function ($deviceToken) {
                        $token =
                            trim(
                                (string)
                                $deviceToken->token
                            );

                        return $this
                            ->isExpoPushToken(
                                $token
                            );
                    }
                )
                ->values();

        if ($expoTokens->isEmpty()) {
            return [
                'success' => true,
                'attempted' => 0,
                'accepted' => 0,
                'message' =>
                    'No Expo push token is registered for this student.',
            ];
        }

        $attempted = 0;
        $accepted = 0;
        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | EXPO ACCEPTS PUSH MESSAGES IN BATCHES
        |--------------------------------------------------------------------------
        */

        foreach (
            $expoTokens->chunk(100)
            as $tokenChunk
        ) {
            $payloads =
                $tokenChunk
                    ->map(
                        function (
                            $deviceToken
                        ) use (
                            $title,
                            $body,
                            $data
                        ) {
                            $attemptData =
                                array_merge(
                                    $data,
                                    [
                                        'sent_at' =>
                                            now()
                                                ->toIso8601String(),
                                    ]
                                );

                            return [
                                'to' =>
                                    trim(
                                        (string)
                                        $deviceToken->token
                                    ),

                                'title' =>
                                    $title,

                                'body' =>
                                    $body,

                                'sound' =>
                                    'default',

                                'priority' =>
                                    'high',

                                'channelId' =>
                                    'default',

                                'data' =>
                                    $attemptData,
                            ];
                        }
                    )
                    ->values()
                    ->all();

            $attempted +=
                count(
                    $payloads
                );

            try {
                $response =
                    Http::acceptJson()
                        ->asJson()
                        ->timeout(15)
                        ->post(
                            self::EXPO_PUSH_URL,
                            $payloads
                        );

                if (
                    !$response->successful()
                ) {
                    $errors[] =
                        'Expo Push Service returned HTTP '
                        .
                        $response->status();

                    Log::warning(
                        'Expo push request failed',
                        [
                            'student_id' =>
                                $studentId,

                            'status' =>
                                $response->status(),

                            'body' =>
                                $response->body(),
                        ]
                    );

                    continue;
                }

                $tickets =
                    $response->json(
                        'data'
                    );

                if (
                    !is_array(
                        $tickets
                    )
                ) {
                    $tickets =
                        [];
                }

                foreach (
                    $tickets
                    as $index =>
                    $ticket
                ) {
                    if (
                        (
                            $ticket['status']
                            ?? null
                        )
                        ===
                        'ok'
                    ) {
                        $accepted++;
                        continue;
                    }

                    $errorCode =
                        $ticket[
                            'details'
                        ][
                            'error'
                        ]
                        ?? null;

                    $message =
                        $ticket[
                            'message'
                        ]
                        ??
                        'Unknown Expo push error.';

                    $errors[] =
                        $message;

                    /*
                    |--------------------------------------------------------------------------
                    | REMOVE TOKENS EXPO SAYS ARE NO LONGER REGISTERED
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $errorCode ===
                        'DeviceNotRegistered'
                    ) {
                        $tokenModel =
                            $tokenChunk
                                ->values()
                                ->get(
                                    $index
                                );

                        if ($tokenModel) {
                            $tokenModel
                                ->delete();
                        }
                    }
                }
            } catch (
                \Throwable $exception
            ) {
                $errors[] =
                    $exception
                        ->getMessage();

                Log::warning(
                    'Expo push exception',
                    [
                        'student_id' =>
                            $studentId,

                        'message' =>
                            $exception
                                ->getMessage(),
                    ]
                );
            }
        }

        return [
            'success' =>
                empty(
                    $errors
                ),

            'attempted' =>
                $attempted,

            'accepted' =>
                $accepted,

            'errors' =>
                $errors,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | EXPO TOKEN FORMAT
    |--------------------------------------------------------------------------
    */

    public function isExpoPushToken(
        ?string $token
    ): bool {
        if (!$token) {
            return false;
        }

        return
            Str::startsWith(
                $token,
                'ExponentPushToken['
            )
            ||
            Str::startsWith(
                $token,
                'ExpoPushToken['
            );
    }
}
