<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAnnouncement;
use App\Models\ArchiveItem;
use App\Models\AuditLog;
use App\Models\ClaimReview;
use App\Models\EventFine;
use App\Models\EventFineArchive;
use App\Models\EventFineEvent;
use App\Models\ItemCategory;
use App\Models\ItemRelease;
use App\Models\ItemVerification;
use App\Models\Notification;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\Student;
use App\Models\StudentRecognitionRecord;
use App\Models\SurrenderRequest;
use App\Models\AdminSetting;
use App\Models\VisiblePostedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AdminWorkflowController extends Controller
{
    private const POINTS_PER_PESO = 100;

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
            ->with('recognitionRecord')
            ->latest()
            ->get()
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number,
                    'name' => $student->name,
                    'email' => $student->email,
                    'course' => $student->course,
                    'department' => $student->department,
                    'year_level' => $student->year_level,
                    'profile_photo' => $student->profile_photo,
                    'status' => $student->status,
                    'account_status' => $student->status,
                    'created_at' => $student->created_at,
                    'updated_at' => $student->updated_at,
                    'recognition_points' => (int) ($student->recognitionRecord?->recognition_points ?? 0),
                ];
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
                    'date' => $log->created_at instanceof \DateTimeInterface
                        ? $log->created_at->format('Y-m-d H:i:s')
                        : $log->created_at,
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
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
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

    public function indexPendingReports(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $query = Report::query()
            ->where('type', 'lost')
            ->whereIn('status', ['pending', 'pending_approval', 'correction_requested']);

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

        $items = $query->with(['student', 'images'])->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexMobilePostItems(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = VisiblePostedItem::query()
            ->with(['report.student', 'report.images'])
            ->latest('posted_at')
            ->get()
            ->map(function (VisiblePostedItem $visibleItem) {
                $report = $visibleItem->report;

                if (!$report) {
                    return null;
                }

                return [
                    'id' => $report->id,
                    'report_id' => $report->id,
                    'title' => $report->title,
                    'type' => $report->type,
                    'status' => $report->status,
                    'location' => $report->location,
                    'published_at' => $report->published_at,
                    'is_visible' => (bool) $visibleItem->is_visible,
                    'student' => $report->student ? $report->student->toArray() : null,
                    'student_name' => $report->student?->full_name,
                    'images' => $report->images ? $report->images->toArray() : [],
                    'item' => $report,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexArchive(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = ArchiveItem::query()
            ->with(['report.student', 'report.images', 'report.category'])
            ->latest('archived_at')
            ->get()
            ->map(function (ArchiveItem $archiveItem) {
                $report = $archiveItem->report;

                if (!$report) {
                    return null;
                }

                return [
                    'id' => $report->id,
                    'report_id' => $report->id,
                    'title' => $report->title,
                    'type' => $report->type,
                    'status' => $report->status,
                    'student' => $report->student ? $report->student->toArray() : null,
                    'student_name' => $report->student?->full_name,
                    'archived_at' => $archiveItem->archived_at,
                    'archive_type' => $archiveItem->archive_type,
                    'reason' => $archiveItem->reason,
                    'item' => $report,
                ];
            })
            ->filter()
            ->values();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function restoreArchiveItem($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);
        ArchiveItem::where('report_id', $report->id)->delete();

        $report->update([
            'status' => $report->type === 'lost' ? 'pending_approval' : 'pending_surrender',
            'archived_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Item restored to its workflow queue.',
            'data' => $report->fresh('student'),
        ]);
    }

    public function publishMobileItem($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);
        $isVisible = ($report->type === 'lost' && $report->status === 'approved')
            || ($report->type === 'found' && $report->status === 'verified');

        if (!$isVisible) {
            return response()->json([
                'status' => 'error',
                'message' => 'This report must be approved or verified before it can be posted to mobile.',
            ], 422);
        }

        $report->update(['published_at' => Carbon::now()]);

        VisiblePostedItem::updateOrCreate(
            ['report_id' => $report->id],
            [
                'post_type' => 'public',
                'is_visible' => true,
                'posted_at' => Carbon::now(),
                'posted_by_admin_id' => $admin->id,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Item posted to mobile successfully.',
            'data' => $report->fresh('student'),
        ]);
    }

    public function toggleMobilePostVisibility(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'is_visible' => 'required|boolean',
        ]);

        $visibleItem = VisiblePostedItem::firstOrCreate(['report_id' => $id]);
        $visibleItem->update([
            'is_visible' => $validated['is_visible'],
            'posted_by_admin_id' => $admin->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $validated['is_visible'] ? 'Mobile post is now visible.' : 'Mobile post is now hidden.',
            'data' => $visibleItem->fresh(),
        ]);
    }

    public function archiveMobilePost($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);

        VisiblePostedItem::where('report_id', $report->id)->delete();

        $report->update([
            'status' => $report->type === 'lost' ? 'archived' : 'archived',
            'published_at' => null,
        ]);

        $archiveItem = ArchiveItem::updateOrCreate(
            ['report_id' => $report->id],
            [
                'status' => $report->status,
                'archive_type' => 'mobile_archive',
                'reason' => 'Archived from mobile posts by admin.',
                'archived_by_admin_id' => $admin->id,
                'archived_at' => Carbon::now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Item archived from mobile posts.',
            'data' => $archiveItem,
        ]);
    }

    public function indexAdminNotifications(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = Notification::query()
            ->where('recipient_role', 'admin')
            ->with('actorAdmin')
            ->latest()
            ->get()
            ->map(function (Notification $notification) {
                $links = [
                    'pending_surrender' => 'superadmin.pending-surrender',
                    'pending_report' => 'superadmin.pending-report',
                    'item_verification' => 'superadmin.item-verification',
                    'potential_match' => 'superadmin.matches',
                    'fine_request' => 'superadmin.fines',
                ];

                return array_merge($notification->toArray(), [
                    'notification_type' => $notification->type,
                    'link' => $links[$notification->type] ?? 'superadmin.notifications',
                ]);
            });

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'unread_count' => $items->where('is_read', false)->count(),
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

    public function indexItemVerifications(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $items = ItemVerification::query()
            ->where('status', '!=', 'archived')
            ->with(['report.student', 'report.images', 'report.category', 'verifierAdmin'])
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
            ->with(['lostReport.images', 'lostReport.student', 'foundReport.images', 'foundReport.student'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexItemCategories(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $categories = ItemCategory::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function indexSurrenderRequests(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $surrenderItems = SurrenderRequest::query()
            ->whereHas('report', function ($reportQuery) {
                $reportQuery->where('type', 'found')
                    ->whereIn('status', ['pending', 'pending_surrender', 'published', 'approved']);
            })
            ->whereNotIn('status', ['received', 'rejected'])
            ->with(['report.student', 'report.category', 'report.images', 'student', 'receivedByAdmin'])
            ->latest()
            ->get()
            ->map(function (SurrenderRequest $surrender) {
                $payload = $surrender->toArray();
                $payload['has_surrender_record'] = true;
                return $payload;
            })
            ->all();

        $reportItems = Report::query()
            ->where('type', 'found')
            ->whereIn('status', ['pending', 'pending_surrender', 'published', 'approved'])
            ->whereDoesntHave('surrenderRequest')
            ->with(['student', 'category', 'images'])
            ->latest()
            ->get()
            ->map(function (Report $report) {
                return [
                    'id' => $report->id,
                    'report_id' => $report->id,
                    'student_id' => $report->student_id,
                    'status' => $report->status,
                    'deadline_at' => $report->created_at?->copy()->addDays(3)->toDateTimeString(),
                    'received_at' => null,
                    'received_by_admin_id' => null,
                    'notes' => null,
                    'has_surrender_record' => false,
                    'report' => $report->toArray(),
                    'student' => $report->student?->toArray(),
                    'receivedByAdmin' => null,
                ];
            })
            ->all();

        $items = array_merge($surrenderItems, $reportItems);
        usort($items, function ($left, $right) {
            $leftDeadline = data_get($left, 'deadline_at');
            $rightDeadline = data_get($right, 'deadline_at');

            $leftTime = $leftDeadline ? strtotime($leftDeadline) : PHP_INT_MAX;
            $rightTime = $rightDeadline ? strtotime($rightDeadline) : PHP_INT_MAX;

            return $leftTime <=> $rightTime;
        });

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
            ->with(['student', 'event', 'report', 'issuedByAdmin'])
            ->latest()
            ->get();

        if ($request->boolean('archived')) {
            return response()->json([
                'status' => 'success',
                'data' => EventFineArchive::query()->with('student')->latest('paid_at')->get(),
            ]);
        }

        $items = $items->where('status', '!=', 'paid')->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function storeEventFine(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'event_fine_event_id' => 'required|exists:event_fine_events,id',
            'amount_to_pay' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:2000',
            'due_at' => 'nullable|date',
        ]);

        $event = EventFineEvent::findOrFail($validated['event_fine_event_id']);
        $student = Student::findOrFail($validated['student_id']);
        if ((float) $validated['amount_to_pay'] > (float) $event->fine_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'The amount to pay cannot be greater than the event fine.',
            ], 422);
        }

        $fine = EventFine::create([
            'student_id' => $validated['student_id'],
            'student_name' => $student->name,
            'school_id' => $student->student_id_number,
            'event_fine_event_id' => $event->id,
            'event_name' => $event->name,
            'amount' => $validated['amount_to_pay'],
            'original_amount' => $event->fine_amount,
            'amount_to_pay' => $validated['amount_to_pay'],
            'paid_amount' => 0,
            'paid' => 0,
            'remaining_amount' => $event->fine_amount,
            'reason' => $validated['reason'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'reference_number' => 'EF-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
            'status' => 'pending',
            'issued_by_admin_id' => $admin->id,
        ]);

        Notification::create([
            'type' => 'fine_request',
            'title' => 'New Fine Request',
            'message' => "A fine request for {$student->name} is waiting for review.",
            'action' => 'fine_request',
            'status' => 'pending',
            'recipient_role' => 'admin',
            'subject_type' => EventFine::class,
            'subject_id' => $fine->id,
            'is_read' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Event fine request created.',
            'data' => $fine->load('student'),
        ], 201);
    }

    public function indexEventFineEvents(Request $request)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => EventFineEvent::query()->where('is_active', true)->latest()->get(),
        ]);
    }

    public function storeEventFineEvent(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fine_amount' => 'required|numeric|min:0.01',
        ]);

        $event = EventFineEvent::firstOrCreate(
            ['name' => $validated['name']],
            [
                'fine_amount' => $validated['fine_amount'],
                'is_active' => true,
                'created_by_admin_id' => $admin->id,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $event->wasRecentlyCreated ? 'Event fine option created.' : 'Event name already exists. Existing event returned.',
            'data' => $event,
        ], $event->wasRecentlyCreated ? 201 : 200);
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

        $pendingSurrenderStatuses = ['pending', 'pending_surrender', 'published', 'approved'];
        $pendingSurrenders = Report::where('type', 'found')
            ->whereIn('status', $pendingSurrenderStatuses)
            ->where(function ($reportQuery) {
                $reportQuery->whereDoesntHave('surrenderRequest')
                    ->orWhereHas('surrenderRequest', function ($surrenderQuery) {
                        $surrenderQuery->whereNotIn('status', ['received', 'rejected']);
                    });
            });

        $summary = [
            'total_reports' => Report::count(),
            'open_reports' => Report::whereIn('status', ['pending', 'pending_approval', 'pending_surrender', 'pending_verification', 'correction_requested'])->count(),
            'pending_surrenders' => $pendingSurrenders->count(),
            'pending_reports' => Report::where('type', 'lost')->whereIn('status', ['pending', 'pending_approval', 'correction_requested'])->count(),
            'pending_verifications' => ItemVerification::where('status', 'pending')
                ->whereHas('report', function ($reportQuery) {
                    $reportQuery->where('type', 'found')
                        ->whereNotIn('status', ['rejected', 'completed', 'completed_return', 'archived', 'returned']);
                })
                ->count(),
            'mobile_posts' => VisiblePostedItem::whereHas('report')->count(),
            'potential_matches' => PotentialMatch::where('status', 'pending')->count(),
            'archived_items' => ArchiveItem::whereHas('report')->count(),
            'total_claims' => ClaimReview::count(),
            'total_releases' => ItemRelease::count(),
            'points_awarded' => PointTransaction::sum('points'),
        ];

        $recentActivity = [
            ...Report::with('student')->latest()->take(3)->get()->map(fn($report) => [
                'id' => $report->id,
                'type' => 'report',
                'label' => "Report '{$report->title}' ({$report->status})",
                'when' => $report->created_at?->toDateTimeString(),
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

    public function indexPointRules(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $pointRuleQuery = PointRule::query()
            ->with('updatedByAdmin');

        if (Schema::hasColumn('point_rules', 'category_id')) {
            $pointRuleQuery->with('category');
        }

        $items = $pointRuleQuery
            ->latest()
            ->get()
            ->map(function (PointRule $item) {
                $item->category_name = $item->category_name
                    ?: $item->item_category
                    ?: $item->category?->name;
                $item->item_category = $item->category_name;
                $item->description = $item->category?->description;
                $item->active = true;

                return $item;
            });

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function storePointRule(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'category_name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'base_points' => 'required|integer|min:0',
        ]);

        $pointRuleData = [
            'updated_by_admin_id' => $admin->id,
            'category_name' => $validated['category_name'],
            'base_points' => $validated['base_points'],
        ];

        if (Schema::hasColumn('point_rules', 'category_id')) {
            $category = ItemCategory::firstOrCreate(
                ['slug' => Str::slug($validated['category_name'])],
                [
                    'name' => $validated['category_name'],
                    'description' => $validated['description'] ?? null,
                    'is_active' => true,
                ]
            );

            if (array_key_exists('description', $validated)) {
                $category->update(['description' => $validated['description']]);
            }

            if (PointRule::where('category_id', $category->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A point rule already exists for this category.',
                ], 422);
            }

            $pointRuleData['category_id'] = $category->id;
        }

        if (Schema::hasColumn('point_rules', 'item_category')) {
            $pointRuleData['item_category'] = $validated['category_name'];
        }

        $item = PointRule::create($pointRuleData);

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
            'category_name' => 'sometimes|required|string|max:120',
            'description' => 'sometimes|nullable|string|max:1000',
            'base_points' => 'sometimes|required|integer|min:0',
        ]);

        $pointRuleData = [
            ...$validated,
            ...(array_key_exists('category_name', $validated)
                ? ['item_category' => $validated['category_name']]
                : []),
            'updated_by_admin_id' => $admin->id,
        ];

        if (!Schema::hasColumn('point_rules', 'item_category')) {
            unset($pointRuleData['item_category']);
        }

        if (array_key_exists('description', $validated) && $item->category) {
            $item->category->update(['description' => $validated['description']]);
        }

        $item->update($pointRuleData);

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
            ->withSum('pointTransactions as recognition_points', 'points')
            ->withCount('reports')
            ->latest()
            ->get();

        $records = $students->map(function (Student $student) {
            $recognitionPoints = (int) ($student->recognition_points ?? $student->point_transactions_sum_points ?? 0);

            return [
                'student_id' => $student->id,
                'recognition_points' => $recognitionPoints,
                'peso_value' => round($recognitionPoints / self::POINTS_PER_PESO, 2),
                'reward_count' => (int) ($student->reward_count ?? 0),
                'report_count' => (int) $student->reports_count,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        StudentRecognitionRecord::upsert(
            $records->all(),
            ['student_id'],
            ['recognition_points', 'peso_value', 'reward_count', 'report_count', 'updated_at']
        );

        $data = $students->map(function (Student $student) {
            $recognitionPoints = (int) ($student->recognition_points ?? $student->point_transactions_sum_points ?? 0);

            return [
                'id' => $student->id,
                'student_id_number' => $student->student_id_number,
                'profile_photo' => $student->profile_photo,
                'name' => $student->name,
                'email' => $student->email,
                'recognition_points' => $recognitionPoints,
                'peso_value' => round($recognitionPoints / self::POINTS_PER_PESO, 2),
                'report_count' => $student->reports_count,
                'reward_count' => (int) ($student->reward_count ?? 0),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function updatePointBalance(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'recognition_points' => 'required|integer|min:0',
            'reward_count' => 'sometimes|integer|min:0',
            'report_count' => 'sometimes|integer|min:0',
        ]);

        $student = Student::findOrFail($id);
        $currentPoints = (int) $student->pointTransactions()->sum('points');
        $targetPoints = (int) $validated['recognition_points'];
        $difference = $targetPoints - $currentPoints;

        DB::transaction(function () use ($student, $admin, $difference, $validated) {
            if ($difference > 0) {
                PointTransaction::create([
                    'student_id' => $student->id,
                    'type' => 'admin_adjustment',
                    'direction' => 'credit',
                    'points' => $difference,
                    'amount' => round($difference / self::POINTS_PER_PESO, 2),
                    'description' => 'Recognition points adjusted by administrator.',
                    'notes' => 'Admin points balance adjustment.',
                    'processed_by_admin_id' => $admin->id,
                    'created_by_admin_id' => $admin->id,
                ]);
            } elseif ($difference < 0) {
                $remaining = abs($difference);

                foreach ($student->pointTransactions()->where('points', '>', 0)->latest('id')->get() as $transaction) {
                    $decrease = min($remaining, (int) $transaction->points);
                    $transaction->decrement('points', $decrease);
                    $remaining -= $decrease;

                    if ($remaining === 0) {
                        break;
                    }
                }
            }

            if (array_key_exists('reward_count', $validated)) {
                $student->update(['reward_count' => $validated['reward_count']]);
            }
        });

        $student->refresh();
        StudentRecognitionRecord::updateOrCreate(
            ['student_id' => $student->id],
            [
                'recognition_points' => $targetPoints,
                'peso_value' => round($targetPoints / self::POINTS_PER_PESO, 2),
                'reward_count' => (int) ($student->reward_count ?? 0),
                'report_count' => $student->reports()->count(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Student points updated successfully.',
            'data' => [
                'id' => $student->id,
                'recognition_points' => $targetPoints,
                'peso_value' => round($targetPoints / self::POINTS_PER_PESO, 2),
                'reward_count' => (int) ($validated['reward_count'] ?? $student->reward_count ?? 0),
            ],
        ]);
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

    public function markAdminNotificationRead($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $notification = Notification::where('recipient_role', 'admin')->findOrFail($id);
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

    private function syncArchiveRecord(Report $report, string $archiveType = 'completed', ?string $reason = null): void
    {
        ArchiveItem::updateOrCreate(
            ['report_id' => $report->id],
            [
                'status' => $report->status,
                'archive_type' => $archiveType,
                'reason' => $reason,
                'archived_by_admin_id' => auth('admin')->id(),
                'archived_at' => Carbon::now(),
            ]
        );
    }

    public function approveLostReport($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);
        $report->update([
            'status' => 'approved',
            'published_at' => Carbon::now(),
        ]);

        VisiblePostedItem::updateOrCreate(
            ['report_id' => $report->id],
            [
                'post_type' => 'public',
                'is_visible' => true,
                'posted_at' => Carbon::now(),
                'posted_by_admin_id' => $admin->id,
            ]
        );

        return response()->json(['status' => 'success', 'message' => 'Report approved and posted to mobile successfully.', 'data' => $report->fresh('student')]);
    }

    public function rejectLostReport($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $report = Report::findOrFail($id);
        $report->update(['status' => 'rejected']);
        $this->syncArchiveRecord($report, 'rejected', 'Rejected by admin review.');

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

    private function resolveSurrenderRequest($id): SurrenderRequest
    {
        if (is_string($id) && str_starts_with($id, 'report:')) {
            $report = Report::findOrFail(substr($id, 7));

            return SurrenderRequest::firstOrCreate(
                ['report_id' => $report->id],
                [
                    'student_id' => $report->student_id,
                    'status' => 'pending',
                    'deadline_at' => $report->created_at?->copy()->addDays(3),
                ]
            );
        }

        $surrender = SurrenderRequest::find($id);

        if ($surrender) {
            return $surrender;
        }

        $report = Report::findOrFail($id);

        return SurrenderRequest::firstOrCreate(
            ['report_id' => $report->id],
            [
                'student_id' => $report->student_id,
                'status' => 'pending',
                'deadline_at' => $report->created_at?->copy()->addDays(3),
            ]
        );
    }

    public function receiveSurrenderRequest(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $surrender = SurrenderRequest::whereKey(
                    $this->resolveSurrenderRequest($id)->id
                )->lockForUpdate()->firstOrFail();

                if ($surrender->received_at || $surrender->received_by_admin_id) {
                    return response()->json(['status' => 'error', 'message' => 'This surrender request has already been received.'], 409);
                }

                $report = $surrender->report()->lockForUpdate()->firstOrFail();

                if ($report->type !== 'found' || in_array($report->status, ['rejected', 'completed', 'archived'], true)) {
                    return response()->json(['status' => 'error', 'message' => 'This report cannot be approved for mobile posting.'], 422);
                }

                $surrender->update([
                    'status' => 'approved',
                    'received_by_admin_id' => $admin->id,
                    'received_at' => Carbon::now(),
                ]);

                $report->update([
                    'status' => 'pending_verification',
                    'published_at' => Carbon::now(),
                ]);

                ItemVerification::firstOrCreate(
                    ['report_id' => $report->id],
                    ['status' => 'pending']
                );

                VisiblePostedItem::updateOrCreate(
                    ['report_id' => $report->id],
                    [
                        'post_type' => 'public',
                        'is_visible' => true,
                        'posted_at' => Carbon::now(),
                        'posted_by_admin_id' => $admin->id,
                    ]
                );

                Notification::create([
                    'student_id' => $report->student_id,
                    'title' => 'Found Item Approved',
                    'message' => "Your found item '{$report->title}' was approved and posted to the mobile app.",
                    'action' => 'item_approved',
                    'recipient_id' => $report->student_id,
                    'recipient_role' => 'student',
                    'subject_type' => SurrenderRequest::class,
                    'subject_id' => $surrender->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'surrender_approved', SurrenderRequest::class, $surrender->id, 'Surrender Request', [
                    'report_id' => $report->id,
                    'received_by_admin_id' => $admin->id,
                    'received_at' => $surrender->received_at?->toDateTimeString(),
                ], $request->ip());

                return response()->json([
                    'status' => 'success',
                    'message' => 'Surrender request approved and posted to mobile.',
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

        $surrender = $this->resolveSurrenderRequest($id);
        $surrender->update(['status' => 'rejected']);

        $report = $surrender->report()->first();
        if ($report) {
            $report->update(['status' => 'rejected']);
            $this->syncArchiveRecord($report, 'rejected', 'Rejected surrender request.');
        }

        return response()->json(['status' => 'success', 'message' => 'Surrender request rejected.', 'data' => $surrender]);
    }

    public function requestSurrenderDetails(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'requested_details' => 'nullable|array',
            'requested_details.*' => 'string|max:100',
            'note' => 'nullable|string|max:1000',
            'message' => 'nullable|string|max:1000',
        ]);

        $surrender = $this->resolveSurrenderRequest($id);
        $requestedDetails = $validated['requested_details'] ?? [];
        $message = $validated['note'] ?? $validated['message'] ?? '';
        if ($requestedDetails) {
            $message = trim($message . ($message ? "\n" : '') . 'Please provide: ' . implode(', ', $requestedDetails));
        }
        $message = $message ?: 'Please provide more details for this surrender request.';

        $surrender->update([
            'notes' => trim($surrender->notes ? $surrender->notes . "\n" : '') . $message,
        ]);

        $report = $surrender->report;
        if ($report?->student_id) {
            Notification::create([
                'student_id' => $report->student_id,
                'title' => 'More Details Needed',
                'message' => $message,
                'action' => 'surrender_details_requested',
                'recipient_id' => $report->student_id,
                'recipient_role' => 'student',
                'subject_type' => SurrenderRequest::class,
                'subject_id' => $surrender->id,
                'is_read' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'More details requested successfully.',
            'data' => $surrender->fresh(),
        ]);
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
                    'status' => 'archived',
                    'verifier_admin_id' => $admin->id,
                    'verified_at' => Carbon::now(),
                    'notes' => $validated['notes'],
                ]);

                $report->update(['status' => 'rejected']);
                $this->syncArchiveRecord($report, 'rejected', $validated['notes']);

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

    public function sendClaimInstructions(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $verification = ItemVerification::with('report')->findOrFail($id);
                $claim = ClaimReview::where('report_id', $verification->report_id)
                    ->whereNotNull('claimant_student_id')
                    ->latest()
                    ->first();
                $recipientStudentId = $claim?->claimant_student_id ?: $verification->report?->student_id;

                if (!$recipientStudentId) {
                    return response()->json(['status' => 'error', 'message' => 'No claimant student is associated with this verification.'], 422);
                }

                Notification::create([
                    'student_id' => $recipientStudentId,
                    'title' => 'Pickup Instructions',
                    'message' => $validated['message'],
                    'action' => 'claim_pickup_instructions',
                    'recipient_id' => $recipientStudentId,
                    'recipient_role' => 'student',
                    'subject_type' => $claim ? ClaimReview::class : ItemVerification::class,
                    'subject_id' => $claim?->id ?: $verification->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog($admin, 'claim_pickup_instructions_sent', $claim ? ClaimReview::class : ItemVerification::class, $claim?->id ?: $verification->id, $claim ? 'Claim Review' : 'Item Verification', [
                    'report_id' => $verification->report_id,
                    'claimant_student_id' => $recipientStudentId,
                    'message' => $validated['message'],
                ], $request->ip());

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pickup instruction sent to the claimant.',
                    'data' => $claim?->fresh(['report', 'claimantStudent']) ?: $verification->fresh('report'),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function completeVerificationTransaction(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $verification = ItemVerification::where('id', $id)
                    ->whereIn('status', ['verified', 'completed'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $report = $verification->report()->lockForUpdate()->firstOrFail();

                $verification->update([
                    'status' => 'completed',
                    'notes' => trim(($verification->notes ? $verification->notes . "\n" : '') . 'Transaction completed by admin.'),
                ]);
                $report->update(['status' => 'completed_return']);
                $this->syncArchiveRecord($report, 'completed', 'Claim transaction completed.');

                $this->recordAuditLog($admin, 'verification_transaction_completed', ItemVerification::class, $verification->id, 'Item Verification', [
                    'report_id' => $report->id,
                    'completed_at' => Carbon::now()->toDateTimeString(),
                ], $request->ip());

                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaction completed and archived.',
                    'data' => $verification->fresh(['report', 'verifierAdmin']),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage()], 422);
        }
    }

    public function archiveVerification(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $verification = ItemVerification::lockForUpdate()->findOrFail($id);
                $report = $verification->report()->lockForUpdate()->firstOrFail();

                $verification->update(['status' => 'archived']);
                $this->syncArchiveRecord($report, $report->status === 'rejected' ? 'rejected' : 'completed', 'Archived from claimant verification.');

                $this->recordAuditLog($admin, 'verification_archived', ItemVerification::class, $verification->id, 'Item Verification', [
                    'report_id' => $report->id,
                ], $request->ip());

                return response()->json([
                    'status' => 'success',
                    'message' => 'Verification archived successfully.',
                    'data' => $verification->fresh(['report', 'verifierAdmin']),
                ]);
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

        $match = PotentialMatch::with(['lostReport', 'foundReport'])->findOrFail($id);

        DB::transaction(function () use ($admin, $match) {
            $match->update([
                'status' => 'confirmed',
                'reviewed_by_admin_id' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($match->lostReport?->student_id) {
                Notification::create([
                    'student_id' => $match->lostReport->student_id,
                    'recipient_id' => $match->lostReport->student_id,
                    'recipient_role' => 'student',
                    'type' => 'lost_found_match',
                    'title' => 'Possible Match Found!',
                    'message' => 'A found item may match your lost report. Please review the details and confirm if this is your item.',
                    'action' => 'view_match',
                    'subject_type' => PotentialMatch::class,
                    'subject_id' => $match->id,
                    'data' => [
                        'potential_match_id' => $match->id,
                        'lost_report_id' => $match->lost_report_id,
                        'found_report_id' => $match->found_report_id,
                    ],
                    'is_read' => false,
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Potential match confirmed and student notified.', 'data' => $match->fresh(['lostReport', 'foundReport'])]);
    }

    public function rejectPotentialMatch($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $match = PotentialMatch::with('foundReport')->findOrFail($id);

        DB::transaction(function () use ($match) {
            $match->update(['status' => 'rejected']);

            if ($match->foundReport) {
                $this->syncArchiveRecord(
                    $match->foundReport,
                    'rejected',
                    'Potential match rejected by admin review.'
                );
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Potential match rejected and archived.', 'data' => $match->fresh(['foundReport'])]);
    }

    public function resetPotentialMatch($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $match = PotentialMatch::findOrFail($id);
        $match->update([
            'status' => 'pending',
            'reviewed_by_admin_id' => null,
            'reviewed_at' => null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Potential match returned to pending review.', 'data' => $match->fresh()]);
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

                $matchingLostReports = PotentialMatch::where('found_report_id', $report->id)
                    ->with('lostReport')
                    ->get();

                foreach ($matchingLostReports as $match) {
                    if ($match->lostReport && $match->lostReport->type === 'lost') {
                        $match->lostReport->update(['status' => 'under_claim_review']);
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
                            'amount' => round((int) ($pointRule?->base_points ?? 5) / self::POINTS_PER_PESO, 2),
                            'points' => (int) ($pointRule?->base_points ?? 5),
                            'type' => 'return_completion',
                            'description' => 'Recognition points awarded for completed return.',
                            'reference_type' => ItemRelease::class,
                            'reference_id' => $release->id,
                            'created_by_admin_id' => $admin->id,
                        ]);
                    }
                }

                $matchingLostReports = PotentialMatch::where('found_report_id', $report->id)
                    ->with('lostReport')
                    ->get();

                foreach ($matchingLostReports as $match) {
                    if ($match->lostReport && $match->lostReport->type === 'lost') {
                        $match->lostReport->update(['status' => 'completed_return']);
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
        $auditDetails = array_merge($details, [
            'module' => 'workflow',
        ]);
        $payload = [
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ip_address' => $ipAddress,
        ];

        if (Schema::hasColumn('audit_logs', 'actor_admin_id')) {
            $payload['actor_admin_id'] = $admin?->id;
        } elseif (Schema::hasColumn('audit_logs', 'actor_id')) {
            $payload['actor_id'] = $admin?->id;
            if (Schema::hasColumn('audit_logs', 'actor_type')) {
                $payload['actor_type'] = Admin::class;
            }
        }

        if (Schema::hasColumn('audit_logs', 'subject_label')) {
            $payload['subject_label'] = $subjectLabel;
        }

        if (Schema::hasColumn('audit_logs', 'details')) {
            $payload['details'] = $auditDetails;
        } elseif (Schema::hasColumn('audit_logs', 'description')) {
            $payload['description'] = $subjectLabel . ': ' . json_encode($auditDetails);
        }

        AuditLog::create($payload);
    }

    public function approveEventFineConversion($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $fine = EventFine::findOrFail($id);
        $fine->update(['status' => (float) $fine->remaining_amount <= 0 ? 'paid' : 'approved']);

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

    public function recordEventFinePayment(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate(['amount' => 'required|numeric|min:0.01']);
        $fine = EventFine::findOrFail($id);
        $payment = min((float) $validated['amount'], (float) $fine->remaining_amount);
        $paidAmount = (float) $fine->paid_amount + $payment;
        $remainingAmount = max(0, (float) $fine->original_amount - $paidAmount);

        $fine->update([
            'paid_amount' => $paidAmount,
            'paid' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $remainingAmount <= 0 ? 'paid' : 'partially paid',
        ]);

        if ($remainingAmount <= 0) {
            EventFineArchive::updateOrCreate(
                ['event_fine_id' => $fine->id],
                [
                    'student_id' => $fine->student_id,
                    'student_name' => $fine->student_name,
                    'school_id' => $fine->school_id,
                    'event_name' => $fine->event_name,
                    'original_amount' => $fine->original_amount,
                    'amount_to_pay' => $fine->amount_to_pay,
                    'paid_amount' => $fine->paid_amount,
                    'remaining_amount' => 0,
                    'status' => 'paid',
                    'reason' => $fine->reason,
                    'paid_at' => now(),
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Payment recorded.', 'data' => $fine->fresh()]);
    }
}
