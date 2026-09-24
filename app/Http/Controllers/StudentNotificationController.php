<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class StudentNotificationController extends Controller
{
    public function apiNotifications()
    {
        $studentId = $this->getAuthenticatedStudentId();

        $notifications = Notification::where(function ($q) use ($studentId) {
            $q->where(function ($sub) use ($studentId) {
                $sub->where('recipient_role', 'student')
                    ->where('recipient_id', $studentId);
            })->orWhereHas('booking', function ($bookingQuery) use ($studentId) {
                $bookingQuery->where('student_id', $studentId);
            });
        })
            ->with('booking.facility')
            ->latest()
            ->get()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    public function deleteStudentNotification($id)
    {
        $studentId = $this->getAuthenticatedStudentId();

        $notification = Notification::where('id', $id)
            ->where(function ($q) use ($studentId) {
                $q->where(function ($sub) use ($studentId) {
                    $sub->where('recipient_role', 'student')
                        ->where('recipient_id', $studentId);
                })->orWhereHas('booking', function ($bookingQuery) use ($studentId) {
                    $bookingQuery->where('student_id', $studentId);
                });
            })
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted successfully.'
        ]);
    }

    public function markStudentNotificationRead($id)
    {
        $studentId = $this->getAuthenticatedStudentId();

        $notification = Notification::where('id', $id)
            ->where(function ($q) use ($studentId) {
                $q->where(function ($sub) use ($studentId) {
                    $sub->where('recipient_role', 'student')
                        ->where('recipient_id', $studentId);
                })->orWhereHas('booking', function ($bookingQuery) use ($studentId) {
                    $bookingQuery->where('student_id', $studentId);
                });
            })
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.'
        ]);
    }

    private function getAuthenticatedStudentId(): ?int
    {
        $bearerToken = request()->bearerToken();
        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            if ($accessToken && $accessToken->tokenable instanceof Student) {
                return $accessToken->tokenable->id;
            }

            abort(401, 'Unauthorized: Invalid student token.');
        }

        return Auth::guard('student')->id();
    }
}
