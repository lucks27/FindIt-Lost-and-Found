<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Student;
use App\Models\StudentRecognitionRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Append-only administrator correction of a student's spendable point balance.
 * Never change old reward, return, or approved fine-conversion ledger entries.
 */
class AdminStudentPointAdjustmentController extends Controller
{
    private const POINTS_PER_PESO = 100;
    private const MAX_POINTS = 1000000;

    public function store(Request $request, int $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized administrator.',
            ], 401);
        }

        $validated = $request->validate([
            'expected_balance' => ['required', 'integer', 'min:0'],
            'target_points' => ['required', 'integer', 'min:0', 'max:' . self::MAX_POINTS],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        if (!Schema::hasTable('point_transactions') || !Schema::hasTable('audit_logs')) {
            return response()->json([
                'status' => 'error',
                'message' => 'The recognition ledger or audit table is not available. Contact the system administrator.',
            ], 503);
        }

        // All writes (points ledger, cached balance and audit) succeed or roll back together.
        // Lock the student so two administrator adjustments cannot use the same balance.
        $result = DB::transaction(function () use ($id, $admin, $validated, $request) {
            $student = Student::query()->lockForUpdate()->findOrFail($id);

            $balance = (int) DB::table('point_transactions')
                ->where('student_id', $student->id)
                ->sum('points');

            if ($balance < 0) {
                throw ValidationException::withMessages([
                    'target_points' => 'This account has a negative ledger balance. Reconcile its transactions before adjusting it.',
                ]);
            }

            if ($balance !== (int) $validated['expected_balance']) {
                return [
                    'conflict' => true,
                    'actual_balance' => $balance,
                ];
            }

            $target = (int) $validated['target_points'];
            $difference = $target - $balance;

            if ($difference === 0) {
                throw ValidationException::withMessages([
                    'target_points' => 'Enter an amount different from the current available points.',
                ]);
            }

            $reason = trim((string) $validated['reason']);
            $reference = 'admin-point-adjustment:' . (string) Str::uuid();

            // The September base schema uses an unsigned points column;
            // migration 2026_10_10_000002 changes it to signed for debits.
            DB::table('point_transactions')->insert([
                'student_id' => $student->id,
                'type' => 'admin_adjustment',
                'direction' => $difference > 0 ? 'credit' : 'debit',
                'points' => $difference,
                'balance_after' => $target,
                'reference_key' => $reference,
                'notes' => $reason,
                'processed_by_admin_id' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $summary = StudentRecognitionRecord::query()
                ->where('student_id', $student->id)
                ->first();

            StudentRecognitionRecord::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'recognition_points' => $target,
                    'peso_value' => round($target / self::POINTS_PER_PESO, 2),
                    'reward_count' => (int) ($summary?->reward_count ?? $student->reward_count ?? 0),
                    'report_count' => (int) ($summary?->report_count ?? $student->reports()->count()),
                ]
            );

            // Support both versions of audit_logs used by FindIt installations.
            $columns = array_flip(Schema::getColumnListing('audit_logs'));
            $audit = [
                'action' => 'student_points_adjusted',
                'subject_type' => 'student',
                'subject_id' => $student->id,
                'subject_label' => $student->name,
                'actor_type' => Admin::class,
                'actor_id' => $admin->id,
                'actor_admin_id' => $admin->id,
                'description' => sprintf(
                    '%s adjusted %s recognition points from %d to %d. Reason: %s',
                    $admin->name ?? 'USC administrator',
                    $student->name,
                    $balance,
                    $target,
                    $reason
                ),
                'details' => json_encode([
                    'module' => 'recognition_points',
                    'summary' => 'Administrator corrected a student recognition balance.',
                    'before' => $balance,
                    'after' => $target,
                    'change' => $difference,
                    'reason' => $reason,
                    'reference_key' => $reference,
                ], JSON_THROW_ON_ERROR),
                'old_values' => json_encode(['recognition_points' => $balance], JSON_THROW_ON_ERROR),
                'new_values' => json_encode(['recognition_points' => $target], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Do not silently save unlogged changes if the audit schema is incompatible.
            $required = ['action', 'subject_type', 'subject_id', 'description'];
            foreach ($required as $column) {
                if (!isset($columns[$column])) {
                    throw new \RuntimeException('Audit log schema missing required column: ' . $column);
                }
            }
            DB::table('audit_logs')->insert(array_intersect_key($audit, $columns));

            return [
                'conflict' => false,
                'student_id' => $student->id,
                'previous_points' => $balance,
                'recognition_points' => $target,
                'adjustment' => $difference,
                'reference_key' => $reference,
                'peso_value' => round($target / self::POINTS_PER_PESO, 2),
            ];
        }, 3);

        if ($result['conflict']) {
            return response()->json([
                'status' => 'error',
                'message' => 'The student balance changed while this form was open. Refresh and try again.',
                'data' => ['current_balance' => $result['actual_balance']],
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Student recognition points adjusted. A transaction and audit record were created.',
            'data' => $result,
        ]);
    }
}
