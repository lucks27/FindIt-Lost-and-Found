<?php

namespace App\Http\Controllers;

use App\Models\EventFine;
use App\Models\EventFineConversion;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Student-created point-to-fine conversion requests.
 * This controller NEVER deducts points or pays fines: the existing USC
 * AdminWorkflowController approval endpoint alone performs those actions.
 */
class StudentFinePaymentController extends Controller
{
    private const POINTS_PER_PESO = 100;

    private function student(Request $request): Student
    {
        $student = $request->user();
        abort_unless($student instanceof Student, 403, 'Student access only.');
        return $student;
    }

    private function activeFines(int $studentId)
    {
        return EventFine::query()
            ->where('student_id', $studentId)
            ->whereNotIn('status', ['paid', 'rejected'])
            ->where('remaining_amount', '>', 0)
            ->orderBy('id');
    }

    public function index(Request $request)
    {
        $student = $this->student($request);
        $fines = $this->activeFines($student->id)->get();
        $balance = max(0, (int) PointTransaction::query()
            ->where('student_id', $student->id)->sum('points'));
        $outstanding = round((float) $fines->sum('remaining_amount'), 2);
        $latest = EventFineConversion::query()
            ->where('student_id', $student->id)->latest('id')->limit(15)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'available_points' => $balance,
                'conversion_rate' => self::POINTS_PER_PESO,
                'outstanding_amount' => $outstanding,
                'points_needed_for_full_payment' => (int) ceil($outstanding * self::POINTS_PER_PESO),
                'pending_request' => $latest->firstWhere('status', 'pending'),
                'requests' => $latest,
                'fines' => $fines->map(fn ($fine) => [
                    'id' => $fine->id,
                    'event_name' => $fine->event_name,
                    'remaining_amount' => (float) $fine->remaining_amount,
                    'status' => $fine->status,
                    'due_at' => $fine->due_at,
                ])->values(),
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request)
    {
        $student = $this->student($request);
        $validated = $request->validate([
            'requested_points' => 'required|integer|min:1',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        return DB::transaction(function () use ($student, $validated) {
            // All balance-changing workflows should lock the same student row.
            Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            $pending = EventFineConversion::query()
                ->where('student_id', $student->id)
                ->where('status', 'pending')->first();
            if ($pending) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You already have a pending payment request awaiting USC approval.',
                ], 409);
            }

            $fines = $this->activeFines($student->id)->lockForUpdate()->get();
            $outstanding = round((float) $fines->sum('remaining_amount'), 2);
            if ($outstanding <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have no outstanding USC event fines.',
                ], 422);
            }

            $available = max(0, (int) PointTransaction::query()
                ->where('student_id', $student->id)->sum('points'));
            $requested = (int) $validated['requested_points'];
            $maximumForBalance = (int) ceil($outstanding * self::POINTS_PER_PESO);
            if ($requested > $available) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Insufficient recognition points. You have {$available} available.",
                    'data' => ['available_points' => $available],
                ], 422);
            }
            if ($requested > $maximumForBalance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Requested points exceed the amount needed to cover your fine balance.',
                ], 422);
            }

            $credit = round($requested / self::POINTS_PER_PESO, 2);
            $totalOriginal = round((float) $fines->sum(
                fn ($fine) => (float) ($fine->original_amount ?? $fine->amount ?? 0)
            ), 2);
            $conversion = EventFineConversion::create([
                // One existing fine anchors the request even on older schemas
                // where event_fine_id is not nullable. Admin still applies the
                // credit across active fines (oldest first).
                'event_fine_id' => $fines->first()->id,
                'student_id' => $student->id,
                'total_original_amount' => $totalOriginal,
                'requested_points' => $requested,
                'converted_points' => $requested,
                'conversion_rate' => self::POINTS_PER_PESO,
                'credit_amount' => $credit,
                'peso_value_converted' => $credit,
                'remaining_balance' => round(max(0, $outstanding - $credit), 2),
                'review_notes' => $validated['review_notes'] ?? null,
                'status' => 'pending',
                'reviewed_by_admin_id' => null,
                'reviewed_at' => null,
            ]);

            Notification::create([
                'type' => 'fine_request',
                'title' => 'Student Point Payment Request',
                'message' => "{$student->name} requested a {$requested}-point conversion (₱" . number_format($credit, 2) . ') for USC event fines.',
                'action' => 'fine_request',
                'status' => 'pending',
                'recipient_role' => 'admin',
                'subject_type' => EventFineConversion::class,
                'subject_id' => $conversion->id,
                'data' => ['student_id' => $student->id, 'conversion_id' => $conversion->id],
                'is_read' => false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Your payment request was sent to USC. Points will only be deducted if approved.',
                'data' => $conversion,
            ], 201);
        });
    }
}
