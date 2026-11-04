<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAnnouncement;
use App\Models\AdminSetting;
use App\Models\ArchiveItem;
use App\Models\AuditLog;
use App\Models\ClaimReview;
use App\Models\EventFine;
use App\Models\EventFineArchive;
use App\Models\EventFineConversion;
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
use App\Models\VisiblePostedItem;
use App\Services\PotentialMatchService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        Storage::put(
            $relativePath,
            json_encode(
                $logs->map(fn ($log) => [
                    'id' => $log->id,
                    'date' => $log->created_at?->toDateTimeString(),
                    'user_name' => $log->actorAdmin?->name ?? 'System',
                    'action' => $log->action,
                    'subject_type' => $log->subject_type,
                    'subject_label' => $log->subject_label,
                    'details' => $log->details,
                    'ip_address' => $log->ip_address,
                ])->all(),
                JSON_PRETTY_PRINT
            )
        );

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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $query = Report::query()
            ->where('type', Report::TYPE_LOST)
            ->whereIn('status', [
                'pending',
                Report::STATUS_PENDING_APPROVAL,
                'correction_requested',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));

            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(location) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(category) LIKE ?', ["%{$search}%"]);
            });
        }

        $items = $query
            ->with(['student', 'images'])
            ->latest()
            ->get()
            ->map(function (Report $report) {
                $data = $report->toArray();
                $metadata = $report->metadata ?? [];
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?: [];
                }
                if (!is_array($metadata)) {
                    $metadata = [];
                }

                // Expose an explicit Time Lost key to the Vue page.
                // Do not substitute created_at (submission time).
                $candidates = [
                    $metadata['event_time'] ?? null,
                    $metadata['time_lost'] ?? null,
                    $report->getAttribute('time_lost'),
                    $report->getAttribute('time_occurred'),
                    // Legacy lost reports might have used time_found.
                    $report->getAttribute('time_found'),
                ];
                $data['time_lost'] = null;
                foreach ($candidates as $candidate) {
                    if (is_string($candidate) && trim($candidate) !== '') {
                        $data['time_lost'] = trim($candidate);
                        break;
                    }
                }

                return $data;
            });

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexMobilePostItems(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $items = ArchiveItem::query()
            ->with([
                'report.student',
                'report.images',
                'report.categoryRelation',
            ])
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

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function restoreArchiveItem($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        return DB::transaction(function () use ($id, $admin) {
            // Lock the report so an archive/restore request cannot race with
            // another workflow action. The archive record is required.
            $report = Report::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $archiveItem = ArchiveItem::query()
                ->where('report_id', $report->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Finalized item handovers are historical records, not restorable
            // workflow items. Reject direct API calls as well as hiding the
            // Restore control in AdminArchive.vue.
            if (
                $report->status === Report::STATUS_COMPLETED_RETURN ||
                $archiveItem->status === Report::STATUS_COMPLETED_RETURN
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Completed return records cannot be restored to an active workflow.',
                ], 422);
            }

            if ($archiveItem->archive_type === 'mobile_archive') {
                $metadata = $report->metadata ?? [];
                if (!is_array($metadata)) {
                    $metadata = [];
                }

                // New mobile archives save the actual published state here.
                // Older archives never recorded the previous visibility, so
                // use a safe, private fallback instead of guessing Public.
                $snapshot = $metadata['mobile_post_archive_snapshot'] ?? null;
                $hasSnapshot = is_array($snapshot);
                $restoredStatus = $hasSnapshot
                    ? ($snapshot['status'] ?? null)
                    : null;

                if (
                    !is_string($restoredStatus) ||
                    !Report::isValidStatusForType($report->type, $restoredStatus) ||
                    in_array($restoredStatus, [Report::STATUS_ARCHIVED, Report::STATUS_REJECTED, Report::STATUS_COMPLETED_RETURN], true)
                ) {
                    // A legacy mobile archive originated from a published
                    // Lost/Found post, but its previous status was discarded.
                    $restoredStatus = $report->type === Report::TYPE_FOUND
                        ? Report::STATUS_VERIFIED
                        : Report::STATUS_APPROVED;
                    $hasSnapshot = false;
                }

                unset($metadata['mobile_post_archive_snapshot']);
                $report->update([
                    'status' => $restoredStatus,
                    'archived_at' => null,
                    // Do not erase completed_at, verification or claim history.
                    'published_at' => $hasSnapshot
                        ? ($snapshot['published_at'] ?? ($report->published_at ?? Carbon::now()))
                        : ($report->published_at ?? Carbon::now()),
                    'metadata' => $metadata,
                ]);

                $canBePublic = $report->isPubliclyVisible();
                $restoreVisibility = $hasSnapshot
                    && !empty($snapshot['is_visible'])
                    && $canBePublic;

                // The Mobile Posts page reads VisiblePostedItem, not Reports.
                // Recreate the row that archiveMobilePost intentionally deleted.
                VisiblePostedItem::updateOrCreate(
                    ['report_id' => $report->id],
                    [
                        'post_type' => $hasSnapshot
                            ? ($snapshot['post_type'] ?? 'public')
                            : 'public',
                        'is_visible' => $restoreVisibility,
                        'posted_at' => $hasSnapshot
                            ? ($snapshot['posted_at'] ?? Carbon::now())
                            : Carbon::now(),
                        'posted_by_admin_id' => $hasSnapshot
                            ? ($snapshot['posted_by_admin_id'] ?? $admin->id)
                            : $admin->id,
                    ]
                );

                $archiveItem->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => $hasSnapshot
                        ? 'Mobile post restored with its previous visibility.'
                        : 'Mobile post restored as Private. Set it to Public in Mobile Posts if appropriate.',
                    'data' => $report->fresh('student'),
                    'restored_to' => 'mobile_posts',
                    'is_visible' => $restoreVisibility,
                ]);
            }

            // Keep the existing behavior for archives originating from other
            // workflows (completed, rejected and other non-mobile sources).
            $archiveItem->delete();
            $report->update([
                'status' => Report::initialStatusForType($report->type),
                'archived_at' => null,
                'completed_at' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Item restored to its workflow queue.',
                'data' => $report->fresh('student'),
            ]);
        });
    }

    public function publishMobileItem($id)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        return DB::transaction(function () use ($admin, $id) {
            // Lock this report to serialize any concurrent publishing attempts.
            $report = Report::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $eligible =
                ($report->type === Report::TYPE_LOST && $report->status === Report::STATUS_APPROVED) ||
                ($report->type === Report::TYPE_FOUND && $report->status === Report::STATUS_VERIFIED);

            if (!$eligible) {
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

            // Found reports receive the first installment only once.
            $award = $this->awardFoundPublicationRecognitionPoints($report, $admin);

            return response()->json([
                'status' => 'success',
                'message' => 'Item posted to mobile successfully.',
                'data' => $report->fresh('student'),
                'publication_reward' => $award,
            ]);
        });
    }

    public function toggleMobilePostVisibility(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'is_visible' => 'required|boolean',
        ]);

        $report = Report::findOrFail($id);

        if ($validated['is_visible'] && !$report->isPubliclyVisible()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This report is not in a public workflow status.',
            ], 422);
        }

        $visibleItem = VisiblePostedItem::firstOrCreate([
            'report_id' => $id,
        ]);

        $visibleItem->update([
            'is_visible' => $validated['is_visible'],
            'posted_by_admin_id' => $admin->id,
            'posted_at' => $visibleItem->posted_at ?? Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $validated['is_visible']
                ? 'Mobile post is now visible.'
                : 'Mobile post is now hidden.',
            'data' => $visibleItem->fresh(),
        ]);
    }

    public function archiveMobilePost($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        return DB::transaction(function () use ($id, $admin) {
            $report = Report::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $visibleItem = VisiblePostedItem::query()
                ->where('report_id', $report->id)
                ->lockForUpdate()
                ->first();

            if (!$visibleItem) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This report is no longer listed in Mobile Posts. Refresh the page.',
                ], 422);
            }

            // Keep the original status, publication date and visibility in
            // the existing reports.metadata JSON. No migration is required.
            // This lets Restore undo the mobile archive without restarting
            // Lost/Found approval, USC verification or claim workflows.
            $metadata = $report->metadata ?? [];
            if (!is_array($metadata)) {
                $metadata = [];
            }
            $metadata['mobile_post_archive_snapshot'] = [
                'status' => $report->status,
                'published_at' => $report->published_at?->toISOString(),
                'post_type' => $visibleItem->post_type ?: 'public',
                'is_visible' => (bool) $visibleItem->is_visible,
                'posted_at' => $visibleItem->posted_at
                    ? Carbon::parse($visibleItem->posted_at)->toISOString()
                    : null,
                'posted_by_admin_id' => $visibleItem->posted_by_admin_id,
            ];

            $archivedAt = Carbon::now();
            $report->update([
                'status' => Report::STATUS_ARCHIVED,
                'published_at' => null,
                'archived_at' => $archivedAt,
                'metadata' => $metadata,
            ]);

            $visibleItem->delete();

            $archiveItem = ArchiveItem::updateOrCreate(
                ['report_id' => $report->id],
                [
                    'status' => Report::STATUS_ARCHIVED,
                    'archive_type' => 'mobile_archive',
                    'reason' => 'Archived from mobile posts by admin.',
                    'archived_by_admin_id' => $admin->id,
                    'archived_at' => $archivedAt,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Item archived from mobile posts.',
                'data' => $archiveItem,
            ]);
        });
    }

    public function indexAdminNotifications(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
                    'potential_match_found' => 'superadmin.matches',
                    'claim_submitted' => 'superadmin.claims',
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $items = ItemVerification::query()
            ->where('status', '!=', 'archived')
            ->with([
                'report.student',
                'report.images',
                'report.categoryRelation',
                'verifierAdmin',
            ])
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $items = PotentialMatch::query()
            ->with([
                'lostReport.images',
                'lostReport.student',
                'foundReport.images',
                'foundReport.student',
            ])
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Read the actual found time from the report, when it was saved.
     * Never replace a missing found time with created_at or updated_at.
     */
    private function reportTimeFound(Report $report): ?string
    {
        $attributes = $report->getAttributes();
        $serialized = $report->toArray();
        $candidateFields = [
            'time_found',
            'event_time', // Older mobile reports saved metadata[event_time].
            'found_time',
            'time_occurred',
            'occurred_time',
            'found_at',
            'found_datetime',
            'date_time_found',
        ];

        foreach ($candidateFields as $field) {
            if (array_key_exists($field, $serialized)) {
                $value = $serialized[$field];
            } elseif (array_key_exists($field, $attributes)) {
                // Read original model attributes, including hidden fields.
                $value = $report->getAttribute($field);
            } else {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d\TH:i:sP');
            }

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        // Some report submissions save extra fields in JSON metadata.
        $metadata = $report->metadata;

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        if (is_array($metadata)) {
            foreach ($candidateFields as $field) {
                $value = data_get($metadata, $field);

                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d\TH:i:sP');
                }

                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        return null;
    }

    public function indexSurrenderRequests(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | EXISTING SURRENDER REQUESTS
        |--------------------------------------------------------------------------
        |
        | surrender_requests.status = pending / overdue
        | reports.status = pending_surrender
        |
        */

        $surrenderItems = SurrenderRequest::query()
            ->whereIn('status', [
                'pending',
                'overdue',
            ])
            ->whereHas('report', function ($reportQuery) {
                $reportQuery
                    ->where('type', Report::TYPE_FOUND)
                    ->where('status', Report::STATUS_PENDING_SURRENDER);
            })
            ->with([
                'report.student',
                'report.images',
                'report.categoryRelation',
                'student',
                'receivedByAdmin',
            ])
            ->latest()
            ->get()
            ->map(function (SurrenderRequest $surrender) {
                $payload = $surrender->toArray();
                $payload['has_surrender_record'] = true;

                if ($surrender->report) {
                    $payload['report']['category'] = $surrender->report->category;
                    $payload['report']['time_found'] = $this->reportTimeFound($surrender->report);
                }

                return $payload;
            });

        /*
        |--------------------------------------------------------------------------
        | FALLBACK FOUND REPORTS WITHOUT SURRENDER REQUEST
        |--------------------------------------------------------------------------
        */

        $reportItems = Report::query()
            ->where('type', Report::TYPE_FOUND)
            ->where('status', Report::STATUS_PENDING_SURRENDER)
            ->whereDoesntHave('surrenderRequest')
            ->with([
                'student',
                'images',
                'categoryRelation',
            ])
            ->latest()
            ->get()
            ->map(function (Report $report) {
                return [
                    'id' => $report->id,
                    'report_id' => $report->id,
                    'student_id' => $report->student_id,
                    'status' => 'pending',
                    'deadline_at' => $report->created_at?->copy()->addDays(3)->toDateTimeString(),
                    'received_at' => null,
                    'received_by_admin_id' => null,
                    'notes' => null,
                    'has_surrender_record' => false,
                    'report' => [
                        ...$report->toArray(),
                        'category' => $report->category,
                        'time_found' => $this->reportTimeFound($report),
                    ],
                    'student' => $report->student?->toArray(),
                    'receivedByAdmin' => null,
                ];
            });

        $items = $surrenderItems
            ->concat($reportItems)
            ->sortBy(function ($item) {
                $deadline = data_get($item, 'deadline_at');

                return $deadline
                    ? strtotime($deadline)
                    : PHP_INT_MAX;
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    public function indexEventFines(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($request->boolean('archived')) {
            return response()->json([
                'status' => 'success',
                'data' => EventFineArchive::query()
                    ->with('student')
                    ->latest('paid_at')
                    ->get(),
            ]);
        }

        $items = EventFine::query()
            ->with([
                'student',
                'event',
                'report',
                'issuedByAdmin',
            ])
            ->where('status', '!=', 'paid')
            ->latest()
            ->get();

        $grouped = $items
            ->groupBy(
                fn (EventFine $fine) =>
                    $fine->student_id
                    ?? $fine->student?->id
                    ?? $fine->school_id
                    ?? $fine->id
            )
            ->map(function ($group, $key) {
                $lead = $group->first();
                $student = $lead?->student;

                $originalAmount = (float) $group->sum(
                    fn ($fine) =>
                        (float) (
                            $fine->original_amount
                            ?? $fine->amount
                            ?? 0
                        )
                );

                $remainingAmount = (float) $group->sum(
                    fn ($fine) =>
                        (float) (
                            $fine->remaining_amount
                            ?? max(
                                0,
                                (float) (
                                    $fine->original_amount
                                    ?? $fine->amount
                                    ?? 0
                                )
                                -
                                (float) (
                                    $fine->paid_amount
                                    ?? 0
                                )
                            )
                        )
                );

                $paidAmount = (float) $group->sum(
                    fn ($fine) =>
                        (float) (
                            $fine->paid_amount
                            ?? 0
                        )
                );

                $status = strtolower(
                    (string) (
                        $lead->status
                        ?? 'pending'
                    )
                );

                if ($status === 'rejected') {
                    $status = 'rejected';
                } elseif ($remainingAmount <= 0) {
                    $status = 'paid';
                } elseif (
                    $group->contains(
                        fn ($fine) =>
                            strtolower(
                                (string) (
                                    $fine->status
                                    ?? ''
                                )
                            )
                            ===
                            'partially paid'
                    )
                ) {
                    $status = 'partially paid';
                } elseif (
                    $group->contains(
                        fn ($fine) =>
                            strtolower(
                                (string) (
                                    $fine->status
                                    ?? ''
                                )
                            )
                            ===
                            'approved'
                    )
                ) {
                    $status = 'approved';
                } else {
                    $status = 'pending';
                }

                /*
                |--------------------------------------------------------------------------
                | RECOGNITION POINT SUMMARY
                |--------------------------------------------------------------------------
                |
                | Credit transactions are earned points.
                | Debit transactions are stored as negative points.
                |
                */

                $totalEarnedPoints = 0;
                $pointsUsed = 0;
                $availablePoints = 0;

                if ($student) {
                    $transactions =
                        PointTransaction::query()
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->get([
                                'points',
                                'direction',
                            ]);

                    $totalEarnedPoints =
                        (int)
                        $transactions
                            ->filter(
                                fn ($transaction) =>
                                    strtolower(
                                        (string)
                                        $transaction->direction
                                    )
                                    ===
                                    'credit'
                                    &&
                                    (int)
                                    $transaction->points > 0
                            )
                            ->sum('points');

                    $pointsUsed =
                        (int)
                        $transactions
                            ->filter(
                                fn ($transaction) =>
                                    strtolower(
                                        (string)
                                        $transaction->direction
                                    )
                                    ===
                                    'debit'
                                    ||
                                    (int)
                                    $transaction->points < 0
                            )
                            ->sum(
                                fn ($transaction) =>
                                    abs(
                                        (int)
                                        $transaction->points
                                    )
                            );

                    $availablePoints =
                        max(
                            0,
                            (int)
                            $transactions->sum('points')
                        );
                }

                $latestConversion =
                    $student
                        ? EventFineConversion::query()
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->latest('id')
                            ->first()
                        : null;

                return [
                    'id' =>
                        $key,

                    'student_id' =>
                        $student?->id
                        ?? $lead?->student_id,

                    'student_name' =>
                        $student?->name
                        ?? $lead?->student_name,

                    'school_id' =>
                        $student?->student_id_number
                        ?? $lead?->school_id,

                    'event_name' =>
                        $group->count() > 1
                            ? 'Combined fine balance'
                            : (
                                $lead?->event_name
                                ?? 'Student fine balance'
                            ),

                    'amount' =>
                        round(
                            $originalAmount,
                            2
                        ),

                    'original_amount' =>
                        round(
                            $originalAmount,
                            2
                        ),

                    'amount_to_pay' =>
                        round(
                            $originalAmount,
                            2
                        ),

                    'paid_amount' =>
                        round(
                            $paidAmount,
                            2
                        ),

                    'remaining_amount' =>
                        round(
                            $remainingAmount,
                            2
                        ),

                    'status' =>
                        $status,

                    'student' =>
                        $student
                            ? $student->toArray()
                            : null,

                    'reason' =>
                        $lead?->reason,

                    'due_at' =>
                        $lead?->due_at,

                    'issued_by_admin_id' =>
                        $lead?->issued_by_admin_id,

                    /*
                    |--------------------------------------------------------------------------
                    | Revised SNSU event-fine conversion fields
                    |--------------------------------------------------------------------------
                    */

                    'total_recognition_points_earned' =>
                        $totalEarnedPoints,

                    'points_used' =>
                        $pointsUsed,

                    'remaining_available_points' =>
                        $availablePoints,

                    'conversion_rate' =>
                        self::POINTS_PER_PESO,

                    'latest_conversion' =>
                        $latestConversion
                            ? [
                                'id' =>
                                    $latestConversion->id,

                                'requested_points' =>
                                    (int)
                                    (
                                        $latestConversion
                                            ->requested_points
                                        ?? 0
                                    ),

                                'converted_points' =>
                                    (int)
                                    (
                                        $latestConversion
                                            ->converted_points
                                        ?? 0
                                    ),

                                'credit_amount' =>
                                    (float)
                                    (
                                        $latestConversion
                                            ->credit_amount
                                        ??
                                        $latestConversion
                                            ->peso_value_converted
                                        ??
                                        0
                                    ),

                                'remaining_balance' =>
                                    (float)
                                    (
                                        $latestConversion
                                            ->remaining_balance
                                        ?? 0
                                    ),

                                'status' =>
                                    $latestConversion->status,

                                'reviewed_by_admin_id' =>
                                    $latestConversion
                                        ->reviewed_by_admin_id,

                                'reviewed_at' =>
                                    $latestConversion
                                        ->reviewed_at,

                                'created_at' =>
                                    $latestConversion
                                        ->created_at,
                            ]
                            : null,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $grouped,
        ]);
    }


    /**
     * Assign an existing USC event fine to one student.
     * This creates an unpaid fine only; students later request point conversion.
     * The conversion approval workflow handles any point deduction.
     */
    public function storeEventFine(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'event_fine_event_id' => 'required|integer|exists:event_fine_events,id',
            'amount_to_pay' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'reason' => 'nullable|string|max:2000',
            'due_at' => 'nullable|date_format:Y-m-d',
        ]);

        return DB::transaction(function () use ($request, $admin, $validated) {
            // Lock the student for a consistent duplicate check across concurrent admins.
            $student = Student::query()->whereKey($validated['student_id'])
                ->lockForUpdate()->firstOrFail();

            // Archived events must never be used for NEW fine assignments.
            $event = EventFineEvent::query()
                ->whereKey($validated['event_fine_event_id'])
                ->where('is_active', true)
                ->first();

            if (!$event) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This USC event is no longer active. Please select another event.',
                ], 422);
            }

            $amount = round((float) $validated['amount_to_pay'], 2);
            if ($amount > (float) $event->fine_amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The assigned amount cannot exceed the event standard fine.',
                ], 422);
            }

            $alreadyAssigned = EventFine::query()
                ->where('student_id', $student->id)
                ->where('event_fine_event_id', $event->id)
                ->where('remaining_amount', '>', 0)
                ->whereNotIn('status', ['paid', 'rejected'])
                ->exists();

            if ($alreadyAssigned) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This student already has an unpaid fine for this event. Please review the existing fine instead of assigning it again.',
                ], 409);
            }

            // Populate the existing Laravel fine fields, including the legacy
            // due_date and created_by_admin_id fields if this project has them.
            // Use the database query builder so the assignment does not rely on
            // an old EventFine::$fillable list missing a newly added column.
            $columns = array_flip(Schema::getColumnListing('event_fines'));
            $candidate = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'school_id' => $student->student_id_number,
                'event_fine_event_id' => $event->id,
                'event_name' => $event->name,
                'description' => $validated['reason'] ?? null,
                'amount' => $amount,
                'original_amount' => $amount,
                'amount_to_pay' => $amount,
                'paid_amount' => 0,
                'paid' => 0,
                'remaining_amount' => $amount,
                'reason' => $validated['reason'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
                'due_date' => $validated['due_at'] ?? null,
                'reference_number' => 'EF-' . now()->format('ymd') . '-' . strtoupper(Str::random(10)),
                'status' => 'pending',
                'issued_by_admin_id' => $admin->id,
                'created_by_admin_id' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $fineId = DB::table('event_fines')->insertGetId(
                array_intersect_key($candidate, $columns)
            );
            $fine = EventFine::with('student')->findOrFail($fineId);

            // The student receives the new unpaid balance in their mobile app.
            // This notification belongs to the student, not the admin.
            Notification::create([
                'student_id' => $student->id,
                'recipient_id' => $student->id,
                'recipient_role' => 'student',
                'type' => 'event_fine_assigned',
                'title' => 'USC Event Fine Assigned',
                'message' => 'A ₱' . number_format($amount, 2) . " fine for '{$event->name}' was assigned to your account. Open Payment Requests to review it.",
                'action' => 'fine_request',
                'status' => 'pending',
                'subject_type' => EventFine::class,
                'subject_id' => $fineId,
                'data' => [
                    'event_fine_id' => $fineId,
                    'event_fine_event_id' => $event->id,
                    'event_name' => $event->name,
                    'fine_amount' => $amount,
                ],
                'is_read' => false,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Student fine assigned successfully.',
                'data' => $fine,
            ], 201);
        });
    }

    /**
     * Admin event catalog. Archived events remain in the database so historical
     * student fines and conversions never lose their original event data.
     */
    public function indexEventFineEvents(Request $request)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => DB::table('event_fine_events')
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function showEventFineEvent(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $event = DB::table('event_fine_events')->where('id', $id)->where('is_active', true)->first();
        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event not found.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $event]);
    }

    public function storeEventFineEvent(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'fine_amount' => 'required|numeric|min:0.01|max:9999999999.99',
            'event_date' => 'required|date_format:Y-m-d',
            'event_time' => 'required|date_format:H:i',
            'venue' => 'required|string|max:191',
        ]);
        $validated['name'] = trim($validated['name']);
        $validated['venue'] = trim($validated['venue']);
        if ($validated['name'] === '' || $validated['venue'] === '') {
            return response()->json(['status' => 'error', 'message' => 'Event name and venue cannot be blank.'], 422);
        }

        // Reactivate a previously archived event instead of creating a duplicate.
        // Preserve its ID so any existing related fine references remain valid.
        $existing = DB::table('event_fine_events')->where('name', $validated['name'])->first();
        if ($existing && $existing->is_active) {
            return response()->json(['status' => 'error', 'message' => 'An active event with this name already exists.'], 422);
        }

        $values = [
            'name' => $validated['name'],
            'fine_amount' => $validated['fine_amount'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'],
            'venue' => $validated['venue'],
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('event_fine_events')->where('id', $existing->id)->update($values);
            $id = $existing->id;
        } else {
            $id = DB::table('event_fine_events')->insertGetId(array_merge($values, [
                'created_by_admin_id' => $admin->id,
                'created_at' => now(),
            ]));
        }

        return response()->json([
            'status' => 'success',
            'message' => $existing ? 'Archived event restored.' : 'Event created successfully.',
            'data' => DB::table('event_fine_events')->where('id', $id)->first(),
        ], $existing ? 200 : 201);
    }

    public function updateEventFineEvent(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $event = DB::table('event_fine_events')->where('id', $id)->where('is_active', true)->first();
        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'fine_amount' => 'required|numeric|min:0.01|max:9999999999.99',
            'event_date' => 'required|date_format:Y-m-d',
            'event_time' => 'required|date_format:H:i',
            'venue' => 'required|string|max:191',
        ]);
        $validated['name'] = trim($validated['name']);
        $validated['venue'] = trim($validated['venue']);
        if ($validated['name'] === '' || $validated['venue'] === '') {
            return response()->json(['status' => 'error', 'message' => 'Event name and venue cannot be blank.'], 422);
        }

        $duplicate = DB::table('event_fine_events')
            ->where('name', $validated['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            return response()->json(['status' => 'error', 'message' => 'An event with this name already exists.'], 422);
        }

        DB::table('event_fine_events')->where('id', $id)->update([
            'name' => $validated['name'],
            'fine_amount' => $validated['fine_amount'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'],
            'venue' => $validated['venue'],
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Event updated successfully.',
            'data' => DB::table('event_fine_events')->where('id', $id)->first(),
        ]);
    }

    public function deleteEventFineEvent(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $event = DB::table('event_fine_events')->where('id', $id)->where('is_active', true)->first();
        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event not found.'], 404);
        }

        // Soft archive: preserve all student fine records linked to this event.
        DB::table('event_fine_events')->where('id', $id)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Event removed from the active list. Existing fines are preserved.',
        ]);
    }

    public function indexAdminSettings(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $pendingSurrenders = Report::query()
            ->where('type', Report::TYPE_FOUND)
            ->where('status', Report::STATUS_PENDING_SURRENDER)
            ->where(function ($reportQuery) {
                $reportQuery
                    ->whereDoesntHave('surrenderRequest')
                    ->orWhereHas('surrenderRequest', function ($surrenderQuery) {
                        $surrenderQuery->whereIn('status', [
                            'pending',
                            'overdue',
                        ]);
                    });
            });

        $summary = [
            'total_reports' => Report::count(),
            'open_reports' => Report::whereIn('status', [
                'pending',
                Report::STATUS_PENDING_APPROVAL,
                Report::STATUS_PENDING_SURRENDER,
                Report::STATUS_PENDING_VERIFICATION,
                'correction_requested',
                Report::STATUS_APPROVED,
                Report::STATUS_VERIFIED,
                Report::STATUS_POTENTIAL_MATCH_FOUND,
                Report::STATUS_UNDER_CLAIM_REVIEW,
                Report::STATUS_READY_FOR_RELEASE,
            ])->count(),
            'pending_surrenders' => $pendingSurrenders->count(),
            'pending_reports' => Report::where('type', Report::TYPE_LOST)
                ->whereIn('status', [
                    'pending',
                    Report::STATUS_PENDING_APPROVAL,
                    'correction_requested',
                ])
                ->count(),
            'pending_verifications' => ItemVerification::where('status', 'pending')
                ->whereHas('report', function ($reportQuery) {
                    $reportQuery
                        ->where('type', Report::TYPE_FOUND)
                        ->where('status', Report::STATUS_PENDING_VERIFICATION);
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
            ...Report::with('student')
                ->latest()
                ->take(3)
                ->get()
                ->map(fn ($report) => [
                    'id' => $report->id,
                    'type' => 'report',
                    'label' => "Report '{$report->title}' ({$report->status})",
                    'when' => $report->created_at?->toDateTimeString(),
                ])
                ->all(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => $summary,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    public function indexPointRules(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POINT RULES ARE THE ADMIN-OWNED ITEM CATEGORY SOURCE OF TRUTH
    |--------------------------------------------------------------------------
    | Keep item_categories in sync so category IDs are valid when students
    | submit reports and when admins use the All Categories dropdown.
    | Deactivating a removed category preserves links from older reports.
    */
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

        $name = trim($validated['category_name']);
        $slug = Str::slug($name);
        if ($slug === '') {
            return response()->json(['status' => 'error', 'message' => 'Use a valid category name.'], 422);
        }

        if (PointRule::query()->whereRaw('LOWER(category_name) = ?', [Str::lower($name)])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'A point rule already exists for this category.'], 422);
        }

        $existingCategory = ItemCategory::query()->where('slug', $slug)->first();
        if ($existingCategory && Schema::hasColumn('point_rules', 'category_id') &&
            PointRule::query()->where('category_id', $existingCategory->id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'A point rule already exists for this category.'], 422);
        }

        $item = DB::transaction(function () use ($validated, $name, $slug, $admin) {
            $category = ItemCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $validated['description'] ?? null, 'is_active' => true]
            );

            // Re-enable categories whose old point rule was deleted.
            $category->update([
                'name' => $name,
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            $attributes = [
                'category_name' => $name,
                'base_points' => $validated['base_points'],
                'updated_by_admin_id' => $admin->id,
            ];
            if (Schema::hasColumn('point_rules', 'category_id')) {
                $attributes['category_id'] = $category->id;
            }
            if (Schema::hasColumn('point_rules', 'item_category')) {
                $attributes['item_category'] = $name;
            }
            return PointRule::create($attributes);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Point rule and mobile category created.',
            'data' => $item,
        ], 201);
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

        $name = trim((string) ($validated['category_name'] ?? $item->category_name ?? $item->item_category));
        $slug = Str::slug($name);
        if (!$name || !$slug) {
            return response()->json(['status' => 'error', 'message' => 'Use a valid category name.'], 422);
        }

        if (PointRule::query()->where('id', '!=', $item->id)
            ->whereRaw('LOWER(category_name) = ?', [Str::lower($name)])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Another point rule already uses this category.'], 422);
        }

        $existingCategory = ItemCategory::query()->where('slug', $slug)->first();
        if ($existingCategory && $item->category_id != $existingCategory->id &&
            Schema::hasColumn('point_rules', 'category_id') &&
            PointRule::query()->where('id', '!=', $item->id)
                ->where('category_id', $existingCategory->id)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Another point rule already uses this category.'], 422);
        }

        $item = DB::transaction(function () use ($item, $validated, $name, $slug, $admin, $existingCategory) {
            $category = $item->category;
            if (!$category) {
                $category = ItemCategory::query()->where('slug', Str::slug((string) ($item->category_name ?: $item->item_category)))->first();
            }

            if ($category && $existingCategory && $category->id !== $existingCategory->id) {
                // Do not rewrite a different category's existing historical records.
                $category->update(['is_active' => false]);
                $category = $existingCategory;
            }

            if (!$category) {
                $category = ItemCategory::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'description' => $validated['description'] ?? null, 'is_active' => true]
                );
            }

            $categoryUpdates = ['name' => $name, 'slug' => $slug, 'is_active' => true];
            if (array_key_exists('description', $validated)) {
                $categoryUpdates['description'] = $validated['description'];
            }
            $category->update($categoryUpdates);

            $attributes = ['category_name' => $name, 'updated_by_admin_id' => $admin->id];
            if (array_key_exists('base_points', $validated)) {
                $attributes['base_points'] = $validated['base_points'];
            }
            if (Schema::hasColumn('point_rules', 'category_id')) {
                $attributes['category_id'] = $category->id;
            }
            if (Schema::hasColumn('point_rules', 'item_category')) {
                $attributes['item_category'] = $name;
            }
            $item->update($attributes);
            return $item->fresh();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Point rule and mobile category updated.',
            'data' => $item,
        ]);
    }

    public function destroyPointRule($id)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        DB::transaction(function () use ($id) {
            $item = PointRule::findOrFail($id);
            $category = $item->category;
            if (!$category) {
                $category = ItemCategory::query()->where('slug', Str::slug((string) ($item->category_name ?: $item->item_category)))->first();
            }
            $item->delete();

            if ($category) {
                $stillInUse = PointRule::query()->whereRaw('LOWER(category_name) = ?', [Str::lower($category->name)])->exists();
                if (Schema::hasColumn('point_rules', 'category_id')) {
                    $stillInUse = $stillInUse || PointRule::query()->where('category_id', $category->id)->exists();
                }
                if (!$stillInUse) {
                    // Preserve category IDs referenced by historical reports.
                    $category->update(['is_active' => false]);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Point rule deleted. Category removed from future report choices.',
        ]);
    }

    public function indexPointBalances(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
                'student_id' => $student->student_id ?? $student->student_id_number,
                'profile_photo' => $student->profile_photo,
                'name' => $student->name,
                'email' => $student->email,
                'course' => $student->course,
                'department' => $student->department,
                'year_level' => $student->year_level,
                'status' => $student->status,
                'recognition_points' => $recognitionPoints,
                'peso_value' => round($recognitionPoints / self::POINTS_PER_PESO, 2),
                'report_count' => $student->reports_count,
                'reward_count' => (int) ($student->reward_count ?? 0),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function updatePointBalance(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
                $student->update([
                    'reward_count' => $validated['reward_count'],
                ]);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $items = ClaimReview::query()
            ->with([
                'report.student',
                'report.images',
                'report.categoryRelation',
                'claimantStudent',
                'reviewedByAdmin',
            ])
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $notification = Notification::where('recipient_role', 'admin')->findOrFail($id);
        $notification->update([
            'is_read' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
            'data' => $notification,
        ]);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
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

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement updated successfully.',
            'data' => $item,
        ]);
    }

    public function deleteAnnouncement($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement deleted successfully.',
        ]);
    }

    public function publishAnnouncement($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $item->update([
            'status' => 'published',
            'published_at' => $item->published_at ?? Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement published successfully.',
            'data' => $item,
        ]);
    }

    public function unpublishAnnouncement($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $item = AdminAnnouncement::findOrFail($id);
        $item->update([
            'status' => 'draft',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement unpublished successfully.',
            'data' => $item,
        ]);
    }

    private function syncArchiveRecord(
        Report $report,
        string $archiveType = 'completed',
        ?string $reason = null
    ): void {
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $report = Report::findOrFail($id);

        if ($report->type !== Report::TYPE_LOST) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only lost-item reports can be approved here.',
            ], 422);
        }

        if (
            !in_array(
                $report->status,
                [
                    Report::STATUS_PENDING_APPROVAL,
                    'correction_requested',
                ],
                true
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This lost-item report is not waiting for approval.',
            ], 422);
        }

        $metadata = $report->metadata ?? [];

        unset(
            $metadata['correction_requested'],
            $metadata['correction_request'],
            $metadata['correction_requested_at'],
            $metadata['correction_requested_by_admin_id'],
            $metadata['correction_resubmitted_at']
        );

        DB::transaction(function () use (
            $report,
            $admin,
            $metadata
        ) {
            $report->update([
                'status' => Report::STATUS_APPROVED,
                'approved_by_admin_id' => $admin->id,
                'approved_at' => Carbon::now(),
                'rejected_by_admin_id' => null,
                'rejected_at' => null,
                'published_at' => Carbon::now(),
                'metadata' => $metadata,
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

            ArchiveItem::where(
                'report_id',
                $report->id
            )->delete();

            $this->createStudentNotification([
                'student_id' => $report->student_id,
                'type' => 'lost_report_approved',
                'title' => 'Lost Report Approved',
                'message' =>
                    "Your lost-item report '{$report->title}' was approved and is now visible in the student feed.",
                'action' => 'lost_report_approved',
                'status' => 'pending',
                'recipient_id' => $report->student_id,
                'recipient_role' => 'student',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'data' => [
                    'report_id' => $report->id,
                ],
                'is_read' => false,
            ]);

            /*
            |--------------------------------------------------------------------------
            | AUTOMATIC LOST/FOUND MATCH SCAN
            |--------------------------------------------------------------------------
            */

            $this->generatePotentialMatchesForReport(
                $report,
                $admin
            );
        });

        return response()->json([
            'status' => 'success',
            'message' =>
                'Report approved and posted to mobile successfully.',
            'data' => $report->fresh([
                'student',
                'images',
                'visiblePost',
            ]),
        ]);
    }

    public function rejectLostReport($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $report = Report::findOrFail($id);

        if ($report->type !== Report::TYPE_LOST) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only lost-item reports can be rejected here.',
            ], 422);
        }

        if (
            !in_array(
                $report->status,
                [
                    Report::STATUS_PENDING_APPROVAL,
                    'correction_requested',
                ],
                true
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This lost-item report is not waiting for USC review.',
            ], 422);
        }

        DB::transaction(function () use (
            $report,
            $admin
        ) {
            $report->update([
                'status' => Report::STATUS_REJECTED,
                'rejected_by_admin_id' => $admin->id,
                'rejected_at' => Carbon::now(),
                'published_at' => null,
            ]);

            VisiblePostedItem::where(
                'report_id',
                $report->id
            )->delete();

            $this->syncArchiveRecord(
                $report,
                'rejected',
                'Rejected by admin review.'
            );

            $this->createStudentNotification([
                'student_id' => $report->student_id,
                'type' => 'lost_report_rejected',
                'title' => 'Lost Report Rejected',
                'message' =>
                    "Your lost-item report '{$report->title}' was rejected by USC.",
                'action' => 'lost_report_rejected',
                'status' => 'pending',
                'recipient_id' => $report->student_id,
                'recipient_role' => 'student',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'data' => [
                    'report_id' => $report->id,
                ],
                'is_read' => false,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Report rejected successfully.',
            'data' => $report->fresh(),
        ]);
    }

    public function requestLostReportCorrection(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $report = Report::findOrFail($id);

        if ($report->type !== Report::TYPE_LOST) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Only lost-item reports can receive correction requests here.',
            ], 422);
        }

        if (
            !in_array(
                $report->status,
                [
                    Report::STATUS_PENDING_APPROVAL,
                    'correction_requested',
                ],
                true
            )
        ) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This lost-item report is not waiting for USC review.',
            ], 422);
        }

        $message = trim(
            $validated['message']
        );

        $metadata = array_merge(
            $report->metadata ?? [],
            [
                'correction_requested' => true,
                'correction_request' => $message,
                'correction_requested_at' =>
                    now()->toIso8601String(),
                'correction_requested_by_admin_id' =>
                    $admin->id,
            ]
        );

        DB::transaction(function () use (
            $report,
            $message,
            $metadata
        ) {
            $report->update([
                'status' => Report::STATUS_PENDING_APPROVAL,
                'metadata' => $metadata,
                'published_at' => null,
            ]);

            VisiblePostedItem::where(
                'report_id',
                $report->id
            )->delete();

            $this->createStudentNotification([
                'student_id' => $report->student_id,
                'type' => 'lost_report_correction_requested',
                'title' => 'Lost Report Needs More Information',
                'message' =>
                    "USC requested an update to your lost-item report '{$report->title}': {$message}",
                'action' => 'lost_report_correction_requested',
                'status' => 'pending',
                'recipient_id' => $report->student_id,
                'recipient_role' => 'student',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'data' => [
                    'report_id' => $report->id,
                    'correction_request' => $message,
                ],
                'is_read' => false,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' =>
                'Correction request sent to the student successfully.',
            'data' => $report->fresh([
                'student',
                'images',
            ]),
        ]);
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $resolved = $this->resolveSurrenderRequest($id);

                $surrender = SurrenderRequest::query()
                    ->whereKey($resolved->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($surrender->received_at || $surrender->received_by_admin_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This surrender request has already been received.',
                    ], 409);
                }

                $report = $surrender
                    ->report()
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($report->type !== Report::TYPE_FOUND) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Only found-item reports can be processed in the surrender queue.',
                    ], 422);
                }

                if ($report->status !== Report::STATUS_PENDING_SURRENDER) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This found item is no longer waiting for surrender.',
                    ], 422);
                }

                $surrender->update([
                    'status' => 'received',
                    'received_by_admin_id' => $admin->id,
                    'received_at' => Carbon::now(),
                ]);

                $report->update([
                    'status' => Report::STATUS_PENDING_VERIFICATION,
                    'published_at' => null,
                ]);

                $verification = ItemVerification::firstOrCreate(
                    ['report_id' => $report->id],
                    [
                        'status' => 'pending',
                        'notes' => 'Physical found item received by USC and waiting for verification.',
                    ]
                );

                if ($verification->status !== 'pending') {
                    $verification->update([
                        'status' => 'pending',
                        'verifier_admin_id' => null,
                        'verified_at' => null,
                    ]);
                }

                /*
                |------------------------------------------------------------------
                | IMPORTANT
                |------------------------------------------------------------------
                | A surrendered item is NOT public yet. It must be verified first.
                */

                VisiblePostedItem::where('report_id', $report->id)->delete();

                $this->createStudentNotification([
                    'student_id' => $report->student_id,
                    'type' => 'found_item_received',
                    'title' => 'Found Item Received by USC',
                    'message' => "USC received your found item '{$report->title}'. It is now waiting for verification.",
                    'action' => 'pending_verification',
                    'status' => 'pending',
                    'recipient_id' => $report->student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ItemVerification::class,
                    'subject_id' => $verification->id,
                    'data' => [
                        'report_id' => $report->id,
                        'verification_id' => $verification->id,
                    ],
                    'is_read' => false,
                ]);

                Notification::create([
                    'type' => 'item_verification',
                    'title' => 'Found Item Ready for Verification',
                    'message' => "The found item '{$report->title}' has been surrendered and is ready for verification.",
                    'action' => 'item_verification',
                    'status' => 'pending',
                    'recipient_role' => 'admin',
                    'subject_type' => ItemVerification::class,
                    'subject_id' => $verification->id,
                    'data' => [
                        'report_id' => $report->id,
                        'verification_id' => $verification->id,
                    ],
                    'is_read' => false,
                ]);

                $this->recordAuditLog(
                    $admin,
                    'surrender_received',
                    SurrenderRequest::class,
                    $surrender->id,
                    'Surrender Request',
                    [
                        'report_id' => $report->id,
                        'received_by_admin_id' => $admin->id,
                        'received_at' => $surrender->received_at?->toDateTimeString(),
                        'next_status' => Report::STATUS_PENDING_VERIFICATION,
                    ],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Found item received successfully. It has been moved to Pending Verification.',
                    'data' => $surrender->fresh([
                        'report.student',
                        'report.images',
                        'student',
                        'receivedByAdmin',
                    ]),
                ]);
            });
        } catch (\Throwable $exception) {
            Log::error('Receive surrender failed', [
                'surrender_id' => $id,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function rejectSurrenderRequest($id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            return DB::transaction(
                function () use (
                    $id,
                    $admin
                ) {
                    $resolved =
                        $this->resolveSurrenderRequest(
                            $id
                        );

                    $surrender =
                        SurrenderRequest::query()
                            ->whereKey(
                                $resolved->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $surrender
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                        Report::TYPE_FOUND
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'Only found-item surrender requests can be rejected here.',
                        ], 422);
                    }

                    if (
                        $report->status !==
                        Report::STATUS_PENDING_SURRENDER
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found item is no longer waiting for surrender.',
                        ], 422);
                    }

                    if (
                        $surrender->received_at ||
                        $surrender->received_by_admin_id ||
                        $surrender->status === 'received'
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'A received physical item cannot be rejected from the surrender queue.',
                        ], 409);
                    }

                    $surrender->update([
                        'status' => 'rejected',
                    ]);

                    $report->update([
                        'status' =>
                            Report::STATUS_REJECTED,
                        'rejected_by_admin_id' =>
                            $admin->id,
                        'rejected_at' =>
                            Carbon::now(),
                        'published_at' =>
                            null,
                    ]);

                    VisiblePostedItem::where(
                        'report_id',
                        $report->id
                    )->delete();

                    $this->syncArchiveRecord(
                        $report,
                        'rejected',
                        'Rejected surrender request.'
                    );

                    $this->createStudentNotification([
                        'student_id' =>
                            $report->student_id,
                        'type' =>
                            'found_surrender_rejected',
                        'title' =>
                            'Found Item Surrender Rejected',
                        'message' =>
                            "USC rejected the surrender request for '{$report->title}'.",
                        'action' =>
                            'found_surrender_rejected',
                        'status' =>
                            'pending',
                        'recipient_id' =>
                            $report->student_id,
                        'recipient_role' =>
                            'student',
                        'subject_type' =>
                            SurrenderRequest::class,
                        'subject_id' =>
                            $surrender->id,
                        'data' => [
                            'report_id' =>
                                $report->id,
                        ],
                        'is_read' =>
                            false,
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' =>
                            'Surrender request rejected.',
                        'data' =>
                            $surrender->fresh([
                                'report.student',
                                'report.images',
                                'student',
                            ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Reject surrender failed',
                [
                    'surrender_id' => $id,
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function requestSurrenderDetails(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated =
            $request->validate([
                'requested_details' =>
                    'nullable|array',

                'requested_details.*' =>
                    'string|max:100',

                'note' =>
                    'nullable|string|max:1000',

                'message' =>
                    'nullable|string|max:1000',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $admin,
                    $validated
                ) {
                    $resolved =
                        $this->resolveSurrenderRequest(
                            $id
                        );

                    $surrender =
                        SurrenderRequest::query()
                            ->whereKey(
                                $resolved->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $surrender
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                        Report::TYPE_FOUND ||
                        $report->status !==
                            Report::STATUS_PENDING_SURRENDER
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found item is no longer waiting for surrender details.',
                        ], 422);
                    }

                    if (
                        $surrender->received_at ||
                        $surrender->received_by_admin_id ||
                        $surrender->status === 'received'
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This physical item has already been received by USC.',
                        ], 409);
                    }

                    $requestedDetails =
                        $validated[
                            'requested_details'
                        ]
                        ?? [];

                    $message =
                        $validated['note']
                        ??
                        $validated['message']
                        ??
                        '';

                    if ($requestedDetails) {
                        $message =
                            trim(
                                $message
                                .
                                ($message ? "\n" : '')
                                .
                                'Please provide: '
                                .
                                implode(
                                    ', ',
                                    $requestedDetails
                                )
                            );
                    }

                    $message =
                        $message
                        ?:
                        'Please provide more details for this surrender request.';

                    $surrender->update([
                        'notes' =>
                            trim(
                                (
                                    $surrender->notes
                                        ? $surrender->notes
                                            . "\n"
                                        : ''
                                )
                                .
                                $message
                            ),
                    ]);

                    $metadata =
                        array_merge(
                            $report->metadata
                                ?? [],
                            [
                                'surrender_details_requested' =>
                                    true,

                                'surrender_details_request' =>
                                    $message,

                                'surrender_details_requested_at' =>
                                    now()
                                        ->toIso8601String(),

                                'surrender_details_requested_by_admin_id' =>
                                    $admin->id,
                            ]
                        );

                    $report->update([
                        'metadata' =>
                            $metadata,
                    ]);

                    $this->createStudentNotification([
                        'student_id' =>
                            $report->student_id,

                        'type' =>
                            'surrender_details_requested',

                        'title' =>
                            'More Found Item Details Needed',

                        'message' =>
                            $message,

                        'action' =>
                            'surrender_details_requested',

                        'status' =>
                            'pending',

                        'recipient_id' =>
                            $report->student_id,

                        'recipient_role' =>
                            'student',

                        'subject_type' =>
                            SurrenderRequest::class,

                        'subject_id' =>
                            $surrender->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'requested_details' =>
                                $requestedDetails,

                            'message' =>
                                $message,
                        ],

                        'is_read' =>
                            false,
                    ]);

                    $this->recordAuditLog(
                        $admin,
                        'surrender_details_requested',
                        SurrenderRequest::class,
                        $surrender->id,
                        'Surrender Request',
                        [
                            'report_id' =>
                                $report->id,
                            'requested_details' =>
                                $requestedDetails,
                            'message' =>
                                $message,
                        ],
                        $request->ip()
                    );

                    return response()->json([
                        'status' => 'success',
                        'message' =>
                            'More details requested successfully.',
                        'data' =>
                            $surrender->fresh([
                                'report.student',
                                'report.images',
                                'student',
                            ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Request surrender details failed',
                [
                    'surrender_id' =>
                        $id,

                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function markSurrenderOverdue(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $admin
                ) {
                    $resolved =
                        $this->resolveSurrenderRequest(
                            $id
                        );

                    $surrender =
                        SurrenderRequest::query()
                            ->whereKey(
                                $resolved->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $surrender
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                        Report::TYPE_FOUND ||
                        $report->status !==
                            Report::STATUS_PENDING_SURRENDER
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found item is no longer waiting for surrender.',
                        ], 422);
                    }

                    if (
                        $surrender->received_at ||
                        $surrender->received_by_admin_id ||
                        $surrender->status === 'received'
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'A received physical item cannot be marked overdue.',
                        ], 409);
                    }

                    $surrender->update([
                        'status' =>
                            'overdue',
                    ]);

                    $this->createStudentNotification([
                        'student_id' =>
                            $report->student_id,

                        'type' =>
                            'surrender_overdue',

                        'title' =>
                            'Found Item Surrender Overdue',

                        'message' =>
                            "Your found item '{$report->title}' is overdue for surrender. Please bring the physical item to the USC office.",

                        'action' =>
                            'surrender_overdue',

                        'status' =>
                            'pending',

                        'recipient_id' =>
                            $report->student_id,

                        'recipient_role' =>
                            'student',

                        'subject_type' =>
                            SurrenderRequest::class,

                        'subject_id' =>
                            $surrender->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'deadline_at' =>
                                $surrender
                                    ->deadline_at,
                        ],

                        'is_read' =>
                            false,
                    ]);

                    $this->recordAuditLog(
                        $admin,
                        'surrender_marked_overdue',
                        SurrenderRequest::class,
                        $surrender->id,
                        'Surrender Request',
                        [
                            'report_id' =>
                                $report->id,
                            'deadline_at' =>
                                $surrender
                                    ->deadline_at,
                        ],
                        $request->ip()
                    );

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Surrender request marked overdue.',

                        'data' =>
                            $surrender->fresh([
                                'report',
                                'student',
                            ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function verifyItem(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated =
            $request->validate([
                'notes' =>
                    'nullable|string|max:2000',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $admin,
                    $validated
                ) {
                    $verification =
                        ItemVerification::query()
                            ->where(
                                'id',
                                $id
                            )
                            ->whereIn(
                                'status',
                                [
                                    'pending',
                                    'needs_more_information',
                                ]
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $verification
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                        Report::TYPE_FOUND
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'Only found items are verified in this workflow.',
                        ], 422);
                    }

                    if (
                        $report->status !==
                        Report::STATUS_PENDING_VERIFICATION
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found report is not ready for verification approval.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PHYSICAL ITEM MUST HAVE BEEN RECEIVED FIRST
                    |--------------------------------------------------------------------------
                    */

                    $surrender =
                        SurrenderRequest::query()
                            ->where(
                                'report_id',
                                $report->id
                            )
                            ->first();

                    if (
                        !$surrender ||
                        $surrender->status !== 'received' ||
                        !$surrender->received_at
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'The physical found item must be received by USC before verification can be completed.',
                        ], 422);
                    }

                    $notes =
                        isset($validated['notes'])
                            ? trim(
                                (string)
                                $validated['notes']
                            )
                            : null;

                    $verification->update([
                        'status' =>
                            'verified',

                        'verifier_admin_id' =>
                            $admin->id,

                        'verified_at' =>
                            Carbon::now(),

                        'notes' =>
                            $notes !== ''
                                ? $notes
                                : $verification->notes,

                        'checklist' =>
                            array_merge(
                                $verification
                                    ->checklist
                                    ?? [],
                                [
                                    'physical_item_received' =>
                                        true,

                                    'physical_item_received_at' =>
                                        $surrender
                                            ->received_at,

                                    'verified_by_admin' =>
                                        $admin->id,
                                ]
                            ),
                    ]);

                    $report->update([
                        'status' =>
                            Report::STATUS_VERIFIED,

                        'published_at' =>
                            Carbon::now(),

                        'rejected_by_admin_id' =>
                            null,

                        'rejected_at' =>
                            null,
                    ]);

                    ArchiveItem::where(
                        'report_id',
                        $report->id
                    )->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | VERIFIED FOUND ITEM IS NOW ELIGIBLE FOR THE MOBILE FEED
                    |--------------------------------------------------------------------------
                    */

                    VisiblePostedItem::updateOrCreate(
                        [
                            'report_id' =>
                                $report->id,
                        ],
                        [
                            'post_type' =>
                                'public',

                            'is_visible' =>
                                true,

                            'posted_at' =>
                                Carbon::now(),

                            'posted_by_admin_id' =>
                                $admin->id,
                        ]
                    );

                    // USC has verified and automatically published the found item.
                    // Award the first installment now, never on student submission.
                    $publicationAward = $this->awardFoundPublicationRecognitionPoints($report, $admin);

                    $this->createStudentNotification([
                        'student_id' =>
                            $report->student_id,

                        'type' =>
                            'found_item_verified',

                        'title' =>
                            'Found Item Verified by USC',

                        'message' =>
                            "USC verified your found item '{$report->title}'. It is now visible in the student feed.",

                        'action' =>
                            'item_verified',

                        'status' =>
                            'pending',

                        'recipient_id' =>
                            $report->student_id,

                        'recipient_role' =>
                            'student',

                        'subject_type' =>
                            ItemVerification::class,

                        'subject_id' =>
                            $verification->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'verification_id' =>
                                $verification->id,
                        ],

                        'is_read' =>
                            false,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | AUTOMATIC LOST/FOUND MATCH SCAN
                    |--------------------------------------------------------------------------
                    */

                    $this->generatePotentialMatchesForReport(
                        $report,
                        $admin
                    );

                    $this->recordAuditLog(
                        $admin,
                        'verification_approved',
                        ItemVerification::class,
                        $verification->id,
                        'Item Verification',
                        [
                            'report_id' =>
                                $report->id,

                            'verifier_admin_id' =>
                                $admin->id,

                            'verified_at' =>
                                $verification
                                    ->verified_at
                                    ?->toDateTimeString(),

                            'mobile_visible' =>
                                true,
                        ],
                        $request->ip()
                    );

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Item verification approved. The found item is now visible in the student feed.',

                        'data' =>
                            $verification
                                ->fresh([
                                    'report.student',
                                    'report.images',
                                    'report.visiblePost',
                                    'verifierAdmin',
                                ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Verify item failed',
                [
                    'verification_id' =>
                        $id,

                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function rejectVerification(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated =
            $request->validate([
                'notes' =>
                    'required|string|max:1000',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $admin,
                    $validated
                ) {
                    $verification =
                        ItemVerification::query()
                            ->where(
                                'id',
                                $id
                            )
                            ->whereIn(
                                'status',
                                [
                                    'pending',
                                    'needs_more_information',
                                ]
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $verification
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                        Report::TYPE_FOUND ||
                        $report->status !==
                            Report::STATUS_PENDING_VERIFICATION
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found report is not currently waiting for USC verification.',
                        ], 422);
                    }

                    $verification->update([
                        'status' =>
                            'archived',

                        'verifier_admin_id' =>
                            $admin->id,

                        'verified_at' =>
                            Carbon::now(),

                        'notes' =>
                            trim(
                                $validated[
                                    'notes'
                                ]
                            ),
                    ]);

                    $report->update([
                        'status' =>
                            Report::STATUS_REJECTED,

                        'rejected_by_admin_id' =>
                            $admin->id,

                        'rejected_at' =>
                            Carbon::now(),

                        'published_at' =>
                            null,
                    ]);

                    VisiblePostedItem::where(
                        'report_id',
                        $report->id
                    )->delete();

                    $this->syncArchiveRecord(
                        $report,
                        'rejected',
                        trim(
                            $validated['notes']
                        )
                    );

                    $this->createStudentNotification([
                        'student_id' =>
                            $report->student_id,

                        'type' =>
                            'found_item_verification_rejected',

                        'title' =>
                            'Found Item Verification Rejected',

                        'message' =>
                            "USC rejected the verification of your found item '{$report->title}'.",

                        'action' =>
                            'item_verification_rejected',

                        'status' =>
                            'pending',

                        'recipient_id' =>
                            $report->student_id,

                        'recipient_role' =>
                            'student',

                        'subject_type' =>
                            ItemVerification::class,

                        'subject_id' =>
                            $verification->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'notes' =>
                                trim(
                                    $validated[
                                        'notes'
                                    ]
                                ),
                        ],

                        'is_read' =>
                            false,
                    ]);

                    $this->recordAuditLog(
                        $admin,
                        'verification_rejected',
                        ItemVerification::class,
                        $verification->id,
                        'Item Verification',
                        [
                            'report_id' =>
                                $report->id,

                            'rejection_notes' =>
                                trim(
                                    $validated[
                                        'notes'
                                    ]
                                ),
                        ],
                        $request->ip()
                    );

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Item verification rejected.',

                        'data' =>
                            $verification
                                ->fresh([
                                    'report.student',
                                    'report.images',
                                    'verifierAdmin',
                                ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function requestVerificationDetails(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated =
            $request->validate([
                'notes' =>
                    'required|string|max:1000',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $admin,
                    $validated
                ) {
                    $verification =
                        ItemVerification::query()
                            ->where(
                                'id',
                                $id
                            )
                            ->whereIn(
                                'status',
                                [
                                    'pending',
                                    'needs_more_information',
                                ]
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $verification
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                        Report::TYPE_FOUND ||
                        $report->status !==
                            Report::STATUS_PENDING_VERIFICATION
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found report is not currently waiting for verification details.',
                        ], 422);
                    }

                    $notes =
                        trim(
                            $validated['notes']
                        );

                    $verification->update([
                        'status' =>
                            'needs_more_information',

                        'notes' =>
                            $notes,
                    ]);

                    $metadata =
                        array_merge(
                            $report->metadata
                                ?? [],
                            [
                                'verification_details_requested' =>
                                    true,

                                'verification_details_request' =>
                                    $notes,

                                'verification_details_requested_at' =>
                                    now()
                                        ->toIso8601String(),

                                'verification_details_requested_by_admin_id' =>
                                    $admin->id,
                            ]
                        );

                    $report->update([
                        'metadata' =>
                            $metadata,
                    ]);

                    $this->createStudentNotification([
                        'student_id' =>
                            $report->student_id,

                        'type' =>
                            'verification_details_requested',

                        'title' =>
                            'Verification Details Needed',

                        'message' =>
                            "USC requested more details for your found item '{$report->title}': {$notes}",

                        'action' =>
                            'verification_details_requested',

                        'status' =>
                            'pending',

                        'recipient_id' =>
                            $report->student_id,

                        'recipient_role' =>
                            'student',

                        'subject_type' =>
                            ItemVerification::class,

                        'subject_id' =>
                            $verification->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'verification_id' =>
                                $verification->id,

                            'notes' =>
                                $notes,
                        ],

                        'is_read' =>
                            false,
                    ]);

                    $this->recordAuditLog(
                        $admin,
                        'verification_details_requested',
                        ItemVerification::class,
                        $verification->id,
                        'Item Verification',
                        [
                            'report_id' =>
                                $report->id,

                            'notes' =>
                                $notes,
                        ],
                        $request->ip()
                    );

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Verification details requested.',

                        'data' =>
                            $verification
                                ->fresh([
                                    'report.student',
                                    'report.images',
                                    'verifierAdmin',
                                ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }

    public function sendClaimInstructions(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $verification = ItemVerification::with('report')->findOrFail($id);

                $claim = ClaimReview::where('report_id', $verification->report_id)
                    ->whereIn('review_status', [
                        'approved',
                        'pending',
                        'additional_information_required',
                    ])
                    ->whereNotNull('claimant_student_id')
                    ->latest()
                    ->first();

                $recipientStudentId = $claim?->claimant_student_id
                    ?: $verification->report?->student_id;

                if (!$recipientStudentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No claimant student is associated with this verification.',
                    ], 422);
                }

                $this->createStudentNotification([
                    'student_id' => $recipientStudentId,
                    'type' => 'claim_pickup_instructions',
                    'title' => 'Item Ready for Claiming',
                    'message' => $validated['message'],
                    'action' => 'claim_pickup_instructions',
                    'status' => 'pending',
                    'recipient_id' => $recipientStudentId,
                    'recipient_role' => 'student',
                    'subject_type' => $claim ? ClaimReview::class : ItemVerification::class,
                    'subject_id' => $claim?->id ?: $verification->id,
                    'is_read' => false,
                ]);

                $this->recordAuditLog(
                    $admin,
                    'claim_pickup_instructions_sent',
                    $claim ? ClaimReview::class : ItemVerification::class,
                    $claim?->id ?: $verification->id,
                    $claim ? 'Claim Review' : 'Item Verification',
                    [
                        'report_id' => $verification->report_id,
                        'claimant_student_id' => $recipientStudentId,
                        'message' => $validated['message'],
                    ],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pickup instruction sent to the claimant.',
                    'data' => $claim?->fresh([
                        'report',
                        'claimantStudent',
                    ]) ?: $verification->fresh('report'),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Compatibility endpoint used by Item Verification's Complete button.
     * A report cannot be marked returned simply by completing verification:
     * USC must confirm the physical handover through Item Release. Delegate
     * to the same finalization action so return rewards are credited once.
     */
    public function completeVerificationTransaction(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $verification = ItemVerification::query()->with('report')->findOrFail($id);
        $report = $verification->report;
        if (!$report || $report->type !== Report::TYPE_FOUND) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only verified Found items can be completed in this workflow.',
            ], 422);
        }

        if ($report->status === Report::STATUS_COMPLETED_RETURN) {
            // The physical return was already finalized by USC via Item Release.
            $verification->update(['status' => 'completed']);
            return response()->json([
                'status' => 'success',
                'message' => 'Return already completed. Verification marked complete.',
                'data' => $verification->fresh(['report', 'verifierAdmin']),
            ]);
        }

        if (!in_array($verification->status, ['verified', 'completed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The found item must be verified before completion.',
            ], 422);
        }

        $release = ItemRelease::query()
            ->where('report_id', $report->id)
            ->where('status', ItemRelease::STATUS_APPROVED)
            ->first();

        if (!$release || !$release->hasCompletedChecklist()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Complete the approved Item Release checklist and confirm the physical handover before closing this transaction.',
            ], 422);
        }

        // Reuse the established, locked release transaction: verified claim,
        // physical return, archive, notifications and final reward installment.
        $result = $this->completeItemRelease($request, $release->id);
        if ($result->getStatusCode() >= 200 && $result->getStatusCode() < 300) {
            $verification->update(['status' => 'completed']);
            return response()->json([
                'status' => 'success',
                'message' => 'Physical return confirmed; transaction and verification completed.',
                'data' => $verification->fresh(['report', 'verifierAdmin']),
            ]);
        }

        return $result;
    }

    public function archiveVerification(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $verification = ItemVerification::lockForUpdate()->findOrFail($id);
                $report = $verification->report()->lockForUpdate()->firstOrFail();

                $verification->update([
                    'status' => 'archived',
                ]);

                $this->syncArchiveRecord(
                    $report,
                    $report->status === Report::STATUS_REJECTED
                        ? 'rejected'
                        : 'completed',
                    'Archived from claimant verification.'
                );

                $this->recordAuditLog(
                    $admin,
                    'verification_archived',
                    ItemVerification::class,
                    $verification->id,
                    'Item Verification',
                    [
                        'report_id' => $report->id,
                    ],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Verification archived successfully.',
                    'data' => $verification->fresh([
                        'report',
                        'verifierAdmin',
                    ]),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE POTENTIAL MATCHES AFTER APPROVAL / VERIFICATION
    |--------------------------------------------------------------------------
    |
    | The system creates PENDING match suggestions only.
    | Students are not told a match is confirmed until USC reviews it.
    |
    */

    private function generatePotentialMatchesForReport(
        Report $report,
        Admin $admin
    ): int {
        $results =
            app(
                PotentialMatchService::class
            )->generateForReport(
                $report
            );

        $createdCount = 0;

        foreach ($results as $result) {
            if (!$result['created']) {
                continue;
            }

            $match =
                $result['match'];

            $createdCount++;

            Notification::create([
                'type' =>
                    'potential_match',

                'title' =>
                    'Potential Match Needs Review',

                'message' =>
                    "The matching system found a possible Lost/Found pair with a similarity score of {$result['score']}%.",

                'action' =>
                    'potential_match',

                'status' =>
                    'pending',

                'recipient_role' =>
                    'admin',

                'subject_type' =>
                    PotentialMatch::class,

                'subject_id' =>
                    $match->id,

                'data' => [
                    'potential_match_id' =>
                        $match->id,

                    'lost_report_id' =>
                        $match->lost_report_id,

                    'found_report_id' =>
                        $match->found_report_id,

                    'score' =>
                        $result['score'],
                ],

                'is_read' =>
                    false,
            ]);

            /*
            |--------------------------------------------------------------------------
            | NOTIFY THE CONCERNED LOST-ITEM STUDENT IMMEDIATELY
            |--------------------------------------------------------------------------
            |
            | Revised SNSU flow:
            | - System detects a possible match.
            | - USC Admin is notified.
            | - The concerned lost-item student is also notified.
            |
            | IMPORTANT:
            | This does NOT confirm ownership. The student must still submit a
            | claim request and USC must verify the ownership proof.
            |
            */

            $lostReport =
                Report::query()
                    ->find(
                        $match->lost_report_id
                    );

            if (
                $lostReport &&
                $lostReport->student_id
            ) {
                $this->createStudentNotification([
                    'student_id' =>
                        $lostReport->student_id,

                    'type' =>
                        'potential_match_detected',

                    'title' =>
                        'Possible Match Detected',

                    'message' =>
                        "A verified found item may match your lost-item report '{$lostReport->title}'. This does not confirm ownership. USC is reviewing the possible match, and you will still need to submit a claim with ownership proof if the item may be yours.",

                    'action' =>
                        'potential_match_detected',

                    'status' =>
                        'pending',

                    'recipient_id' =>
                        $lostReport->student_id,

                    'recipient_role' =>
                        'student',

                    'subject_type' =>
                        PotentialMatch::class,

                    'subject_id' =>
                        $match->id,

                    'data' => [
                        'potential_match_id' =>
                            $match->id,

                        'lost_report_id' =>
                            $match->lost_report_id,

                        'found_report_id' =>
                            $match->found_report_id,

                        'score' =>
                            $result['score'],

                        'ownership_confirmed' =>
                            false,

                        'requires_claim' =>
                            true,
                    ],

                    'is_read' =>
                        false,
                ]);
            }
        }

        return $createdCount;
    }


    public function confirmPotentialMatch(
        Request $request,
        $id
    ) {
        $admin =
            Auth::guard(
                'admin'
            )->user();

        if (!$admin) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Unauthorized.',
            ], 401);
        }

        $match =
            PotentialMatch::with([
                'lostReport',
                'foundReport',
            ])->findOrFail(
                $id
            );

        if (
            $match->status ===
            'confirmed'
        ) {
            return response()->json([
                'status' =>
                    'success',

                'message' =>
                    'This potential match is already confirmed.',

                'data' =>
                    $match,
            ]);
        }

        if (
            $match->status !==
            'pending'
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Only pending potential matches can be confirmed.',
            ], 422);
        }

        if (
            !$match->lostReport ||
            !$match->foundReport
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'The Lost or Found report for this match no longer exists.',
            ], 422);
        }

        $service =
            app(
                PotentialMatchService::class
            );

        if (
            !$service->isEligible(
                $match->lostReport
            ) ||
            !$service->isEligible(
                $match->foundReport
            )
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'One of these reports is no longer eligible for matching.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $request,
                $admin,
                $match
            ) {
                $match->update([
                    'status' =>
                        'confirmed',

                    'reviewed_by_admin_id' =>
                        $admin->id,

                    'reviewed_at' =>
                        Carbon::now(),
                ]);

                $match
                    ->lostReport
                    ->update([
                        'status' =>
                            Report::STATUS_POTENTIAL_MATCH_FOUND,
                    ]);

                $match
                    ->foundReport
                    ->update([
                        'status' =>
                            Report::STATUS_POTENTIAL_MATCH_FOUND,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | A confirmed potential match is NOT proof of ownership.
                |--------------------------------------------------------------------------
                */

                if (
                    $match
                        ->lostReport
                        ->student_id
                ) {
                    $this->createStudentNotification([
                        'student_id' =>
                            $match
                                ->lostReport
                                ->student_id,

                        'recipient_id' =>
                            $match
                                ->lostReport
                                ->student_id,

                        'recipient_role' =>
                            'student',

                        'type' =>
                            'lost_found_match',

                        'title' =>
                            'Possible Match Found',

                        'message' =>
                            'USC confirmed a possible found-item match for your lost report. This is not ownership confirmation. Review the found item and submit a claim if you believe it is yours.',

                        'action' =>
                            'view_match',

                        'status' =>
                            'pending',

                        'subject_type' =>
                            PotentialMatch::class,

                        'subject_id' =>
                            $match->id,

                        'data' => [
                            'potential_match_id' =>
                                $match->id,

                            'lost_report_id' =>
                                $match
                                    ->lost_report_id,

                            'found_report_id' =>
                                $match
                                    ->found_report_id,

                            'requires_claim' =>
                                true,
                        ],

                        'is_read' =>
                            false,
                    ]);
                }

                $this->recordAuditLog(
                    $admin,
                    'potential_match_confirmed',
                    PotentialMatch::class,
                    $match->id,
                    'Potential Match',
                    [
                        'lost_report_id' =>
                            $match
                                ->lost_report_id,

                        'found_report_id' =>
                            $match
                                ->found_report_id,

                        'score' =>
                            $match->score,
                    ],
                    $request->ip()
                );
            }
        );

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Potential match confirmed. The lost-item owner has been notified to review it and submit a claim if appropriate.',

            'data' =>
                $match->fresh([
                    'lostReport.images',
                    'lostReport.student',
                    'foundReport.images',
                    'foundReport.student',
                ]),
        ]);
    }


    public function rejectPotentialMatch(
        Request $request,
        $id
    ) {
        $admin =
            Auth::guard(
                'admin'
            )->user();

        if (!$admin) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Unauthorized.',
            ], 401);
        }

        $match =
            PotentialMatch::with([
                'lostReport',
                'foundReport',
            ])->findOrFail(
                $id
            );

        if (
            $match->status ===
            'rejected'
        ) {
            return response()->json([
                'status' =>
                    'success',

                'message' =>
                    'This potential match is already rejected.',

                'data' =>
                    $match,
            ]);
        }

        $service =
            app(
                PotentialMatchService::class
            );

        DB::transaction(
            function () use (
                $request,
                $admin,
                $match,
                $service
            ) {
                $match->update([
                    'status' =>
                        'rejected',

                    'reviewed_by_admin_id' =>
                        $admin->id,

                    'reviewed_at' =>
                        Carbon::now(),
                ]);

                if ($match->lostReport) {
                    $service
                        ->restoreBaseStatusAfterReview(
                            $match->lostReport,
                            $match->id
                        );
                }

                if ($match->foundReport) {
                    $service
                        ->restoreBaseStatusAfterReview(
                            $match->foundReport,
                            $match->id
                        );
                }

                $this->recordAuditLog(
                    $admin,
                    'potential_match_rejected',
                    PotentialMatch::class,
                    $match->id,
                    'Potential Match',
                    [
                        'lost_report_id' =>
                            $match
                                ->lost_report_id,

                        'found_report_id' =>
                            $match
                                ->found_report_id,
                    ],
                    $request->ip()
                );
            }
        );

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Potential match rejected. The reports remain available for other valid matches.',

            'data' =>
                $match->fresh([
                    'lostReport.images',
                    'lostReport.student',
                    'foundReport.images',
                    'foundReport.student',
                ]),
        ]);
    }


    public function resetPotentialMatch(
        Request $request,
        $id
    ) {
        $admin =
            Auth::guard(
                'admin'
            )->user();

        if (!$admin) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Unauthorized.',
            ], 401);
        }

        $match =
            PotentialMatch::with([
                'lostReport',
                'foundReport',
            ])->findOrFail(
                $id
            );

        $previousStatus =
            $match->status;

        $service =
            app(
                PotentialMatchService::class
            );

        DB::transaction(
            function () use (
                $request,
                $admin,
                $match,
                $previousStatus,
                $service
            ) {
                $match->update([
                    'status' =>
                        'pending',

                    'reviewed_by_admin_id' =>
                        null,

                    'reviewed_at' =>
                        null,
                ]);

                if (
                    $previousStatus ===
                    'confirmed'
                ) {
                    if ($match->lostReport) {
                        $service
                            ->restoreBaseStatusAfterReview(
                                $match->lostReport,
                                $match->id
                            );
                    }

                    if ($match->foundReport) {
                        $service
                            ->restoreBaseStatusAfterReview(
                                $match->foundReport,
                                $match->id
                            );
                    }
                }

                $this->recordAuditLog(
                    $admin,
                    'potential_match_reset',
                    PotentialMatch::class,
                    $match->id,
                    'Potential Match',
                    [
                        'previous_status' =>
                            $previousStatus,
                    ],
                    $request->ip()
                );
            }
        );

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Potential match returned to pending USC review.',

            'data' =>
                $match->fresh([
                    'lostReport.images',
                    'lostReport.student',
                    'foundReport.images',
                    'foundReport.student',
                ]),
        ]);
    }


    public function approveClaim(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            return DB::transaction(function () use ($request, $id, $admin) {
                $claim = ClaimReview::query()
                    ->where('id', $id)
                    ->whereIn('review_status', [
                        'pending',
                        'additional_information_required',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $claim
                    ->report()
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($report->type !== Report::TYPE_FOUND) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Only verified found items can be approved for ownership claims.',
                    ], 422);
                }

                if (!in_array(
                    $report->status,
                    [
                        Report::STATUS_VERIFIED,
                        Report::STATUS_POTENTIAL_MATCH_FOUND,
                        Report::STATUS_UNDER_CLAIM_REVIEW,
                    ],
                    true
                )) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This found report is not ready for claim approval.',
                    ], 422);
                }

                $otherApprovedClaim = ClaimReview::query()
                    ->where('report_id', $report->id)
                    ->where('id', '<>', $claim->id)
                    ->where('review_status', 'approved')
                    ->lockForUpdate()
                    ->first();

                if ($otherApprovedClaim) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Another ownership claim for this item has already been approved.',
                    ], 409);
                }

                $claim->update([
                    'review_status' => 'approved',
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ]);

                /*
                |------------------------------------------------------------------
                | ONLY ONE CLAIMANT MAY RECEIVE THE PHYSICAL ITEM
                |------------------------------------------------------------------
                */

                $competingClaims = ClaimReview::query()
                    ->where('report_id', $report->id)
                    ->where('id', '<>', $claim->id)
                    ->whereIn('review_status', [
                        'pending',
                        'additional_information_required',
                    ])
                    ->lockForUpdate()
                    ->get();

                foreach ($competingClaims as $competingClaim) {
                    $competingClaim->update([
                        'review_status' => 'rejected',
                        'notes' => 'Another ownership claim for this found item was approved by USC.',
                        'reviewed_by_admin_id' => $admin->id,
                        'reviewed_at' => Carbon::now(),
                    ]);

                    $this->createStudentNotification([
                        'student_id' => $competingClaim->claimant_student_id,
                        'type' => 'claim_rejected',
                        'title' => 'Claim Closed',
                        'message' => "Another ownership claim for '{$report->title}' was approved by USC, so this claim has been closed.",
                        'action' => 'claim_rejected',
                        'status' => 'pending',
                        'recipient_id' => $competingClaim->claimant_student_id,
                        'recipient_role' => 'student',
                        'subject_type' => ClaimReview::class,
                        'subject_id' => $competingClaim->id,
                        'data' => [
                            'report_id' => $report->id,
                        ],
                        'is_read' => false,
                    ]);
                }

                $report->update([
                    'status' => Report::STATUS_READY_FOR_RELEASE,
                ]);

                /*
                |------------------------------------------------------------------
                | IF THIS CLAIMANT OWNS A CONFIRMED MATCHED LOST REPORT,
                | KEEP THAT LOST REPORT UNDER CLAIM REVIEW UNTIL RETURN COMPLETES.
                |------------------------------------------------------------------
                */

                $confirmedMatches = PotentialMatch::query()
                    ->where('found_report_id', $report->id)
                    ->where('status', 'confirmed')
                    ->with('lostReport')
                    ->get();

                foreach ($confirmedMatches as $match) {
                    if (
                        $match->lostReport &&
                        $match->lostReport->type === Report::TYPE_LOST &&
                        (int) $match->lostReport->student_id ===
                            (int) $claim->claimant_student_id
                    ) {
                        $match->lostReport->update([
                            'status' => Report::STATUS_UNDER_CLAIM_REVIEW,
                        ]);
                    }
                }

                $release = ItemRelease::query()
                    ->where('report_id', $report->id)
                    ->where('claimant_student_id', $claim->claimant_student_id)
                    ->whereIn('status', [
                        'pending',
                        'approved',
                    ])
                    ->first();

                if (!$release) {
                    $release = ItemRelease::create([
                        'report_id' => $report->id,
                        'claimant_student_id' => $claim->claimant_student_id,
                        'approved_by_admin_id' => $admin->id,
                        'release_code' => (string) Str::uuid(),
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

                $this->createStudentNotification([
                    'student_id' => $claim->claimant_student_id,
                    'type' => 'claim_approved',
                    'title' => 'Ownership Claim Approved',
                    'message' => "USC approved your ownership claim for '{$report->title}'. Please proceed to the USC office for the physical release process.",
                    'action' => 'claim_approved',
                    'status' => 'pending',
                    'recipient_id' => $claim->claimant_student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'data' => [
                        'report_id' => $report->id,
                        'claim_id' => $claim->id,
                        'release_id' => $release->id,
                        'release_code' => $release->release_code,
                    ],
                    'is_read' => false,
                ]);

                $this->recordAuditLog(
                    $admin,
                    'claim_approved',
                    ClaimReview::class,
                    $claim->id,
                    'Claim Review',
                    [
                        'report_id' => $report->id,
                        'claimant_student_id' => $claim->claimant_student_id,
                        'release_id' => $release->id,
                        'competing_claims_closed' => $competingClaims->count(),
                    ],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Claim approved. The item has moved to the Item Release workflow.',
                    'data' => $claim->fresh([
                        'report.images',
                        'report.categoryRelation',
                        'claimantStudent',
                        'reviewedByAdmin',
                    ]),
                    'release' => $release,
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }


    public function rejectClaim(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $claim = ClaimReview::query()
                    ->where('id', $id)
                    ->whereIn('review_status', [
                        'pending',
                        'additional_information_required',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $claim
                    ->report()
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($report->type !== Report::TYPE_FOUND) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Only found-item ownership claims can be rejected here.',
                    ], 422);
                }

                $claim->update([
                    'review_status' => 'rejected',
                    'notes' => trim($validated['notes']),
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ]);

                /*
                |------------------------------------------------------------------
                | KEEP UNDER_CLAIM_REVIEW IF ANOTHER ACTIVE CLAIM STILL EXISTS
                |------------------------------------------------------------------
                */

                $anotherActiveClaimExists = ClaimReview::query()
                    ->where('report_id', $report->id)
                    ->where('id', '<>', $claim->id)
                    ->whereIn('review_status', [
                        'pending',
                        'additional_information_required',
                        'approved',
                    ])
                    ->exists();

                if (
                    $report->status === Report::STATUS_UNDER_CLAIM_REVIEW &&
                    !$anotherActiveClaimExists
                ) {
                    $confirmedMatchExists = PotentialMatch::query()
                        ->where('found_report_id', $report->id)
                        ->where('status', 'confirmed')
                        ->exists();

                    $report->update([
                        'status' => $confirmedMatchExists
                            ? Report::STATUS_POTENTIAL_MATCH_FOUND
                            : Report::STATUS_VERIFIED,
                    ]);
                }

                $this->createStudentNotification([
                    'student_id' => $claim->claimant_student_id,
                    'type' => 'claim_rejected',
                    'title' => 'Ownership Claim Rejected',
                    'message' => "USC rejected your ownership claim for '{$report->title}'.",
                    'action' => 'claim_rejected',
                    'status' => 'pending',
                    'recipient_id' => $claim->claimant_student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'data' => [
                        'report_id' => $report->id,
                        'notes' => trim($validated['notes']),
                    ],
                    'is_read' => false,
                ]);

                $this->recordAuditLog(
                    $admin,
                    'claim_rejected',
                    ClaimReview::class,
                    $claim->id,
                    'Claim Review',
                    [
                        'report_id' => $claim->report_id,
                        'claimant_student_id' => $claim->claimant_student_id,
                        'notes' => trim($validated['notes']),
                    ],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Claim rejected successfully.',
                    'data' => $claim->fresh([
                        'report.images',
                        'report.categoryRelation',
                        'claimantStudent',
                        'reviewedByAdmin',
                    ]),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }


    public function requestClaimInformation(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $admin, $validated) {
                $claim = ClaimReview::query()
                    ->where('id', $id)
                    ->whereIn('review_status', [
                        'pending',
                        'additional_information_required',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                $report = $claim
                    ->report()
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $report->type !== Report::TYPE_FOUND ||
                    $report->status === Report::STATUS_READY_FOR_RELEASE ||
                    in_array(
                        $report->status,
                        [
                            Report::STATUS_COMPLETED_RETURN,
                            Report::STATUS_ARCHIVED,
                            Report::STATUS_REJECTED,
                        ],
                        true
                    )
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'This claim can no longer request additional ownership proof.',
                    ], 422);
                }

                $notes = trim($validated['notes']);

                $claim->update([
                    'review_status' => 'additional_information_required',
                    'notes' => $notes,
                    'reviewed_by_admin_id' => $admin->id,
                    'reviewed_at' => Carbon::now(),
                ]);

                if ($report->status !== Report::STATUS_UNDER_CLAIM_REVIEW) {
                    $report->update([
                        'status' => Report::STATUS_UNDER_CLAIM_REVIEW,
                    ]);
                }

                $this->createStudentNotification([
                    'student_id' => $claim->claimant_student_id,
                    'type' => 'claim_information_requested',
                    'title' => 'More Ownership Proof Needed',
                    'message' => "USC requested additional proof for your claim on '{$report->title}': {$notes}",
                    'action' => 'claim_information_requested',
                    'status' => 'pending',
                    'recipient_id' => $claim->claimant_student_id,
                    'recipient_role' => 'student',
                    'subject_type' => ClaimReview::class,
                    'subject_id' => $claim->id,
                    'data' => [
                        'report_id' => $report->id,
                        'claim_id' => $claim->id,
                        'notes' => $notes,
                    ],
                    'is_read' => false,
                ]);

                $this->recordAuditLog(
                    $admin,
                    'claim_information_requested',
                    ClaimReview::class,
                    $claim->id,
                    'Claim Review',
                    [
                        'report_id' => $claim->report_id,
                        'claimant_student_id' => $claim->claimant_student_id,
                        'notes' => $notes,
                    ],
                    $request->ip()
                );

                return response()->json([
                    'status' => 'success',
                    'message' => 'Additional claim information requested from the claimant.',
                    'data' => $claim->fresh([
                        'report.images',
                        'report.categoryRelation',
                        'claimantStudent',
                        'reviewedByAdmin',
                    ]),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }


    public function completeItemRelease(Request $request, $id)
    {
        $admin =
            Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $admin
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | RELEASE MUST BE FULLY APPROVED FIRST
                    |--------------------------------------------------------------------------
                    */

                    $release =
                        ItemRelease::query()
                            ->where(
                                'id',
                                $id
                            )
                            ->where(
                                'status',
                                ItemRelease::STATUS_APPROVED
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        !$release
                            ->hasCompletedChecklist()
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'All release checklist items must be completed before the item can be returned.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | APPROVED CLAIM
                    |--------------------------------------------------------------------------
                    */

                    $claim =
                        ClaimReview::query()
                            ->where(
                                'report_id',
                                $release->report_id
                            )
                            ->where(
                                'claimant_student_id',
                                $release->claimant_student_id
                            )
                            ->where(
                                'review_status',
                                ClaimReview::STATUS_APPROVED
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | FOUND REPORT MUST BE READY FOR RELEASE
                    |--------------------------------------------------------------------------
                    */

                    $report =
                        $release
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $report->type !==
                            Report::TYPE_FOUND ||
                        $report->status !==
                            Report::STATUS_READY_FOR_RELEASE
                    ) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'This found item is not in the Ready for Release stage.',
                        ], 422);
                    }

                    $finder =
                        $report->student;

                    $completedAt =
                        Carbon::now();

                    /*
                    |--------------------------------------------------------------------------
                    | PHYSICAL HANDOVER
                    |--------------------------------------------------------------------------
                    |
                    | Claim status sequence:
                    |
                    | approved -> item_released -> completed
                    |
                    | The two final transitions happen in the same protected database
                    | transaction because "Complete Item Return" represents the USC
                    | administrator confirming the physical handover and completing
                    | the transaction.
                    |
                    */

                    $release->markAsReleased(
                        $admin->id
                    );

                    $claim->markItemReleased(
                        $admin->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | FOUND REPORT = COMPLETED RETURN
                    |--------------------------------------------------------------------------
                    */

                    $report->update([
                        'status' =>
                            Report::STATUS_COMPLETED_RETURN,

                        'completed_at' =>
                            $completedAt,

                        'archived_at' =>
                            $completedAt,

                        'published_at' =>
                            null,
                    ]);

                    VisiblePostedItem::where(
                        'report_id',
                        $report->id
                    )->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | COMPLETE ONLY THE CLAIMANT'S CONFIRMED MATCHED LOST REPORT
                    |--------------------------------------------------------------------------
                    |
                    | Do not complete every Lost report that happened to be suggested
                    | against this Found item. Only a confirmed potential match that
                    | belongs to the approved claimant may be completed.
                    |
                    */

                    $completedLostReportIds = [];

                    $confirmedMatches =
                        PotentialMatch::query()
                            ->where(
                                'found_report_id',
                                $report->id
                            )
                            ->where(
                                'status',
                                'confirmed'
                            )
                            ->with(
                                'lostReport'
                            )
                            ->get();

                    foreach (
                        $confirmedMatches
                        as $match
                    ) {
                        $lostReport =
                            $match->lostReport;

                        if (
                            !$lostReport ||
                            $lostReport->type !==
                                Report::TYPE_LOST ||
                            (int)
                            $lostReport->student_id !==
                                (int)
                                $claim->claimant_student_id
                        ) {
                            continue;
                        }

                        $lostReport->update([
                            'status' =>
                                Report::STATUS_COMPLETED_RETURN,

                            'completed_at' =>
                                $completedAt,

                            'archived_at' =>
                                $completedAt,

                            'published_at' =>
                                null,
                        ]);

                        VisiblePostedItem::where(
                            'report_id',
                            $lostReport->id
                        )->delete();

                        $this->syncArchiveRecord(
                            $lostReport,
                            'completed',
                            'Lost report completed after the verified matching found item was returned to its owner.'
                        );

                        $completedLostReportIds[] =
                            $lostReport->id;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | TRANSFER THE FOUND REPORT TO THE ADMIN ARCHIVE
                    |--------------------------------------------------------------------------
                    |
                    | The source report keeps the official completed_return status for
                    | workflow history, while ArchiveItem stores the archived record.
                    |
                    */

                    $this->syncArchiveRecord(
                        $report,
                        'completed',
                        'Item successfully returned to the verified claimant.'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | CLAIM TRANSACTION COMPLETE
                    |--------------------------------------------------------------------------
                    */

                    $claim->complete(
                        $admin->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | FINAL CATEGORY-BASED RECOGNITION POINTS
                    |--------------------------------------------------------------------------
                    |
                    | First installment was credited at verified publication.
                    | This action credits only the remainder of that category reward.
                    |
                    */

                    $recognitionAward =
                        $this->awardCompletedReturnRecognitionPoints(
                            $finder,
                            $report,
                            $release,
                            $admin
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | NOTIFICATIONS
                    |--------------------------------------------------------------------------
                    */

                    if ($finder?->id) {
                        $this->createStudentNotification([
                            'student_id' =>
                                $finder->id,

                            'type' =>
                                'return_completed',

                            'title' =>
                                'Successful Item Return',

                            'message' =>
                                "USC completed the return of your found item '{$report->title}' to its verified owner.",

                            'action' =>
                                'return_completed',

                            'status' =>
                                'pending',

                            'recipient_id' =>
                                $finder->id,

                            'recipient_role' =>
                                'student',

                            'subject_type' =>
                                ItemRelease::class,

                            'subject_id' =>
                                $release->id,

                            'data' => [
                                'report_id' =>
                                    $report->id,

                                'release_id' =>
                                    $release->id,

                                'recognition_points_pending' =>
                                    false,

                                'points_awarded' =>
                                    $recognitionAward['awarded'],

                                'recognition_points_balance' =>
                                    $recognitionAward['balance'],
                            ],

                            'is_read' =>
                                false,
                        ]);
                    }

                    if (
                        $claim
                            ->claimant_student_id
                    ) {
                        $this->createStudentNotification([
                            'student_id' =>
                                $claim
                                    ->claimant_student_id,

                            'type' =>
                                'claim_return_completed',

                            'title' =>
                                'Item Return Completed',

                            'message' =>
                                "USC completed your item release for '{$report->title}'. The return transaction is now complete.",

                            'action' =>
                                'claim_return_completed',

                            'status' =>
                                'pending',

                            'recipient_id' =>
                                $claim
                                    ->claimant_student_id,

                            'recipient_role' =>
                                'student',

                            'subject_type' =>
                                ItemRelease::class,

                            'subject_id' =>
                                $release->id,

                            'data' => [
                                'report_id' =>
                                    $report->id,

                                'claim_id' =>
                                    $claim->id,

                                'release_id' =>
                                    $release->id,

                                'matched_lost_report_ids' =>
                                    $completedLostReportIds,
                            ],

                            'is_read' =>
                                false,
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | AUDIT LOG
                    |--------------------------------------------------------------------------
                    */

                    $this->recordAuditLog(
                        $admin,
                        'release_completed',
                        ItemRelease::class,
                        $release->id,
                        'Item Release',
                        [
                            'report_id' =>
                                $report->id,

                            'claim_id' =>
                                $claim->id,

                            'claimant_student_id' =>
                                $claim
                                    ->claimant_student_id,

                            'finder_student_id' =>
                                $finder?->id,

                            'released_at' =>
                                $release
                                    ->fresh()
                                    ->released_at
                                    ?->toDateTimeString(),

                            'claim_status_sequence' => [
                                ClaimReview::STATUS_ITEM_RELEASED,
                                ClaimReview::STATUS_COMPLETED,
                            ],

                            'completed_lost_report_ids' =>
                                $completedLostReportIds,

                            'recognition_points_awarded' =>
                                $recognitionAward['awarded'],

                            'recognition_points_balance' =>
                                $recognitionAward['balance'],
                        ],
                        $request->ip()
                    );

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Item return completed successfully. The reports were removed from active listings and transferred to the archive.',

                        'data' =>
                            $release->fresh([
                                'report.student',
                                'report.images',
                                'report.categoryRelation',
                                'claimantStudent',
                                'approvedByAdmin',
                            ]),

                        'claim_status' =>
                            ClaimReview::STATUS_COMPLETED,

                        'report_status' =>
                            Report::STATUS_COMPLETED_RETURN,

                        'completed_lost_report_ids' =>
                            $completedLostReportIds,

                        'recognition_points_awarded' =>
                            $recognitionAward['awarded'],

                        'recognition_points_balance' =>
                            $recognitionAward['balance'],
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Complete item release failed',
                [
                    'release_id' =>
                        $id,

                    'message' =>
                        $exception
                            ->getMessage(),
                ]
            );

            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    $exception
                        ->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE PREDEFINED RETURN POINTS FOR AN ITEM CATEGORY
    |--------------------------------------------------------------------------
    |
    | Admin Point Rules are the only source of truth for Base Points.
    | No static fallback points apply to new reward transactions.
    |
    */

    /**
     * Admin-only first installment, called inside a transaction with the
     * found Report locked FOR UPDATE. The unique reference_key also protects
     * against duplicate awards across retries and concurrent requests.
     *
     * The Base Points value is snapshotted in notes at publication. Later
     * changes to Admin Point Rules affect only reports not yet rewarded.
     */
    private function awardFoundPublicationRecognitionPoints(Report $report, Admin $admin): array
    {
        if (
            $report->type !== Report::TYPE_FOUND ||
            $report->status !== Report::STATUS_VERIFIED ||
            !$report->published_at ||
            !VisiblePostedItem::query()
                ->where('report_id', $report->id)
                ->where('is_visible', true)
                ->exists()
        ) {
            return ['awarded' => 0, 'balance' => null, 'reason' => 'not_published'];
        }

        $finder = $report->student;
        if (!$finder) {
            return ['awarded' => 0, 'balance' => null, 'reason' => 'no_finder'];
        }

        // Synchronize every ledger mutation against the same student row.
        Student::query()->whereKey($finder->id)->lockForUpdate()->firstOrFail();
        $referenceKey = 'findit:found:published:' . $report->id;

        $existing = PointTransaction::query()
            ->where('reference_key', $referenceKey)
            ->orWhere(function ($query) use ($finder, $report) {
                // Recognize an installment issued by the previous controller.
                $query->where('student_id', $finder->id)
                    ->where('type', 'found_publication')
                    ->where('reference_type', Report::class)
                    ->where('reference_id', $report->id);
            })
            ->first();

        if ($existing) {
            return [
                'awarded' => 0,
                'balance' => $this->currentRecognitionBalance($finder->id),
                'reason' => 'already_awarded',
            ];
        }

        $total = $this->resolveCompletedReturnPoints($report);
        if ($total === null) {
            // A missing/inactive category rule must not be replaced by an
            // invented default value such as 5, 10, or 100 points.
            return [
                'awarded' => 0,
                'balance' => $this->currentRecognitionBalance($finder->id),
                'reason' => 'no_active_point_rule',
            ];
        }

        // Whole-number policy: 15 Base Points => 7 at publication + 8 on return.
        $firstInstallment = intdiv($total, 2);
        $time = now();
        DB::table('point_transactions')->insert([
            'student_id' => $finder->id,
            'type' => 'found_publication',
            'direction' => 'credit',
            'points' => $firstInstallment,
            'amount' => round($firstInstallment / self::POINTS_PER_PESO, 2),
            'description' => 'First half of admin category Base Points after USC verified and published the found item.',
            'notes' => 'configured_total=' . $total . '; category_id=' . (int) $report->category_id,
            'reference_key' => $referenceKey,
            'reference_type' => Report::class,
            'reference_id' => $report->id,
            'created_by_admin_id' => $admin->id,
            'processed_by_admin_id' => $admin->id,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $balance = $this->syncFinderRecognitionBalance($finder);
        if ($firstInstallment > 0) {
            $this->createStudentNotification([
                'student_id' => $finder->id,
                'type' => 'recognition_points_awarded',
                'title' => 'Found Item Published — First Reward',
                'message' => "You earned {$firstInstallment} recognition points for the verified publication of '{$report->title}'. The remaining points are awarded after USC confirms its return to the owner.",
                'action' => 'recognition_points_awarded',
                'status' => 'pending',
                'recipient_id' => $finder->id,
                'recipient_role' => 'student',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'data' => [
                    'report_id' => $report->id,
                    'award_type' => 'found_publication',
                    'points_awarded' => $firstInstallment,
                    'configured_category_reward' => $total,
                    'remaining_reward_points' => $total - $firstInstallment,
                    'recognition_points_balance' => $balance,
                ],
                'is_read' => false,
            ]);
        }

        return [
            'awarded' => $firstInstallment,
            'balance' => $balance,
            'configured_category_reward' => $total,
            'remaining_reward_points' => $total - $firstInstallment,
        ];
    }

    /** Signed point ledger (credits positive; approved fine conversions negative). */
    private function currentRecognitionBalance(int $studentId): int
    {
        return max(0, (int) PointTransaction::query()
            ->where('student_id', $studentId)->sum('points'));
    }

    private function syncFinderRecognitionBalance(Student $finder): int
    {
        $balance = $this->currentRecognitionBalance($finder->id);
        StudentRecognitionRecord::updateOrCreate(
            ['student_id' => $finder->id],
            [
                'recognition_points' => $balance,
                'peso_value' => round($balance / self::POINTS_PER_PESO, 2),
                'reward_count' => (int) ($finder->reward_count ?? 0),
                'report_count' => $finder->reports()->count(),
            ]
        );
        return $balance;
    }

    /**
     * Lookup ONLY the matching, active admin category rule. Returns null if
     * there is no configured rule; zero is a valid admin-defined Base Points.
     */
    private function resolveCompletedReturnPoints(Report $report): ?int
    {
        if (!Schema::hasTable('point_rules')) {
            return null;
        }

        $newQuery = function () {
            $query = PointRule::query();
            if (Schema::hasColumn('point_rules', 'is_active')) {
                $query->where('is_active', true);
            } elseif (Schema::hasColumn('point_rules', 'active')) {
                $query->where('active', true);
            }
            return $query;
        };

        $pointRule = null;
        if ($report->category_id && Schema::hasColumn('point_rules', 'category_id')) {
            $pointRule = $newQuery()
                ->where('category_id', $report->category_id)
                ->latest('id')->first();
        }

        // Older reports may store only the category name.
        $categoryName = trim((string) ($report->category ?: $report->categoryRelation?->name ?: ''));
        if (!$pointRule && $categoryName !== '') {
            foreach (['category_name', 'item_category'] as $column) {
                if (!Schema::hasColumn('point_rules', $column)) {
                    continue;
                }
                $pointRule = $newQuery()
                    ->whereRaw('LOWER(' . $column . ') = ?', [Str::lower($categoryName)])
                    ->latest('id')->first();
                if ($pointRule) {
                    break;
                }
            }
        }

        return $pointRule ? max(0, (int) $pointRule->base_points) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | AWARD COMPLETED-RETURN RECOGNITION POINTS
    |--------------------------------------------------------------------------
    */

    private function awardCompletedReturnRecognitionPoints(
        ?Student $finder,
        Report $report,
        ItemRelease $release,
        Admin $admin
    ): array {
        if (!$finder || $report->type !== Report::TYPE_FOUND ||
            $report->status !== Report::STATUS_COMPLETED_RETURN) {
            return ['awarded' => 0, 'balance' => 0, 'reason' => 'not_completed'];
        }

        Student::query()->whereKey($finder->id)->lockForUpdate()->firstOrFail();

        // The unique key is based on the FOUND REPORT, not the release ID.
        // Even if someone creates a second release row, no double payment occurs.
        $referenceKey = 'findit:found:returned:' . $report->id;
        $existing = PointTransaction::query()
            ->where('reference_key', $referenceKey)
            ->orWhere(function ($query) use ($finder, $release) {
                $query->where('student_id', $finder->id)
                    ->where('type', 'return_completion')
                    ->where('reference_type', ItemRelease::class)
                    ->where('reference_id', $release->id);
            })
            ->first();

        if ($existing) {
            return [
                'awarded' => 0,
                'balance' => $this->currentRecognitionBalance($finder->id),
                'reason' => 'already_awarded',
            ];
        }

        $publication = PointTransaction::query()
            ->where('reference_key', 'findit:found:published:' . $report->id)
            ->orWhere(function ($query) use ($finder, $report) {
                $query->where('student_id', $finder->id)
                    ->where('type', 'found_publication')
                    ->where('reference_type', Report::class)
                    ->where('reference_id', $report->id);
            })
            ->first();

        // No retrospective / free-standing final payouts. Reports published
        // before this two-stage workflow remain untouched for manual review.
        if (!$publication ||
            !preg_match('/configured_total=(\d+)/', (string) $publication->notes, $matches)) {
            return [
                'awarded' => 0,
                'balance' => $this->currentRecognitionBalance($finder->id),
                'reason' => 'publication_snapshot_missing',
            ];
        }

        $total = max(0, (int) $matches[1]);
        $first = max(0, (int) $publication->points);
        $remaining = max(0, $total - $first);
        $time = now();

        // Record even a zero remaining installment to lock the stage forever.
        DB::table('point_transactions')->insert([
            'student_id' => $finder->id,
            'type' => 'return_completion',
            'direction' => 'credit',
            'points' => $remaining,
            'amount' => round($remaining / self::POINTS_PER_PESO, 2),
            'description' => 'Remaining admin category Base Points after USC confirmed the item returned to its owner.',
            'notes' => 'configured_total=' . $total . '; publication_points=' . $first . '; report_id=' . $report->id,
            'reference_key' => $referenceKey,
            'reference_type' => ItemRelease::class,
            'reference_id' => $release->id,
            'created_by_admin_id' => $admin->id,
            'processed_by_admin_id' => $admin->id,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $balance = $this->syncFinderRecognitionBalance($finder);
        if ($remaining > 0) {
            $this->createStudentNotification([
                'student_id' => $finder->id,
                'type' => 'recognition_points_awarded',
                'title' => 'Item Returned — Final Reward',
                'message' => "USC confirmed the return of '{$report->title}'. You received your final {$remaining} points (total category reward: {$total}).",
                'action' => 'recognition_points_awarded',
                'status' => 'pending',
                'recipient_id' => $finder->id,
                'recipient_role' => 'student',
                'subject_type' => ItemRelease::class,
                'subject_id' => $release->id,
                'data' => [
                    'report_id' => $report->id,
                    'release_id' => $release->id,
                    'award_type' => 'return_completion',
                    'points_awarded' => $remaining,
                    'configured_category_reward' => $total,
                    'recognition_points_balance' => $balance,
                ],
                'is_read' => false,
            ]);
        }

        return [
            'awarded' => $remaining,
            'balance' => $balance,
            'configured_category_reward' => $total,
        ];
    }


    private function createStudentNotification(
        array $payload
    ) {
        $notification =
            Notification::create(
                $payload
            );

        $studentId =
            (int)
            (
                $payload[
                    'student_id'
                ]
                ??
                $payload[
                    'recipient_id'
                ]
                ??
                0
            );

        if ($studentId > 0) {
            try {
                app(
                    PushNotificationService::class
                )->sendToStudent(
                    $studentId,

                    (string)
                    (
                        $payload[
                            'title'
                        ]
                        ??
                        'FindIt'
                    ),

                    (string)
                    (
                        $payload[
                            'message'
                        ]
                        ??
                        ''
                    ),

                    array_merge(
                        is_array(
                            $payload[
                                'data'
                            ]
                            ??
                            null
                        )
                            ? $payload[
                                'data'
                            ]
                            : [],
                        [
                            'notification_id' =>
                                $notification->id,

                            'type' =>
                                $payload[
                                    'type'
                                ]
                                ??
                                null,

                            'action' =>
                                $payload[
                                    'action'
                                ]
                                ??
                                null,
                        ]
                    )
                );
            } catch (
                \Throwable $exception
            ) {
                report(
                    $exception
                );
            }
        }

        return $notification;
    }


    private function recordAuditLog(
        $admin,
        string $action,
        string $subjectType,
        int $subjectId,
        string $subjectLabel,
        array $details = [],
        ?string $ipAddress = null
    ): void {
        $auditDetails = array_merge($details, [
            'module' => 'workflow',
        ]);

        $descriptionParts = [
            $subjectLabel,
            str_replace('_', ' ', $action),
        ];

        if (!empty($details['report_id'])) {
            $descriptionParts[] = 'Report ID: ' . $details['report_id'];
        }

        $description = ucfirst(implode(' - ', array_filter($descriptionParts)));

        $payload = [
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ip_address' => $ipAddress,
        ];

        if (Schema::hasColumn('audit_logs', 'actor_admin_id')) {
            $payload['actor_admin_id'] = $admin?->id;
        }

        if (Schema::hasColumn('audit_logs', 'actor_id')) {
            $payload['actor_id'] = $admin?->id;
        }

        if (Schema::hasColumn('audit_logs', 'actor_type')) {
            $payload['actor_type'] = Admin::class;
        }

        if (Schema::hasColumn('audit_logs', 'subject_label')) {
            $payload['subject_label'] = $subjectLabel;
        }

        if (Schema::hasColumn('audit_logs', 'description')) {
            $payload['description'] = $description;
        }

        if (Schema::hasColumn('audit_logs', 'details')) {
            $payload['details'] = $auditDetails;
        }

        AuditLog::create($payload);
    }

    public function storeEventFineConversion(Request $request)
    {
        $admin =
            Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated =
            $request->validate([
                'student_id' =>
                    'required|exists:students,id',

                'requested_points' =>
                    'required|integer|min:1',

                'review_notes' =>
                    'sometimes|nullable|string|max:1000',
            ]);

        $student =
            Student::findOrFail(
                $validated['student_id']
            );

        /*
        |--------------------------------------------------------------------------
        | ONLY ONE PENDING CONVERSION PER STUDENT
        |--------------------------------------------------------------------------
        */

        $pendingConversion =
            EventFineConversion::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'status',
                    'pending'
                )
                ->latest('id')
                ->first();

        if ($pendingConversion) {
            return response()->json([
                'status' => 'error',

                'message' =>
                    'This student already has a pending event-fine conversion request.',

                'data' =>
                    $pendingConversion,
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | OUTSTANDING FINES
        |--------------------------------------------------------------------------
        */

        $activeFines =
            EventFine::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->whereNotIn(
                    'status',
                    [
                        'paid',
                        'rejected',
                    ]
                )
                ->get();

        $totalOriginalAmount =
            (float)
            $activeFines->sum(
                fn ($fine) =>
                    (float)
                    (
                        $fine->original_amount
                        ??
                        $fine->amount
                        ??
                        0
                    )
            );

        $totalRemainingAmount =
            round(
                (float)
                $activeFines->sum(
                    fn ($fine) =>
                        (float)
                        (
                            $fine->remaining_amount
                            ?? 0
                        )
                ),
                2
            );

        if ($totalRemainingAmount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This student has no outstanding USC event fine to convert.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT AVAILABLE RECOGNITION POINTS
        |--------------------------------------------------------------------------
        |
        | Recognition credits are positive.
        | Approved event-fine conversions are negative debit transactions.
        |
        */

        $availablePoints =
            max(
                0,
                (int)
                PointTransaction::query()
                    ->where(
                        'student_id',
                        $student->id
                    )
                    ->sum('points')
            );

        if ($availablePoints <= 0) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This student does not have available recognition points.',
            ], 422);
        }

        $requestedPoints =
            min(
                (int)
                $validated['requested_points'],
                $availablePoints
            );

        /*
        |--------------------------------------------------------------------------
        | 100 POINTS = PHP 1.00
        |--------------------------------------------------------------------------
        |
        | Preserve the conversion rate already used by this project.
        |
        */

        $maxPointsNeededForFine =
            (int)
            ceil(
                $totalRemainingAmount
                *
                self::POINTS_PER_PESO
            );

        $convertedPoints =
            min(
                $requestedPoints,
                $maxPointsNeededForFine
            );

        if ($convertedPoints <= 0) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No recognition points can be applied to this fine balance.',
            ], 422);
        }

        $pesoValueConverted =
            round(
                min(
                    $totalRemainingAmount,
                    $convertedPoints
                    /
                    self::POINTS_PER_PESO
                ),
                2
            );

        $remainingBalance =
            round(
                max(
                    0,
                    $totalRemainingAmount
                    -
                    $pesoValueConverted
                ),
                2
            );

        $conversion =
            EventFineConversion::create([
                'student_id' =>
                    $student->id,

                /*
                |--------------------------------------------------------------------------
                | NULL means this request applies across the student's active fines.
                |--------------------------------------------------------------------------
                */

                'event_fine_id' =>
                    null,

                'total_original_amount' =>
                    $totalOriginalAmount,

                'converted_points' =>
                    $convertedPoints,

                'peso_value_converted' =>
                    $pesoValueConverted,

                'remaining_balance' =>
                    $remainingBalance,

                'status' =>
                    'pending',

                'requested_points' =>
                    $requestedPoints,

                'conversion_rate' =>
                    (float)
                    self::POINTS_PER_PESO,

                'credit_amount' =>
                    $pesoValueConverted,

                'review_notes' =>
                    $validated[
                        'review_notes'
                    ]
                    ?? null,

                'reviewed_by_admin_id' =>
                    null,

                'reviewed_at' =>
                    null,
            ]);

        $this->recordAuditLog(
            $admin,
            'event_fine_conversion_created',
            EventFineConversion::class,
            $conversion->id,
            'Event Fine Conversion',
            [
                'student_id' =>
                    $student->id,

                'requested_points' =>
                    $requestedPoints,

                'converted_points' =>
                    $convertedPoints,

                'credit_amount' =>
                    $pesoValueConverted,

                'fine_balance_before' =>
                    $totalRemainingAmount,

                'fine_balance_after_if_approved' =>
                    $remainingBalance,

                'available_points_before' =>
                    $availablePoints,

                'conversion_rate' =>
                    self::POINTS_PER_PESO,
            ],
            $request->ip()
        );

        return response()->json([
            'status' => 'success',

            'message' =>
                'Fine balance conversion request created and is waiting for USC approval.',

            'data' => [
                'conversion' =>
                    $conversion,

                'summary' => [
                    'total_recognition_points_earned' =>
                        (int)
                        PointTransaction::query()
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->where(
                                'direction',
                                'credit'
                            )
                            ->where(
                                'points',
                                '>',
                                0
                            )
                            ->sum('points'),

                    'points_already_used' =>
                        (int)
                        PointTransaction::query()
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->where(
                                'direction',
                                'debit'
                            )
                            ->get()
                            ->sum(
                                fn ($transaction) =>
                                    abs(
                                        (int)
                                        $transaction->points
                                    )
                            ),

                    'remaining_available_points' =>
                        $availablePoints,

                    'event_fine_amount' =>
                        $totalRemainingAmount,

                    'points_requested' =>
                        $requestedPoints,

                    'points_to_convert' =>
                        $convertedPoints,

                    'fine_credit' =>
                        $pesoValueConverted,

                    'remaining_fine_if_approved' =>
                        $remainingBalance,

                    'conversion_rate' =>
                        self::POINTS_PER_PESO,
                ],
            ],
        ], 201);
    }


    public function approveEventFineConversion($id)
    {
        $admin =
            Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Compatibility:
        | ID may be the conversion ID, or an older frontend may send student ID.
        |--------------------------------------------------------------------------
        */

        $conversion =
            EventFineConversion::query()
                ->whereKey($id)
                ->where(
                    'status',
                    'pending'
                )
                ->first();

        if (!$conversion) {
            $conversion =
                EventFineConversion::query()
                    ->where(
                        'student_id',
                        $id
                    )
                    ->where(
                        'status',
                        'pending'
                    )
                    ->latest('id')
                    ->first();
        }

        if (!$conversion) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No pending event-fine conversion request was found.',
            ], 404);
        }

        try {
            return DB::transaction(
                function () use (
                    $admin,
                    $conversion
                ) {
                    $conversion =
                        EventFineConversion::query()
                            ->whereKey(
                                $conversion->id
                            )
                            ->where(
                                'status',
                                'pending'
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $student =
                        $conversion
                            ->student()
                            ->lockForUpdate()
                            ->firstOrFail();

                    $activeFines =
                        EventFine::query()
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->whereNotIn(
                                'status',
                                [
                                    'paid',
                                    'rejected',
                                ]
                            )
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->get();

                    $currentFineBalance =
                        round(
                            (float)
                            $activeFines->sum(
                                fn ($fine) =>
                                    (float)
                                    (
                                        $fine
                                            ->remaining_amount
                                        ?? 0
                                    )
                            ),
                            2
                        );

                    if ($currentFineBalance <= 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'The student no longer has an outstanding event fine.',
                        ], 422);
                    }

                    $availablePoints =
                        max(
                            0,
                            (int)
                            PointTransaction::query()
                                ->where(
                                    'student_id',
                                    $student->id
                                )
                                ->sum('points')
                        );

                    $pointsToUse =
                        (int)
                        $conversion
                            ->converted_points;

                    if (
                        $pointsToUse <= 0 ||
                        $availablePoints <
                            $pointsToUse
                    ) {
                        return response()->json([
                            'status' => 'error',

                            'message' =>
                                'The student no longer has enough available recognition points for this conversion. Reject this request and create a new one.',
                        ], 409);
                    }

                    $creditAmount =
                        round(
                            min(
                                $currentFineBalance,
                                $pointsToUse
                                /
                                self::POINTS_PER_PESO
                            ),
                            2
                        );

                    if ($creditAmount <= 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'The calculated fine credit is zero.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | APPLY CREDIT TO ACTIVE FINES, OLDEST FIRST
                    |--------------------------------------------------------------------------
                    */

                    $remainingToApply =
                        $creditAmount;

                    $affectedFines = [];

                    foreach (
                        $activeFines
                        as $fine
                    ) {
                        if (
                            $remainingToApply <= 0
                        ) {
                            break;
                        }

                        $fineRemaining =
                            max(
                                0,
                                (float)
                                (
                                    $fine
                                        ->remaining_amount
                                    ?? 0
                                )
                            );

                        if ($fineRemaining <= 0) {
                            continue;
                        }

                        $deduction =
                            round(
                                min(
                                    $fineRemaining,
                                    $remainingToApply
                                ),
                                2
                            );

                        if ($deduction <= 0) {
                            continue;
                        }

                        $oldPaid =
                            (float)
                            (
                                $fine->paid_amount
                                ?? $fine->paid
                                ?? 0
                            );

                        $newPaid =
                            round(
                                $oldPaid
                                +
                                $deduction,
                                2
                            );

                        $newRemaining =
                            round(
                                max(
                                    0,
                                    $fineRemaining
                                    -
                                    $deduction
                                ),
                                2
                            );

                        $newStatus =
                            $newRemaining <= 0
                                ? 'paid'
                                : 'partially paid';

                        $fine->update([
                            'paid_amount' =>
                                $newPaid,

                            'paid' =>
                                $newPaid,

                            'remaining_amount' =>
                                $newRemaining,

                            'status' =>
                                $newStatus,
                        ]);

                        $affectedFines[] = [
                            'event_fine_id' =>
                                $fine->id,

                            'credit_applied' =>
                                $deduction,

                            'remaining_amount' =>
                                $newRemaining,

                            'status' =>
                                $newStatus,
                        ];

                        /*
                        |--------------------------------------------------------------------------
                        | ARCHIVE FULLY PAID FINE
                        |--------------------------------------------------------------------------
                        */

                        if ($newRemaining <= 0) {
                            EventFineArchive::updateOrCreate(
                                [
                                    'event_fine_id' =>
                                        $fine->id,
                                ],
                                [
                                    'student_id' =>
                                        $fine
                                            ->student_id,

                                    'student_name' =>
                                        $fine
                                            ->student_name,

                                    'school_id' =>
                                        $fine
                                            ->school_id,

                                    'event_name' =>
                                        $fine
                                            ->event_name,

                                    'original_amount' =>
                                        $fine
                                            ->original_amount,

                                    'amount_to_pay' =>
                                        $fine
                                            ->amount_to_pay,

                                    'paid_amount' =>
                                        $newPaid,

                                    'remaining_amount' =>
                                        0,

                                    'status' =>
                                        'paid',

                                    'reason' =>
                                        $fine->reason,

                                    'paid_at' =>
                                        now(),
                                ]
                            );
                        }

                        $remainingToApply =
                            round(
                                $remainingToApply
                                -
                                $deduction,
                                2
                            );
                    }

                    $actualCreditApplied =
                        round(
                            $creditAmount
                            -
                            max(
                                0,
                                $remainingToApply
                            ),
                            2
                        );

                    $actualPointsUsed =
                        (int)
                        round(
                            $actualCreditApplied
                            *
                            self::POINTS_PER_PESO
                        );

                    if ($actualPointsUsed <= 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'No fine balance was reduced.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DEDUCT POINTS
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    | Debit points are stored as NEGATIVE values so all existing
                    | balance calculations using SUM(points) stay correct.
                    |
                    */

                    $existingDebit =
                        PointTransaction::query()
                            ->where(
                                'student_id',
                                $student->id
                            )
                            ->where(
                                'type',
                                'event_fine_conversion'
                            )
                            ->where(
                                'reference_type',
                                EventFineConversion::class
                            )
                            ->where(
                                'reference_id',
                                $conversion->id
                            )
                            ->exists();

                    if ($existingDebit) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'Recognition points were already deducted for this conversion.',
                        ], 409);
                    }

                    PointTransaction::create([
                        'student_id' =>
                            $student->id,

                        'type' =>
                            'event_fine_conversion',

                        'direction' =>
                            'debit',

                        'points' =>
                            -$actualPointsUsed,

                        'amount' =>
                            $actualCreditApplied,

                        'description' =>
                            'Recognition points converted into USC event-fine credit.',

                        'notes' =>
                            'Approved by USC administrator.',

                        'reference_type' =>
                            EventFineConversion::class,

                        'reference_id' =>
                            $conversion->id,

                        'processed_by_admin_id' =>
                            $admin->id,

                        'created_by_admin_id' =>
                            $admin->id,
                    ]);

                    $remainingPoints =
                        max(
                            0,
                            (int)
                            PointTransaction::query()
                                ->where(
                                    'student_id',
                                    $student->id
                                )
                                ->sum('points')
                        );

                    $remainingFineBalance =
                        round(
                            (float)
                            EventFine::query()
                                ->where(
                                    'student_id',
                                    $student->id
                                )
                                ->whereNotIn(
                                    'status',
                                    [
                                        'paid',
                                        'rejected',
                                    ]
                                )
                                ->sum(
                                    'remaining_amount'
                                ),
                            2
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE STUDENT RECOGNITION SUMMARY
                    |--------------------------------------------------------------------------
                    */

                    StudentRecognitionRecord::updateOrCreate(
                        [
                            'student_id' =>
                                $student->id,
                        ],
                        [
                            'recognition_points' =>
                                $remainingPoints,

                            'peso_value' =>
                                round(
                                    $remainingPoints
                                    /
                                    self::POINTS_PER_PESO,
                                    2
                                ),

                            'reward_count' =>
                                (int)
                                (
                                    $student
                                        ->reward_count
                                    ?? 0
                                ),

                            'report_count' =>
                                $student
                                    ->reports()
                                    ->count(),
                        ]
                    );

                    $conversion->update([
                        'converted_points' =>
                            $actualPointsUsed,

                        'peso_value_converted' =>
                            $actualCreditApplied,

                        'credit_amount' =>
                            $actualCreditApplied,

                        'remaining_balance' =>
                            $remainingFineBalance,

                        'status' =>
                            'approved',

                        'reviewed_by_admin_id' =>
                            $admin->id,

                        'reviewed_at' =>
                            now(),

                        'review_notes' =>
                            $conversion
                                ->review_notes
                            ??
                            'Approved by USC administrator.',
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | STUDENT NOTIFICATION
                    |--------------------------------------------------------------------------
                    */

                    $this->createStudentNotification([
                        'student_id' =>
                            $student->id,

                        'type' =>
                            'event_fine_conversion_approved',

                        'title' =>
                            'Event Fine Conversion Approved',

                        'message' =>
                            "{$actualPointsUsed} recognition point(s) were converted into PHP "
                            .
                            number_format(
                                $actualCreditApplied,
                                2
                            )
                            .
                            " of USC event-fine credit. You have {$remainingPoints} recognition point(s) remaining.",

                        'action' =>
                            'event_fine_conversion_approved',

                        'status' =>
                            'pending',

                        'recipient_id' =>
                            $student->id,

                        'recipient_role' =>
                            'student',

                        'subject_type' =>
                            EventFineConversion::class,

                        'subject_id' =>
                            $conversion->id,

                        'data' => [
                            'conversion_id' =>
                                $conversion->id,

                            'points_used' =>
                                $actualPointsUsed,

                            'fine_credit' =>
                                $actualCreditApplied,

                            'remaining_points' =>
                                $remainingPoints,

                            'remaining_fine_balance' =>
                                $remainingFineBalance,

                            'conversion_rate' =>
                                self::POINTS_PER_PESO,

                            'approved_by_admin_id' =>
                                $admin->id,

                            'approved_at' =>
                                now()
                                    ->toIso8601String(),
                        ],

                        'is_read' =>
                            false,
                    ]);

                    $this->recordAuditLog(
                        $admin,
                        'event_fine_conversion_approved',
                        EventFineConversion::class,
                        $conversion->id,
                        'Event Fine Conversion',
                        [
                            'student_id' =>
                                $student->id,

                            'points_used' =>
                                $actualPointsUsed,

                            'fine_credit' =>
                                $actualCreditApplied,

                            'remaining_points' =>
                                $remainingPoints,

                            'remaining_fine_balance' =>
                                $remainingFineBalance,

                            'conversion_rate' =>
                                self::POINTS_PER_PESO,

                            'affected_fines' =>
                                $affectedFines,
                        ],
                        request()->ip()
                    );

                    return response()->json([
                        'status' => 'success',

                        'message' =>
                            'Event fine conversion approved successfully.',

                        'data' => [
                            'conversion' =>
                                $conversion->fresh(),

                            'summary' => [
                                'points_used' =>
                                    $actualPointsUsed,

                                'fine_credit' =>
                                    $actualCreditApplied,

                                'remaining_available_points' =>
                                    $remainingPoints,

                                'remaining_fine_balance' =>
                                    $remainingFineBalance,

                                'conversion_rate' =>
                                    self::POINTS_PER_PESO,

                                'approved_by_admin_id' =>
                                    $admin->id,

                                'approved_at' =>
                                    now()
                                        ->toIso8601String(),
                            ],

                            'affected_fines' =>
                                $affectedFines,
                        ],
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Approve event fine conversion failed',
                [
                    'conversion_id' =>
                        $conversion->id
                        ?? $id,

                    'message' =>
                        $exception
                            ->getMessage(),
                ]
            );

            return response()->json([
                'status' => 'error',
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    public function rejectEventFineConversion($id)
    {
        $admin =
            Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $conversion =
            EventFineConversion::query()
                ->whereKey($id)
                ->where(
                    'status',
                    'pending'
                )
                ->first();

        if (!$conversion) {
            $conversion =
                EventFineConversion::query()
                    ->where(
                        'student_id',
                        $id
                    )
                    ->where(
                        'status',
                        'pending'
                    )
                    ->latest('id')
                    ->first();
        }

        if (!$conversion) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'No pending event-fine conversion request was found.',
            ], 404);
        }

        $conversion->update([
            'status' =>
                'rejected',

            'reviewed_by_admin_id' =>
                $admin->id,

            'reviewed_at' =>
                now(),

            'review_notes' =>
                $conversion
                    ->review_notes
                ??
                'Rejected by USC administrator.',
        ]);

        $student =
            $conversion
                ->student()
                ->first();

        if ($student) {
            $this->createStudentNotification([
                'student_id' =>
                    $student->id,

                'type' =>
                    'event_fine_conversion_rejected',

                'title' =>
                    'Event Fine Conversion Rejected',

                'message' =>
                    'USC rejected the recognition-point conversion request. No recognition points were deducted.',

                'action' =>
                    'event_fine_conversion_rejected',

                'status' =>
                    'pending',

                'recipient_id' =>
                    $student->id,

                'recipient_role' =>
                    'student',

                'subject_type' =>
                    EventFineConversion::class,

                'subject_id' =>
                    $conversion->id,

                'data' => [
                    'conversion_id' =>
                        $conversion->id,

                    'requested_points' =>
                        (int)
                        (
                            $conversion
                                ->requested_points
                            ?? 0
                        ),

                    'points_deducted' =>
                        0,

                    'reviewed_by_admin_id' =>
                        $admin->id,

                    'reviewed_at' =>
                        now()
                            ->toIso8601String(),
                ],

                'is_read' =>
                    false,
            ]);
        }

        $this->recordAuditLog(
            $admin,
            'event_fine_conversion_rejected',
            EventFineConversion::class,
            $conversion->id,
            'Event Fine Conversion',
            [
                'student_id' =>
                    $conversion
                        ->student_id,

                'requested_points' =>
                    (int)
                    (
                        $conversion
                            ->requested_points
                        ?? 0
                    ),

                'points_deducted' =>
                    0,
            ],
            request()->ip()
        );

        return response()->json([
            'status' => 'success',

            'message' =>
                'Event fine conversion rejected. No recognition points were deducted.',

            'data' =>
                $conversion->fresh(),
        ]);
    }


    public function recordEventFinePayment(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

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

        return response()->json([
            'status' => 'success',
            'message' => 'Payment recorded.',
            'data' => $fine->fresh(),
        ]);
    }
}
