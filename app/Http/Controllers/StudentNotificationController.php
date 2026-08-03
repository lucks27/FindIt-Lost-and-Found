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

        $notifications = Notification::whereHas('booking', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })
            ->where(function ($q) use ($studentId) {
                $q->where(function ($sub) use ($studentId) {
                    $sub->where('recipient_role', 'student')
                        ->where('recipient_id', $studentId);
                })
                    ->orWhere(function ($sub) {
                        $sub->whereNull('recipient_id')
                            ->whereNull('recipient_role');
                    });
            })
            ->with('booking.facility')
            ->latest()
            ->get()
            ->unique(fn($item) => ($item->booking_id ?? $item->id) . '_' . strtolower($item->action ?? ''))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    public function deleteStudentNotification($id)
    {
        $studentId = $this->getAuthenticatedStudentId();

        $notification = Notification::whereHas('booking', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })->findOrFail($id);

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted successfully.'
        ]);
    }

    public function markStudentNotificationRead($id)
    {
        $studentId = $this->getAuthenticatedStudentId();

        $notification = Notification::whereHas('booking', function ($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })->findOrFail($id);

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
