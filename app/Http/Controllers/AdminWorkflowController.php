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

        $query = Report::query();

        if ($request->filled('type')) {
            $query->where('report_type', $request->input('type'));
        }

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->input('report_type'));
        }

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

        $items = $query->with('student')->latest()->get();

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
            'open_reports' => Report::whereIn('status', ['open', 'pending', 'matched'])->count(),
            'lost_reports' => Report::where('report_type', 'lost')->count(),
            'found_reports' => Report::where('report_type', 'found')->count(),
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

        return response()->json(['status' => 'success', 'data' => [
            'summary' => $summary,
            'recent_activity' => $recentActivity,
        ]]);
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

    public function receiveSurrenderRequest($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $surrender = SurrenderRequest::findOrFail($id);
        $surrender->update(['status' => 'received', 'received_by_admin_id' => $admin->id]);

        return response()->json(['status' => 'success', 'message' => 'Surrender request marked as received.', 'data' => $surrender]);
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

    public function verifyItem($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $verification = ItemVerification::findOrFail($id);
        $verification->update(['status' => 'approved', 'verifier_admin_id' => $admin->id, 'verified_at' => Carbon::now()]);

        return response()->json(['status' => 'success', 'message' => 'Item verification approved.', 'data' => $verification]);
    }

    public function rejectVerification($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $verification = ItemVerification::findOrFail($id);
        $verification->update(['status' => 'rejected', 'verifier_admin_id' => $admin->id, 'verified_at' => Carbon::now()]);

        return response()->json(['status' => 'success', 'message' => 'Item verification rejected.', 'data' => $verification]);
    }

    public function requestVerificationDetails(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $verification = ItemVerification::findOrFail($id);
        $verification->update(['status' => 'needs_more_information', 'notes' => $validated['notes'] ?? $verification->notes]);

        return response()->json(['status' => 'success', 'message' => 'Verification details requested.', 'data' => $verification]);
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

    public function approveClaim($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $claim = ClaimReview::findOrFail($id);
        $claim->update(['review_status' => 'approved', 'reviewed_by_admin_id' => $admin->id, 'reviewed_at' => Carbon::now()]);

        return response()->json(['status' => 'success', 'message' => 'Claim approved successfully.', 'data' => $claim]);
    }

    public function rejectClaim($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $claim = ClaimReview::findOrFail($id);
        $claim->update(['review_status' => 'rejected', 'reviewed_by_admin_id' => $admin->id, 'reviewed_at' => Carbon::now()]);

        return response()->json(['status' => 'success', 'message' => 'Claim rejected successfully.', 'data' => $claim]);
    }

    public function requestClaimInformation(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $claim = ClaimReview::findOrFail($id);
        $claim->update(['review_status' => 'information_requested', 'notes' => $validated['notes'] ?? $claim->notes]);

        return response()->json(['status' => 'success', 'message' => 'Claim information requested.', 'data' => $claim]);
    }

    public function completeItemRelease($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $release = ItemRelease::findOrFail($id);
        $release->update(['status' => 'completed', 'approved_by_admin_id' => $admin->id, 'released_at' => Carbon::now()]);

        return response()->json(['status' => 'success', 'message' => 'Item release completed.', 'data' => $release]);
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
