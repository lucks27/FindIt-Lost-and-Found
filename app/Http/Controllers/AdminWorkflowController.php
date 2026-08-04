<?php

namespace App\Http\Controllers;

use App\Models\AdminAnnouncement;
use App\Models\AuditLog;
use App\Models\ClaimReview;
use App\Models\EventFine;
use App\Models\ItemRelease;
use App\Models\ItemVerification;
use App\Models\Notification;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\Student;
use App\Models\SurrenderRequest;
use App\Models\AdminSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AdminWorkflowController extends Controller
{
    public function listStudents(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $students = Student::query()
            ->latest()
            ->get()
            ->map(function (Student $student) {
                return array_merge($student->toArray(), [
                    'recognition_points' => $student->rewards()->sum('amount') ?? 0,
                ]);
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'students' => $students,
            ],
        ]);
    }

    public function indexAuditLogs(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $logs = AuditLog::query()
            ->with('actorAdmin')
            ->latest('created_at')
            ->get()
            ->map(function (AuditLog $log) {
                $details = $log->details ?? [];
                $module = $details['module'] ?? $log->subject_type ?? 'system';
                $summary = $details['summary'] ?? $details['message'] ?? $log->subject_label ?? 'No summary available.';

                return [
                    'id' => $log->id,
                    'date' => $log->created_at?->toDateTimeString(),
                    'user_name' => $log->actorAdmin?->name ?? 'System',
                    'action' => $log->action,
                    'module' => $module,
                    'summary' => $summary,
                    'description' => $summary,
                    'details' => $summary,
                    'ip_address' => $log->ip_address,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    public function exportAuditLogs(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $logs = AuditLog::query()
            ->with('actorAdmin')
            ->latest('created_at')
            ->get();

        $fileName = 'audit-logs-' . now()->format('Ymd_His') . '.json';
        $relativePath = 'public/exports/' . $fileName;
        Storage::put($relativePath, json_encode($logs->map(fn($log) => [
            'id' => $log->id,
            'date' => $log->created_at?->toDateTimeString(),
            'user_name' => $log->actorAdmin?->name ?? 'System',
            'action' => $log->action,
            'subject_type' => $log->subject_type,
            'subject_label' => $log->subject_label,
            'details' => $log->details,
            'ip_address' => $log->ip_address,
        ])->all(), JSON_PRETTY_PRINT));

        return response()->json([
            'status' => 'success',
            'message' => 'Audit logs exported successfully.',
            'data' => [
                'url' => url('storage/exports/' . $fileName),
            ],
        ]);
    }

    public function indexAnnouncements(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = AdminAnnouncement::query()
            ->with('admin')
            ->latest('published_at')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexLostFoundItems(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $query = Report::query()
            ->where('type', 'found')
            ->whereIn('status', ['pending_verification', 'verified', 'under_claim_review', 'ready_for_release']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(location) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(category) LIKE ?', ["%{$search}%"]);
            });
        }

        $items = $query->with(['student', 'surrenderRequest', 'itemVerification', 'claimReview'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexAdminNotifications(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = Notification::query()
            ->with('actorAdmin')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function storeAnnouncement(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'audience' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
            'target_filters' => 'nullable|array',
        ]);

        $item = AdminAnnouncement::create([
            'admin_id' => $admin->id,
            ...$validated,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement saved successfully.',
            'data' => $item,
        ], 201);
    }

    public function indexClaimReviews(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = ClaimReview::query()
            ->with(['report.student', 'claimantStudent', 'reviewedByAdmin'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexItemVerifications(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = ItemVerification::query()
            ->with(['report.student', 'verifierAdmin'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexItemReleases(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = ItemRelease::query()
            ->with(['report.student', 'claimantStudent', 'approvedByAdmin'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexPotentialMatches(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = PotentialMatch::query()
            ->with(['sourceReport.student', 'matchedReport.student'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexSurrenderRequests(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = SurrenderRequest::query()
            ->with(['report.student', 'student', 'receivedByAdmin'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexEventFines(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = EventFine::query()
            ->with(['student', 'report', 'issuedByAdmin'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexAdminSettings(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = AdminSetting::query()
            ->with('updatedByAdmin')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function storeAdminSetting(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'key' => 'required|string|unique:admin_settings,key',
            'value' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $item = AdminSetting::create([
            'updated_by_admin_id' => $admin->id,
            ...$validated,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Setting saved successfully.',
            'data' => $item,
        ], 201);
    }

    public function dashboardMetrics(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $summary = [
            'total_reports' => Report::count(),
            'open_reports' => Report::whereIn('status', ['pending_approval', 'pending_surrender', 'pending_verification', 'verified', 'ready_for_release', 'under_claim_review'])->count(),
            'lost_reports' => Report::where('type', 'lost')->count(),
            'found_reports' => Report::where('type', 'found')->count(),
            'total_claims' => ClaimReview::count(),
            'total_releases' => ItemRelease::count(),
            'active_surrenders' => SurrenderRequest::where('status', 'pending')->count(),
            'total_point_rules' => PointRule::count(),
            'student_count' => Student::count(),
        ];

        $recentActivity = [
            ...Report::with('student')->latest()->take(3)->get()->map(fn($report) => [
                'id' => $report->id,
                'type' => 'report',
                'label' => "Report '{$report->title}' ({$report->status})",
                'when' => $report->created_at?->toDateTimeString(),
            ])->all(),
            ...ClaimReview::with(['report', 'claimantStudent'])->latest()->take(3)->get()->map(fn($item) => [
                'id' => 'claim-' . $item->id,
                'type' => 'claim',
                'label' => "Claim review for '{$item->report?->title}' ({$item->review_status})",
                'when' => $item->reviewed_at?->toDateTimeString() ?? $item->created_at?->toDateTimeString(),
            ])->all(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'recent_activity' => $recentActivity,
            ]
        ]);
    }

    public function indexCompletedReturns(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = ItemRelease::query()
            ->with(['report.student', 'claimantStudent', 'approvedByAdmin'])
            ->where(function ($query) {
                $query->whereIn('status', ['released', 'completed', 'returned'])
                    ->orWhereNotNull('released_at');
            })
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function indexPointRules(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = PointRule::query()
            ->with('updatedByAdmin')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function storePointRule(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'item_category' => 'required|string|max:120',
            'base_points' => 'required|integer|min:0',
            'active' => 'sometimes|boolean',
        ]);

        $item = PointRule::create([
            'updated_by_admin_id' => $admin->id,
            'active' => $validated['active'] ?? true,
            ...$validated,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Point rule created successfully.', 'data' => $item], 201);
    }

    public function updatePointRule(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $item = PointRule::findOrFail($id);
        $validated = $request->validate([
            'item_category' => 'sometimes|required|string|max:120',
            'base_points' => 'sometimes|required|integer|min:0',
            'active' => 'sometimes|boolean',
        ]);

        $item->update($validated);

        return response()->json(['status' => 'success', 'message' => 'Point rule updated successfully.', 'data' => $item]);
    }

    public function destroyPointRule($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $item = PointRule::findOrFail($id);
        $item->delete();

        return response()->json(['status' => 'success', 'message' => 'Point rule deleted successfully.']);
    }

    public function indexPointBalances(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $students = Student::query()
            ->withSum('pointTransactions as recognition_points', 'amount')
            ->withCount('reports')
            ->withCount('rewards')
            ->latest()
            ->get()
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'recognition_points' => (int) ($student->recognition_points ?? $student->point_transactions_sum_amount ?? 0),
                    'report_count' => $student->reports_count,
                    'reward_count' => $student->rewards_count,
                ];
            });

        return response()->json(['status' => 'success', 'data' => $students]);
    }

    public function markAdminNotificationRead($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['status' => 'success', 'message' => 'Notification marked as read.', 'data' => $notification]);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'audience' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
            'target_filters' => 'nullable|array',
        ]);

        $item->update($validated);

        return response()->json(['status' => 'success', 'message' => 'Announcement updated successfully.', 'data' => $item]);
    }

    public function deleteAnnouncement($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $item->delete();

        return response()->json(['status' => 'success', 'message' => 'Announcement deleted successfully.']);
    }

    public function publishAnnouncement($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? Carbon::now()]);

        return response()->json(['status' => 'success', 'message' => 'Announcement published successfully.', 'data' => $item]);
    }

    public function unpublishAnnouncement($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $item->update(['status' => 'draft']);

        return response()->json(['status' => 'success', 'message' => 'Announcement unpublished successfully.', 'data' => $item]);
    }

    public function approveLostReport($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);
        $report->update(['status' => 'approved']);

        return response()->json(['status' => 'success', 'message' => 'Report approved successfully.', 'data' => $report]);
    }

    public function rejectLostReport($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);
        $report->update(['status' => 'rejected']);

        return response()->json(['status' => 'success', 'message' => 'Report rejected successfully.', 'data' => $report]);
    }

    public function requestLostReportCorrection(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        $report = Report::findOrFail($id);
        $report->update(['status' => 'correction_requested', 'metadata' => array_merge($report->metadata ?? [], ['correction_request' => $validated['message'] ?? ''])]);

        return response()->json(['status' => 'success', 'message' => 'Correction requested successfully.', 'data' => $report]);
    }

    public function receiveSurrenderRequest(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $surrender = SurrenderRequest::where('id', $id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($surrender->received_at || $surrender->received_by_admin_id) {
                    return response()->json(['status' => 'error', 'message' => 'This surrender request has already been received.'], 409);
                }

                $report = $surrender->report()->lockForUpdate()->firstOrFail();

                if ($report->status !== 'pending_surrender') {
                    return response()->json(['status' => 'error', 'message' => 'This report is not pending surrender.'], 422);
                }

                $surrender->update([
                    'status' => 'received',
                    'received_by_admin_id' => $admin->id,
                    'received_at' => Carbon::now(),
                ]);

                $report->update(['status' => 'pending_verification']);

                $verification = ItemVerification::where('report_id', $report->id)->first();
                if (!$verification) {
                    $verification = ItemVerification::create([
                        'report_id' => $report->id,
                        'status' => 'pending',
                        'checklist' => [
                            'item_condition_checked' => false,
                            'serial_or_identifier_checked' => false,
                            'owner_details_confirmed' => false,
                        ],
                    ]);
                }

                Notification::create([
                    'student_id' => $report->student_id,
                    'title' => 'Item Received by USC',
                    'message' => "USC has received your found item '{$report->title}'. Verification is now in progress.",
                    'action' => 'item_received',
                    'recipient_id' => $report->student_id,
                    'recipient_role' => 'student',
                    'subject_type' => SurrenderRequest::class,
                    'subject_id' => $surrender->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'surrender_received', SurrenderRequest::class, $surrender->id, 'Surrender Request', [
                    'report_id' => $report->id,
                    'received_by_admin_id' => $admin->id,
                    'received_at' => $surrender->received_at?->toDateTimeString(),
                ], $request->ip());

                return response()->json([
                    'status' => 'success',
                    'message' => 'Surrender request marked as received.',
                    'data' => $surrender->fresh(['report', 'student', 'receivedByAdmin']),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function rejectSurrenderRequest($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $surrender = SurrenderRequest::findOrFail($id);
        $surrender->update(['status' => 'rejected']);

        return response()->json(['status' => 'success', 'message' => 'Surrender request rejected.', 'data' => $surrender]);
    }

    public function markSurrenderOverdue($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $surrender = SurrenderRequest::findOrFail($id);
        $surrender->update(['status' => 'overdue']);

        return response()->json(['status' => 'success', 'message' => 'Surrender request marked overdue.', 'data' => $surrender]);
    }

    public function verifyItem(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $verification = ItemVerification::where('id', $id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $verification->report()->lockForUpdate()->firstOrFail();

                if ($report->type !== 'found' || $report->status !== 'pending_verification') {
                    return response()->json(['status' => 'error', 'message' => 'This found report is not ready for verification approval.'], 422);
                }

                $notes = $request->input('notes');

                $verification->update([
                    'status' => 'verified',
                    'verifier_admin_id' => $admin->id,
                    'verified_at' => Carbon::now(),
                    'notes' => $notes ?? $verification->notes,
                    'checklist' => array_merge($verification->checklist ?? [], [
                        'verified_by_admin' => $admin->id,
                    ]),
                ]);

                $report->update(['status' => 'verified']);

                Notification::create([
                    'student_id' => $report->student_id,
                    'title' => 'Item Verified by USC',
                    'message' => "USC has verified your found item '{$report->title}' and it is now visible in the student feed.",
                    'action' => 'item_verified',
                    'recipient_id' => $report->student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ItemVerification::class,
                    'subject_id' => $verification->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'verification_approved', ItemVerification::class, $verification->id, 'Item Verification', [
                    'report_id' => $report->id,
                    'verifier_admin_id' => $admin->id,
                    'verified_at' => $verification->verified_at?->toDateTimeString(),
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Item verification approved.', 'data' => $verification->fresh(['report', 'verifierAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function rejectVerification(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $verification = ItemVerification::where('id', $id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $verification->report()->lockForUpdate()->firstOrFail();
                $verification->update([
                    'status' => 'rejected',
                    'verifier_admin_id' => $admin->id,
                    'verified_at' => Carbon::now(),
                    'notes' => $validated['notes'],
                ]);

                $report->update(['status' => 'rejected']);

                Notification::create([
                    'student_id' => $report->student_id,
                    'title' => 'Item Verification Rejected',
                    'message' => "USC rejected the verification of your found item '{$report->title}'.",
                    'action' => 'item_verification_rejected',
                    'recipient_id' => $report->student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ItemVerification::class,
                    'subject_id' => $verification->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'verification_rejected', ItemVerification::class, $verification->id, 'Item Verification', [
                    'report_id' => $report->id,
                    'rejection_notes' => $validated['notes'],
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Item verification rejected.', 'data' => $verification->fresh(['report', 'verifierAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function requestVerificationDetails(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $verification = ItemVerification::where('id', $id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $verification->report()->lockForUpdate()->firstOrFail();
                $verification->update([
                    'status' => 'needs_more_information',
                    'notes' => $validated['notes'],
                ]);

                Notification::create([
                    'student_id' => $report->student_id,
                    'title' => 'Verification Details Needed',
                    'message' => "USC requested more details for your found item '{$report->title}'.",
                    'action' => 'verification_details_requested',
                    'recipient_id' => $report->student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ItemVerification::class,
                    'subject_id' => $verification->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'verification_details_requested', ItemVerification::class, $verification->id, 'Item Verification', [
                    'report_id' => $report->id,
                    'notes' => $validated['notes'],
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Verification details requested.', 'data' => $verification->fresh(['report', 'verifierAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function confirmPotentialMatch($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $match = PotentialMatch::findOrFail($id);
        $match->update(['status' => 'confirmed']);

        return response()->json(['status' => 'success', 'message' => 'Potential match confirmed.', 'data' => $match]);
    }

    public function rejectPotentialMatch($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $match = PotentialMatch::findOrFail($id);
        $match->update(['status' => 'rejected']);

        return response()->json(['status' => 'success', 'message' => 'Potential match rejected.', 'data' => $match]);
    }

    public function approveClaim(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $claim = ClaimReview::where('id', $id)
                    ->whereIn('review_status', ['pending', 'additional_information_required'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $claim->report()->lockForUpdate()->firstOrFail();
                if ($report->type !== 'found' || $report->status !== 'verified') {
                    return response()->json(['status' => 'error', 'message' => 'Only verified found items can be approved for claim review.'], 422);
                }

                $claim->update([
                    'review_status' => 'approved',
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ]);

                $report->update(['status' => 'ready_for_release']);

                $matchingLostReports = PotentialMatch::where('matched_report_id', $report->id)
                    ->with('sourceReport')
                    ->get();

                foreach ($matchingLostReports as $match) {
                    if ($match->sourceReport && $match->sourceReport->type === 'lost') {
                        $match->sourceReport->update(['status' => 'under_claim_review']);
                    }
                }

                $release = ItemRelease::where('report_id', $report->id)
                    ->where('claimant_student_id', $claim->claimant_student_id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->first();

                if (!$release) {
                    $release = ItemRelease::create([
                        'report_id' => $report->id,
                        'claimant_student_id' => $claim->claimant_student_id,
                        'approved_by_admin_id' => $admin->id,
                        'release_code' => Str::uuid(),
                        'status' => 'pending',
                        'checklist' => [
                            'claim_approved' => true,
                            'claimant_identity_checked' => false,
                            'ownership_proof_confirmed' => false,
                            'physical_item_condition_checked' => false,
                            'claimant_acknowledgment_completed' => false,
                        ],
                        'scheduled_at' => Carbon::now(),
                    ]);
                }

                Notification::create([
                    'student_id' => $claim->claimant_student_id,
                    'title' => 'Claim Approved',
                    'message' => "USC approved your claim for '{$report->title}'.",
                    'action' => 'claim_approved',
                    'recipient_id' => $claim->claimant_student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'claim_approved', ClaimReview::class, $claim->id, 'Claim Review', [
                    'report_id' => $report->id,
                    'claimant_student_id' => $claim->claimant_student_id,
                    'release_id' => $release->id,
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Claim approved successfully.', 'data' => $claim->fresh(['report', 'claimantStudent', 'reviewedByAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function rejectClaim(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $claim = ClaimReview::where('id', $id)
                    ->whereIn('review_status', ['pending', 'additional_information_required'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $claim->update([
                    'review_status' => 'rejected',
                    'notes' => $validated['notes'],
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ]);

                Notification::create([
                    'student_id' => $claim->claimant_student_id,
                    'title' => 'Claim Rejected',
                    'message' => "USC rejected your claim for '{$claim->report?->title}'.",
                    'action' => 'claim_rejected',
                    'recipient_id' => $claim->claimant_student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'claim_rejected', ClaimReview::class, $claim->id, 'Claim Review', [
                    'report_id' => $claim->report_id,
                    'claimant_student_id' => $claim->claimant_student_id,
                    'notes' => $validated['notes'],
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Claim rejected successfully.', 'data' => $claim->fresh(['report', 'claimantStudent', 'reviewedByAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function requestClaimInformation(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $claim = ClaimReview::where('id', $id)
                    ->whereIn('review_status', ['pending', 'additional_information_required'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $claim->update([
                    'review_status' => 'additional_information_required',
                    'notes' => $validated['notes'],
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ]);

                Notification::create([
                    'student_id' => $claim->claimant_student_id,
                    'title' => 'More Claim Information Needed',
                    'message' => "USC requested more proof for your claim on '{$claim->report?->title}'.",
                    'action' => 'claim_information_requested',
                    'recipient_id' => $claim->claimant_student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'claim_information_requested', ClaimReview::class, $claim->id, 'Claim Review', [
                    'report_id' => $claim->report_id,
                    'claimant_student_id' => $claim->claimant_student_id,
                    'notes' => $validated['notes'],
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Claim information requested.', 'data' => $claim->fresh(['report', 'claimantStudent', 'reviewedByAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function completeItemRelease(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $release = ItemRelease::where('id', $id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->firstOrFail();

                $requiredChecklist = [
                    'claim_approved',
                    'claimant_identity_checked',
                    'ownership_proof_confirmed',
                    'physical_item_condition_checked',
                    'claimant_acknowledgment_completed',
                ];

                $checklist = $release->checklist ?? [];
                foreach ($requiredChecklist as $key) {
                    if (!($checklist[$key] ?? false)) {
                        return response()->json(['status' => 'error', 'message' => 'All release checklist items must be completed before return completion.'], 422);
                    }
                }

                $claim = ClaimReview::where('report_id', $release->report_id)
                    ->where('claimant_student_id', $release->claimant_student_id)
                    ->where('review_status', 'approved')
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $release->report()->lockForUpdate()->firstOrFail();
                $finder = $report->student;

                $release->update([
                    'status' => 'released',
                    'approved_by_admin_id' => $admin->id,
                    'released_at' => Carbon::now(),
                ]);

                $claim->update(['review_status' => 'completed']);
                $report->update(['status' => 'completed_return']);

                if ($finder) {
                    $pointRule = PointRule::where('item_category', $report->category)
                        ->where('active', true)
                        ->orderByDesc('id')
                        ->first();

                    $existingTransaction = PointTransaction::where('reference_type', ItemRelease::class)
                        ->where('reference_id', $release->id)
                        ->where('student_id', $finder->id)
                        ->where('type', 'return_completion')
                        ->exists();

                    if (!$existingTransaction) {
                        PointTransaction::create([
                            'student_id' => $finder->id,
                            'amount' => (int) ($pointRule?->base_points ?? 5),
                            'type' => 'return_completion',
                            'description' => 'Recognition points awarded for completed return.',
                            'reference_type' => ItemRelease::class,
                            'reference_id' => $release->id,
                            'created_by_admin_id' => $admin->id,
                        ]);
                    }
                }

                $matchingLostReports = PotentialMatch::where('matched_report_id', $report->id)
                    ->with('sourceReport')
                    ->get();

                foreach ($matchingLostReports as $match) {
                    if ($match->sourceReport && $match->sourceReport->type === 'lost') {
                        $match->sourceReport->update(['status' => 'completed_return']);
                    }
                }

                Notification::create([
                    'student_id' => $finder?->id,
                    'title' => 'Item Return Completed',
                    'message' => "USC completed the return of your found item '{$report->title}'.",
                    'action' => 'return_completed',
                    'recipient_id' => $finder?->id,
                    'recipient_role' => 'student',
                    'subject_type' => ItemRelease::class,
                    'subject_id' => $release->id,
                    'is_read' => false,
                ]);

                if ($claim->claimant_student_id) {
                    Notification::create([
                        'student_id' => $claim->claimant_student_id,
                        'title' => 'Item Return Completed',
                        'message' => "USC completed your item release for '{$report->title}'.",
                        'action' => 'claim_return_completed',
                        'recipient_id' => $claim->claimant_student_id,
                        'recipient_role' => 'student',
                        'subject_type' => ItemRelease::class,
                        'subject_id' => $release->id,
                        'is_read' => false,
                    ]);
                }

                $this->recordAuditLog($admin, 'release_completed', ItemRelease::class, $release->id, 'Item Release', [
                    'report_id' => $report->id,
                    'claim_id' => $claim->id,
                    'claimant_student_id' => $claim->claimant_student_id,
                    'finder_student_id' => $finder?->id,
                    'released_at' => $release->released_at?->toDateTimeString(),
                ], $request->ip());

                return response()->json(['status' => 'success', 'message' => 'Item release completed.', 'data' => $release->fresh(['report', 'claimantStudent', 'approvedByAdmin'])]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    private function recordAuditLog($admin, string $action, string $subjectType, int $subjectId, string $subjectLabel, array $details = [], ?string $ipAddress = null): void
    {
        AuditLog::create([
            'actor_admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'details' => array_merge($details, [
                'module' => 'workflow',
            ]),
            'ip_address' => $ipAddress,
        ]);
    }

    public function approveEventFineConversion($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $fine = EventFine::findOrFail($id);
        $fine->update(['status' => 'approved']);

        return response()->json(['status' => 'success', 'message' => 'Event fine conversion approved.', 'data' => $fine]);
    }

    public function rejectEventFineConversion($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $fine = EventFine::findOrFail($id);
        $fine->update(['status' => 'rejected']);

        return response()->json(['status' => 'success', 'message' => 'Event fine conversion rejected.', 'data' => $fine]);
    }
}
