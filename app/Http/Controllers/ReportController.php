<?php

namespace App\Http\Controllers;

use App\Models\ClaimReview;
use App\Models\ItemVerification;
use App\Models\ItemCategory;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\PointRule;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\Reward;
use App\Models\SurrenderRequest;
use App\Models\StudentRecognitionRecord;
use App\Services\PotentialMatchService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    private const POINTS_PER_PESO = 100;

    private const FOUND_REPORT_SUBMISSION_POINTS = 1;

    /*
    |--------------------------------------------------------------------------
    | STUDENT MOBILE CATEGORY CHOICES
    |--------------------------------------------------------------------------
    |
    | Only active categories configured through USC's Point Rules appear in
    | the mobile Lost/Found forms. This public endpoint is read-only.
    |
    */

    public function categories()
    {
        $rules = PointRule::query()->get();

        $names = $rules->map(function (PointRule $rule) {
            return mb_strtolower(trim((string) ($rule->category_name ?: $rule->item_category ?: '')));
        })->filter()->unique()->values()->all();

        $ids = Schema::hasColumn('point_rules', 'category_id')
            ? $rules->pluck('category_id')->filter()->unique()->values()->all()
            : [];

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->where(function ($query) use ($ids, $names) {
                if ($ids) {
                    $query->whereIn('id', $ids);
                }
                if ($names) {
                    $query->orWhereIn(DB::raw('LOWER(name)'), $names);
                }
                if (!$ids && !$names) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ])->header('Cache-Control', 'no-store, private');
    }

    /*
    |--------------------------------------------------------------------------
    | FOUND TIME INPUT
    |--------------------------------------------------------------------------
    |
    | Some mobile versions may send found_time, timeFound, or a value inside
    | metadata. Use the canonical time_found field without altering other data.
    |
    */

    private function mergeFoundTimeInput(Request $request): void
    {
        if ($request->filled('time_found')) {
            return;
        }

        foreach (['found_time', 'timeFound', 'metadata.time_found', 'metadata.found_time'] as $field) {
            if ($request->filled($field)) {
                $request->merge(['time_found' => $request->input($field)]);
                return;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE VALIDATED FOUND TIME
    |--------------------------------------------------------------------------
    |
    | Store a real, supplied Time Found as HH:MM:SS for the reports TIME column.
    | Never substitute created_at or the report submission time.
    |
    */

    private function normalizeFoundTime(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        foreach (['H:i', 'H:i:s', 'g:i A', 'h:i A', 'g:i a', 'h:i a'] as $format) {
            $time = \DateTimeImmutable::createFromFormat('!' . $format, trim($value));
            $errors = \DateTimeImmutable::getLastErrors();

            if ($time !== false && (
                $errors === false ||
                ($errors['warning_count'] === 0 && $errors['error_count'] === 0)
            )) {
                return $time->format('H:i:s');
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC REPORT FEED
    |--------------------------------------------------------------------------
    |
    | PUBLIC LOST:
    |
    | - approved
    | - potential_match_found
    | - under_claim_review
    |
    | PUBLIC FOUND:
    |
    | - verified
    | - potential_match_found
    | - under_claim_review
    | - ready_for_release
    |
    | Pending, rejected, completed and archived reports are hidden.
    |
    */

    public function index(Request $request)
    {
        $user = $request->user();

        $query = Report::query();

        /*
        |--------------------------------------------------------------------------
        | FILTER: TYPE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('type')) {
            $type = strtolower(
                trim(
                    (string) $request->input('type')
                )
            );

            if (
                in_array(
                    $type,
                    [
                        Report::TYPE_LOST,
                        Report::TYPE_FOUND,
                    ],
                    true
                )
            ) {
                $query->where(
                    'type',
                    $type
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER: STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER: CATEGORY
        |--------------------------------------------------------------------------
        |
        | Supports:
        |
        | category=1
        |
        | or
        |
        | category=Electronics
        |
        */

        if ($request->filled('category')) {
            $category =
                trim(
                    (string) $request->input('category')
                );

            if (is_numeric($category)) {
                $query->where(
                    'category_id',
                    (int) $category
                );
            } else {
                $query->whereRaw(
                    'LOWER(category) = ?',
                    [
                        strtolower($category),
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MY REPORTS THROUGH /reports?mine=1
        |--------------------------------------------------------------------------
        |
        | Owner may see ALL statuses.
        |
        */

        if ($request->boolean('mine')) {
            $query->where(
                'student_id',
                $user->id
            );
        } else {
            /*
            |--------------------------------------------------------------------------
            | PUBLIC STATUS FILTER
            |--------------------------------------------------------------------------
            */

            $query->publiclyVisible();

            /*
            |--------------------------------------------------------------------------
            | PUBLICATION FILTER
            |--------------------------------------------------------------------------
            |
            | Support the current VisiblePostedItem workflow.
            |
            | Also preserve older records that used published_at.
            |
            */

            $query->where(
                function ($publicQuery) {
                    $publicQuery
                        ->whereHas(
                            'visiblePost',
                            function (
                                $visiblePostQuery
                            ) {
                                $visiblePostQuery->where(
                                    'is_visible',
                                    true
                                );
                            }
                        )
                        ->orWhere(
                            function (
                                $legacyQuery
                            ) {
                                $legacyQuery
                                    ->whereDoesntHave(
                                        'visiblePost'
                                    )
                                    ->whereNotNull(
                                        'published_at'
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search =
                strtolower(
                    trim(
                        (string) $request->input(
                            'search'
                        )
                    )
                );

            $query->where(
                function ($searchQuery) use ($search) {
                    $searchQuery
                        ->whereRaw(
                            'LOWER(title) LIKE ?',
                            [
                                "%{$search}%",
                            ]
                        )
                        ->orWhereRaw(
                            'LOWER(description) LIKE ?',
                            [
                                "%{$search}%",
                            ]
                        )
                        ->orWhereRaw(
                            'LOWER(location) LIKE ?',
                            [
                                "%{$search}%",
                            ]
                        )
                        ->orWhereRaw(
                            'LOWER(category) LIKE ?',
                            [
                                "%{$search}%",
                            ]
                        )
                        ->orWhereRaw(
                            'LOWER(color) LIKE ?',
                            [
                                "%{$search}%",
                            ]
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $perPage =
            min(
                max(
                    $request->integer(
                        'per_page',
                        20
                    ),
                    1
                ),
                100
            );

        $reports =
            $query
                ->with([
                    'student',
                    'images',
                    'visiblePost',
                ])
                ->latest()
                ->paginate(
                    $perPage
                );

        /*
        |--------------------------------------------------------------------------
        | NEVER EXPOSE PRIVATE IDENTIFYING DETAILS PUBLICLY
        |--------------------------------------------------------------------------
        */

        $reports
            ->getCollection()
            ->each(
                function ($report) {
                    $report->makeHidden(
                        'private_identifying_details'
                    );
                }
            );

        return response()->json([
            'status' =>
                'success',

            'data' =>
                $reports,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MY REPORTS
    |--------------------------------------------------------------------------
    |
    | This intentionally returns ALL statuses belonging to the student:
    |
    | pending
    | approved
    | rejected
    | verified
    | matched
    | completed
    | archived
    |
    */

    public function mine(Request $request)
    {
        $user =
            $request->user();

        $reports =
            Report::query()
                ->where(
                    'student_id',
                    $user->id
                )
                ->with([
                    'student',
                    'images',
                    'visiblePost',
                    'surrenderRequest',
                    'itemVerification',
                    'claimReview',
                ])
                ->latest()
                ->get();

        $reports->each(
            function ($report) {
                $report->makeHidden(
                    'private_identifying_details'
                );
            }
        );

        return response()->json([
            'status' =>
                'success',

            'data' =>
                $reports,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE REPORT
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        $id
    ) {
        $report =
            Report::with([
                'student',
                'images',
                'visiblePost',
            ])
                ->findOrFail(
                    $id
                );

        $user =
            $request->user();

        $isOwner =
            (int) $report->student_id ===
            (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Non-owner may only open a PUBLIC report.
        |--------------------------------------------------------------------------
        */

        if (
            !$isOwner &&
            !$report->isPubliclyVisible()
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Report not found.',
            ], 404);
        }

        $report->makeHidden(
            'private_identifying_details'
        );

        return response()->json([
            'status' =>
                'success',

            'data' =>
                $report,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SUBMIT CLAIM
    |--------------------------------------------------------------------------
    |
    | Final workflow:
    |
    | Claims are for FOUND items that have already been verified.
    |
    | Claimable statuses:
    |
    | verified
    | potential_match_found
    |
    */

    public function submitClaim(
        Request $request,
        $id
    ) {
        $user =
            $request->user();

        $validated =
            $request->validate([
                'proof_description' =>
                    'required|string|max:3000',

                'proof_photo' =>
                    'nullable|file|image|max:5120',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $user,
                    $validated
                ) {
                    $report =
                        Report::query()
                            ->where(
                                'id',
                                $id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | CLAIMS ARE ONLY FOR VERIFIED FOUND ITEMS
                    |--------------------------------------------------------------------------
                    |
                    | under_claim_review remains claimable so another student may still submit
                    | ownership proof while USC is reviewing a different pending claim.
                    |
                    */

                    $claimableStatuses = [
                        Report::STATUS_VERIFIED,
                        Report::STATUS_POTENTIAL_MATCH_FOUND,
                        Report::STATUS_UNDER_CLAIM_REVIEW,
                    ];

                    if (
                        !$report->isFound() ||
                        !in_array(
                            $report->status,
                            $claimableStatuses,
                            true
                        )
                    ) {
                        return response()->json([
                            'status' =>
                                'error',

                            'message' =>
                                'Only verified found items that are still open for claims can be claimed.',
                        ], 422);
                    }

                    if (
                        (int) $report->student_id ===
                        (int) $user->id
                    ) {
                        return response()->json([
                            'status' =>
                                'error',

                            'message' =>
                                'You cannot submit a claim on your own found-item report.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | STOP NEW CLAIMS AFTER USC HAS APPROVED AN OWNER
                    |--------------------------------------------------------------------------
                    */

                    $approvedClaimExists =
                        ClaimReview::query()
                            ->where(
                                'report_id',
                                $report->id
                            )
                            ->where(
                                'review_status',
                                'approved'
                            )
                            ->exists();

                    if ($approvedClaimExists) {
                        return response()->json([
                            'status' =>
                                'error',

                            'message' =>
                                'This item already has an approved claimant and is no longer accepting claims.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT DUPLICATE ACTIVE CLAIM BY THE SAME STUDENT
                    |--------------------------------------------------------------------------
                    */

                    $existingClaim =
                        ClaimReview::query()
                            ->where(
                                'report_id',
                                $report->id
                            )
                            ->where(
                                'claimant_student_id',
                                $user->id
                            )
                            ->whereIn(
                                'review_status',
                                [
                                    'pending',
                                    'additional_information_required',
                                    'approved',
                                ]
                            )
                            ->exists();

                    if ($existingClaim) {
                        return response()->json([
                            'status' =>
                                'error',

                            'message' =>
                                'You already have an active claim for this item.',
                        ], 422);
                    }

                    $evidence = [
                        'proof_description' =>
                            trim(
                                $validated[
                                    'proof_description'
                                ]
                            ),
                    ];

                    if (
                        $request->hasFile(
                            'proof_photo'
                        )
                    ) {
                        $evidence[
                            'proof_photo'
                        ] =
                            $request
                                ->file(
                                    'proof_photo'
                                )
                                ->store(
                                    'claim-evidence',
                                    'public'
                                );
                    }

                    $claim =
                        ClaimReview::create([
                            'report_id' =>
                                $report->id,

                            'claimant_student_id' =>
                                $user->id,

                            'review_status' =>
                                'pending',

                            'evidence' =>
                                $evidence,
                        ]);

                    $report->update([
                        'status' =>
                            Report::STATUS_UNDER_CLAIM_REVIEW,
                    ]);

                    Notification::create([
                        'type' =>
                            'claim_submitted',

                        'title' =>
                            'New Ownership Claim',

                        'message' =>
                            "A student submitted an ownership claim for '{$report->title}'.",

                        'action' =>
                            'claim_submitted',

                        'status' =>
                            'pending',

                        'recipient_role' =>
                            'admin',

                        'subject_type' =>
                            ClaimReview::class,

                        'subject_id' =>
                            $claim->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'claim_id' =>
                                $claim->id,

                            'claimant_student_id' =>
                                $user->id,
                        ],

                        'is_read' =>
                            false,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | NOTIFY THE FINDER ABOUT THE NEW CLAIM
                    |--------------------------------------------------------------------------
                    |
                    | Do not expose the claimant's private ownership evidence.
                    | USC remains responsible for reviewing the proof.
                    |
                    */

                    if (
                        $report->student_id &&
                        (int) $report->student_id !==
                            (int) $user->id
                    ) {
                        $this->createStudentNotification([
                            'student_id' =>
                                $report->student_id,

                            'type' =>
                                'new_claim_request',

                            'title' =>
                                'New Claim Request',

                            'message' =>
                                "A student submitted an ownership claim for your found item '{$report->title}'. USC will review the claim and verify the ownership proof.",

                            'action' =>
                                'new_claim_request',

                            'status' =>
                                'pending',

                            'recipient_id' =>
                                $report->student_id,

                            'recipient_role' =>
                                'student',

                            'subject_type' =>
                                ClaimReview::class,

                            'subject_id' =>
                                $claim->id,

                            'data' => [
                                'report_id' =>
                                    $report->id,

                                'claim_id' =>
                                    $claim->id,

                                'private_evidence_exposed' =>
                                    false,
                            ],

                            'is_read' =>
                                false,
                        ]);
                    }


                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Ownership claim submitted successfully. USC will review your proof.',

                        'data' =>
                            $claim->load([
                                'report.images',
                                'claimantStudent',
                            ]),
                    ], 201);
                }
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MY OWNERSHIP CLAIMS
    |--------------------------------------------------------------------------
    */

    public function myClaims(
        Request $request
    ) {
        $user =
            $request->user();

        $claims =
            ClaimReview::query()
                ->where(
                    'claimant_student_id',
                    $user->id
                )
                ->with([
                    'report.images',
                    'report.categoryRelation',
                    'reviewedByAdmin',
                ])
                ->latest()
                ->get();

        return response()->json([
            'status' =>
                'success',

            'data' =>
                $claims,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SUBMIT ADDITIONAL CLAIM INFORMATION
    |--------------------------------------------------------------------------
    |
    | Used after USC changes a claim to additional_information_required.
    | The claimant can update their ownership proof and optionally attach a new
    | proof photo. After resubmission, the claim returns to pending review.
    |
    */

    public function submitAdditionalClaimInformation(
        Request $request,
        $id
    ) {
        $user =
            $request->user();

        $validated =
            $request->validate([
                'proof_description' =>
                    'required|string|max:3000',

                'proof_photo' =>
                    'nullable|file|image|max:5120',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $id,
                    $user,
                    $validated
                ) {
                    $claim =
                        ClaimReview::query()
                            ->where(
                                'id',
                                $id
                            )
                            ->where(
                                'claimant_student_id',
                                $user->id
                            )
                            ->where(
                                'review_status',
                                'additional_information_required'
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $report =
                        $claim
                            ->report()
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        !$report->isFound() ||
                        $report->status ===
                            Report::STATUS_READY_FOR_RELEASE ||
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
                            'status' =>
                                'error',

                            'message' =>
                                'This claim can no longer receive additional information.',
                        ], 422);
                    }

                    $evidence =
                        is_array(
                            $claim->evidence
                        )
                            ? $claim->evidence
                            : [];

                    $evidence[
                        'proof_description'
                    ] =
                        trim(
                            $validated[
                                'proof_description'
                            ]
                        );

                    $evidence[
                        'additional_information_submitted_at'
                    ] =
                        now()->toIso8601String();

                    if (
                        $request->hasFile(
                            'proof_photo'
                        )
                    ) {
                        $evidence[
                            'proof_photo'
                        ] =
                            $request
                                ->file(
                                    'proof_photo'
                                )
                                ->store(
                                    'claim-evidence',
                                    'public'
                                );
                    }

                    $claim->update([
                        'review_status' =>
                            'pending',

                        'evidence' =>
                            $evidence,

                        'reviewed_by_admin_id' =>
                            null,

                        'reviewed_at' =>
                            null,
                    ]);

                    if (
                        $report->status !==
                        Report::STATUS_UNDER_CLAIM_REVIEW
                    ) {
                        $report->update([
                            'status' =>
                                Report::STATUS_UNDER_CLAIM_REVIEW,
                        ]);
                    }

                    Notification::create([
                        'type' =>
                            'claim_information_submitted',

                        'title' =>
                            'Claim Information Updated',

                        'message' =>
                            "The claimant submitted additional ownership proof for '{$report->title}'.",

                        'action' =>
                            'claim_submitted',

                        'status' =>
                            'pending',

                        'recipient_role' =>
                            'admin',

                        'subject_type' =>
                            ClaimReview::class,

                        'subject_id' =>
                            $claim->id,

                        'data' => [
                            'report_id' =>
                                $report->id,

                            'claim_id' =>
                                $claim->id,
                        ],

                        'is_read' =>
                            false,
                    ]);

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Additional ownership proof submitted. Your claim is back under USC review.',

                        'data' =>
                            $claim->fresh([
                                'report.images',
                                'claimantStudent',
                            ]),
                    ]);
                }
            );
        } catch (\Throwable $exception) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    $exception->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE REPORT
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {
        $user =
            $request->user();

        $this->mergeFoundTimeInput($request);

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate(
                [
                    'type' =>
                        'required|in:lost,found',

                    'title' =>
                        'required|string|max:255',

                    'description' =>
                        'required|string|max:5000',

                    'private_identifying_details' =>
                        'required|string|max:5000',

                    'category_id' =>
                        'nullable|exists:item_categories,id',

                    /*
                    |--------------------------------------------------------------------------
                    | Mobile currently sends:
                    |
                    | metadata[category]
                    |--------------------------------------------------------------------------
                    */

                    'metadata' =>
                        'nullable|array',

                    'metadata.category' =>
                        'nullable|string|max:255',

                    'color' =>
                        'nullable|string|max:255',

                    'location' =>
                        'required|string|max:255',

                    /*
                    |--------------------------------------------------------------------------
                    | Never accept a future date.
                    |--------------------------------------------------------------------------
                    */

                    'date_occurred' =>
                        'required|date|before_or_equal:' . now('Asia/Manila')->toDateString(),

                    // Optional for older mobile clients; never invent the time.
                    'time_found' =>
                        'nullable|date_format:H:i,H:i:s,g:i A,h:i A',

                    /*
                    |--------------------------------------------------------------------------
                    | Preserve compatibility with older requests.
                    |--------------------------------------------------------------------------
                    */

                    'event_date' =>
                        'nullable|date',

                    'reward_amount' =>
                        'nullable|numeric|min:0',

                    'reward_type' =>
                        'nullable|in:reward,fine',

                    'image' =>
                        'nullable|string|max:255',

                    /*
                    |--------------------------------------------------------------------------
                    | Reports require 1-3 photos.
                    |--------------------------------------------------------------------------
                    */

                    'images' =>
                        'required|array|min:1|max:3',

                    'images.*' =>
                        'required|file|image|max:5120',
                ],
                [
                    'date_occurred.before_or_equal' =>
                        'The report date cannot be in the future.',

                    'time_found.date_format' =>
                        'Time Found must be a valid time, such as 14:35 or 2:35 PM.',

                    'images.required' =>
                        'At least one image is required to submit a report.',

                    'images.min' =>
                        'At least one image is required to submit a report.',

                    'images.max' =>
                        'You can upload up to 3 images.',

                    'images.*.image' =>
                        'All files must be valid images.',

                    'images.*.max' =>
                        'Images must not exceed 5MB each.',
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | INITIAL STATUS
        |--------------------------------------------------------------------------
        |
        | LOST:
        |
        | pending_approval
        |
        | FOUND:
        |
        | pending_surrender
        |
        | IMPORTANT:
        |
        | Status comes from Laravel.
        | Mobile cannot choose it.
        |
        */

        $status =
            Report::initialStatusForType(
                $validated['type']
            );

        /*
        |--------------------------------------------------------------------------
        | CATEGORY NAME
        |--------------------------------------------------------------------------
        */

        $categoryName =
            $validated['metadata']['category']
            ??
            null;

        /*
        |--------------------------------------------------------------------------
        | CREATE REPORT
        |--------------------------------------------------------------------------
        |
        | Build explicitly instead of array_merge($validated) so unrelated
        | request values cannot accidentally become report columns.
        |
        */

        $report =
            Report::create([
                'student_id' =>
                    $user->id,

                'type' =>
                    $validated['type'],

                'title' =>
                    trim(
                        $validated['title']
                    ),

                'description' =>
                    trim(
                        $validated['description']
                    ),

                'private_identifying_details' =>
                    trim(
                        $validated[
                            'private_identifying_details'
                        ]
                    ),

                'category_id' =>
                    $validated[
                        'category_id'
                    ]
                    ??
                    null,

                'category' =>
                    $categoryName,

                'color' =>
                    isset(
                        $validated['color']
                    )
                        ? trim(
                            $validated['color']
                        )
                        : null,

                'location' =>
                    trim(
                        $validated['location']
                    ),

                'date_occurred' =>
                    $validated[
                        'date_occurred'
                    ],

                'time_found' =>
                    $this->normalizeFoundTime($validated['time_found'] ?? null),

                'metadata' =>
                    $validated['metadata']
                    ??
                    [],

                'status' =>
                    $status,

                'requires_images' =>
                    true,
            ]);

        /*
        |--------------------------------------------------------------------------
        | STORE REPORT PHOTOS
        |--------------------------------------------------------------------------
        */

        $isPrimary =
            true;

        foreach (
            $request->file(
                'images',
                []
            )
            as
            $imageFile
        ) {
            $path =
                $imageFile->store(
                    'report-images',
                    'public'
                );

            ReportImage::create([
                'report_id' =>
                    $report->id,

                'file_path' =>
                    $path,

                'original_name' =>
                    $imageFile
                        ->getClientOriginalName(),

                'mime_type' =>
                    $imageFile
                        ->getMimeType(),

                'file_size' =>
                    $imageFile
                        ->getSize(),

                'is_primary' =>
                    $isPrimary,
            ]);

            $isPrimary =
                false;
        }

        /*
        |--------------------------------------------------------------------------
        | FOUND ITEM WORKFLOW
        |--------------------------------------------------------------------------
        |
        | pending_surrender
        |
        | Student must bring physical item to USC.
        |
        */

        if (
            $status ===
            Report::STATUS_PENDING_SURRENDER
        ) {
            $surrender =
                SurrenderRequest::create([
                    'report_id' =>
                        $report->id,

                    'student_id' =>
                        $user->id,

                    'status' =>
                        'pending',

                    'deadline_at' =>
                        now()->addDays(
                            3
                        ),
                ]);

            /*
            |--------------------------------------------------------------------------
            | STUDENT NOTIFICATION
            |--------------------------------------------------------------------------
            */

            $this->createStudentNotification([
                'student_id' =>
                    $user->id,

                'type' =>
                    'found_report_submitted',

                'title' =>
                    'Found Item Report Submitted',

                'message' =>
                    "Please surrender '{$report->title}' to the USC office for verification.",

                'action' =>
                    'surrender_requested',

                'status' =>
                    'pending',

                'recipient_id' =>
                    $user->id,

                'recipient_role' =>
                    'student',

                'subject_type' =>
                    Report::class,

                'subject_id' =>
                    $report->id,

                'data' => [
                    'report_id' =>
                        $report->id,

                    'deadline_at' =>
                        $surrender
                            ->deadline_at
                            ?? null,
                ],

                'is_read' =>
                    false,
            ]);

            /*
            |--------------------------------------------------------------------------
            | ADMIN NOTIFICATION
            |--------------------------------------------------------------------------
            */

            Notification::create([
                'type' =>
                    'pending_surrender',

                'title' =>
                    'New Found Item Pending Surrender',

                'message' =>
                    "The found item '{$report->title}' is waiting to be surrendered to USC.",

                'action' =>
                    'pending_surrender',

                'status' =>
                    'pending',

                'recipient_role' =>
                    'admin',

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
        }


            /*
            |--------------------------------------------------------------------------
            | SMALL FOUND-REPORT SUBMISSION RECOGNITION AWARD
            |--------------------------------------------------------------------------
            |
            | Revised SNSU flow:
            | - Finder submits the found report.
            | - The finder receives a small initial recognition award.
            | - The larger category-based award is given only after Completed Return.
            |
            | The award is idempotent by report reference, so the same report cannot
            | receive the submission point twice.
            |
            */

            $this->awardFoundReportSubmissionPoints(
                $user,
                $report
            );

        /*
        |--------------------------------------------------------------------------
        | LOST ITEM WORKFLOW
        |--------------------------------------------------------------------------
        */

        if (
            $status ===
            Report::STATUS_PENDING_APPROVAL
        ) {
            Notification::create([
                'type' =>
                    'pending_report',

                'title' =>
                    'New Lost Item Report',

                'message' =>
                    "A lost-item report for '{$report->title}' is waiting for review.",

                'action' =>
                    'pending_report',

                'status' =>
                    'pending',

                'recipient_role' =>
                    'admin',

                'subject_type' =>
                    Report::class,

                'subject_id' =>
                    $report->id,

                'data' => [
                    'report_id' =>
                        $report->id,
                ],

                'is_read' =>
                    false,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | EXISTING OPTIONAL REWARD FEATURE
        |--------------------------------------------------------------------------
        |
        | Keep this for backward compatibility.
        |
        | Recognition points will later be handled separately by category.
        |
        */

        if (
            !empty(
                $validated[
                    'reward_amount'
                ]
            )
        ) {
            Reward::create([
                'report_id' =>
                    $report->id,

                'student_id' =>
                    $user->id,

                'title' =>
                    $validated['title']
                    .
                    ' Reward',

                'description' =>
                    $validated['description']
                    ??
                    'Reward posted for the report',

                'amount' =>
                    $validated[
                        'reward_amount'
                    ],

                'type' =>
                    $validated[
                        'reward_type'
                    ]
                    ??
                    'reward',

                'status' =>
                    'pending',
            ]);
        }

        $report->load([
            'student',
            'images',
            'surrenderRequest',
        ]);

        $report->makeHidden(
            'private_identifying_details'
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE MESSAGE
        |--------------------------------------------------------------------------
        */

        $message =
            $report->isFound()
                ? 'Found-item report submitted. Please surrender the physical item to USC for verification.'
                : 'Lost-item report submitted and is waiting for USC approval.';

        return response()->json([
            'status' =>
                'success',

            'message' =>
                $message,

            'data' =>
                $report,
        ], 201);
    }



    /*
    |--------------------------------------------------------------------------
    | UPDATE LOST REPORT AFTER USC REQUESTS CORRECTION
    |--------------------------------------------------------------------------
    |
    | Owner-only correction endpoint for lost reports that are still waiting
    | for USC approval. The official status remains pending_approval.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | UPDATE REPORT AFTER USC REQUESTS MORE INFORMATION
    |--------------------------------------------------------------------------
    |
    | LOST:
    | - pending_approval
    |
    | FOUND:
    | - pending_surrender
    | - pending_verification
    |
    | The student may only update their own non-terminal report.
    | Official workflow statuses are preserved while the corrected information
    | is returned to the proper USC queue.
    |
    */

    public function update(
        Request $request,
        $id
    ) {
        $user = $request->user();

        $this->mergeFoundTimeInput($request);

        $report = Report::query()
            ->whereKey($id)
            ->firstOrFail();

        if (
            (int) $report->student_id !==
            (int) $user->id
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not own this report.',
            ], 403);
        }

        $allowed = false;

        if ($report->isLost()) {
            $allowed = in_array(
                $report->status,
                [
                    Report::STATUS_PENDING_APPROVAL,
                    'correction_requested',
                ],
                true
            );
        }

        if ($report->isFound()) {
            $allowed = in_array(
                $report->status,
                [
                    Report::STATUS_PENDING_SURRENDER,
                    Report::STATUS_PENDING_VERIFICATION,
                ],
                true
            );
        }

        if (!$allowed) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This report can no longer be edited in its current workflow status.',
            ], 422);
        }

        $validated = $request->validate([
            'title' =>
                'sometimes|required|string|max:255',

            'description' =>
                'sometimes|required|string|max:5000',

            'private_identifying_details' =>
                'sometimes|required|string|max:5000',

            'category_id' =>
                'sometimes|nullable|exists:item_categories,id',

            'metadata' =>
                'sometimes|nullable|array',

            'metadata.category' =>
                'sometimes|nullable|string|max:255',

            'color' =>
                'sometimes|nullable|string|max:255',

            'location' =>
                'sometimes|required|string|max:255',

            'date_occurred' =>
                'sometimes|required|date|before_or_equal:' . now('Asia/Manila')->toDateString(),

            'time_found' =>
                'sometimes|nullable|date_format:H:i,H:i:s,g:i A,h:i A',
        ], [
            'date_occurred.before_or_equal' =>
                'The report date cannot be in the future.',

            'time_found.date_format' =>
                'Time Found must be a valid time, such as 14:35 or 2:35 PM.',
        ]);

        $update = [];

        foreach (
            [
                'title',
                'description',
                'private_identifying_details',
                'category_id',
                'color',
                'location',
                'date_occurred',
                'time_found',
            ]
            as $field
        ) {
            if (array_key_exists($field, $validated)) {
                $value = $validated[$field];

                if (
                    is_string($value) &&
                    $field !== 'date_occurred'
                ) {
                    $value = trim($value);
                }

                $update[$field] = $field === 'time_found'
                    ? $this->normalizeFoundTime($value)
                    : $value;
            }
        }

        $metadata = array_merge(
            $report->metadata ?? [],
            $validated['metadata'] ?? []
        );

        if (
            isset($validated['metadata']) &&
            array_key_exists(
                'category',
                $validated['metadata']
            )
        ) {
            $update['category'] =
                $validated['metadata']['category'];
        }

        /*
        |--------------------------------------------------------------------------
        | LOST CORRECTION RESUBMISSION
        |--------------------------------------------------------------------------
        */

        if ($report->isLost()) {
            unset(
                $metadata['correction_requested'],
                $metadata['correction_request'],
                $metadata['correction_requested_at'],
                $metadata['correction_requested_by_admin_id']
            );

            $metadata['correction_resubmitted_at'] =
                now()->toIso8601String();

            $update['status'] =
                Report::STATUS_PENDING_APPROVAL;
        }

        /*
        |--------------------------------------------------------------------------
        | FOUND DETAILS RESUBMISSION
        |--------------------------------------------------------------------------
        */

        if ($report->isFound()) {
            $metadata['details_resubmitted_at'] =
                now()->toIso8601String();

            $metadata['details_resubmitted_from_status'] =
                $report->status;

            if (
                $report->status ===
                Report::STATUS_PENDING_SURRENDER
            ) {
                $update['status'] =
                    Report::STATUS_PENDING_SURRENDER;
            }

            if (
                $report->status ===
                Report::STATUS_PENDING_VERIFICATION
            ) {
                $update['status'] =
                    Report::STATUS_PENDING_VERIFICATION;
            }
        }

        $update['metadata'] = $metadata;

        DB::transaction(function () use (
            $report,
            $update
        ) {
            $report->update($update);

            /*
            |--------------------------------------------------------------------------
            | IF USC REQUESTED VERIFICATION DETAILS, RETURN IT TO PENDING REVIEW
            |--------------------------------------------------------------------------
            */

            if (
                $report->isFound() &&
                $report->status ===
                    Report::STATUS_PENDING_VERIFICATION
            ) {
                $verification =
                    ItemVerification::query()
                        ->where(
                            'report_id',
                            $report->id
                        )
                        ->first();

                if (
                    $verification &&
                    $verification->status ===
                        'needs_more_information'
                ) {
                    $verification->update([
                        'status' => 'pending',
                    ]);
                }
            }
        });

        /*
        |--------------------------------------------------------------------------
        | NOTIFY USC THAT THE STUDENT RESUBMITTED
        |--------------------------------------------------------------------------
        */

        if ($report->isLost()) {
            Notification::create([
                'type' => 'pending_report',
                'title' => 'Lost Report Updated',
                'message' =>
                    "The student updated lost-item report '{$report->title}' and it is ready for USC review again.",
                'action' => 'pending_report',
                'status' => 'pending',
                'recipient_role' => 'admin',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'data' => [
                    'report_id' => $report->id,
                    'resubmitted' => true,
                ],
                'is_read' => false,
            ]);
        }

        if (
            $report->isFound() &&
            $report->status ===
                Report::STATUS_PENDING_SURRENDER
        ) {
            Notification::create([
                'type' => 'pending_surrender',
                'title' => 'Found Report Details Updated',
                'message' =>
                    "The finder updated details for '{$report->title}'. The item is still waiting to be surrendered to USC.",
                'action' => 'pending_surrender',
                'status' => 'pending',
                'recipient_role' => 'admin',
                'subject_type' => Report::class,
                'subject_id' => $report->id,
                'data' => [
                    'report_id' => $report->id,
                    'resubmitted' => true,
                ],
                'is_read' => false,
            ]);
        }

        if (
            $report->isFound() &&
            $report->status ===
                Report::STATUS_PENDING_VERIFICATION
        ) {
            $verification =
                ItemVerification::query()
                    ->where(
                        'report_id',
                        $report->id
                    )
                    ->first();

            Notification::create([
                'type' => 'item_verification',
                'title' => 'Verification Details Updated',
                'message' =>
                    "The finder updated details for '{$report->title}'. Please continue USC verification.",
                'action' => 'item_verification',
                'status' => 'pending',
                'recipient_role' => 'admin',
                'subject_type' =>
                    $verification
                        ? ItemVerification::class
                        : Report::class,
                'subject_id' =>
                    $verification?->id
                        ?? $report->id,
                'data' => [
                    'report_id' => $report->id,
                    'verification_id' =>
                        $verification?->id,
                    'resubmitted' => true,
                ],
                'is_read' => false,
            ]);
        }

        $report->load([
            'student',
            'images',
            'visiblePost',
            'surrenderRequest',
            'itemVerification',
        ]);

        $report->makeHidden(
            'private_identifying_details'
        );

        return response()->json([
            'status' => 'success',
            'message' =>
                'Report updated successfully and returned to the USC workflow.',
            'data' => $report,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD ADDITIONAL REPORT IMAGES
    |--------------------------------------------------------------------------
    */

    public function uploadImages(
        Request $request,
        $reportId
    ) {
        $user =
            $request->user();

        $report =
            Report::findOrFail(
                $reportId
            );

        /*
        |--------------------------------------------------------------------------
        | OWNER ONLY
        |--------------------------------------------------------------------------
        */

        if (
            (int) $report->student_id !==
            (int) $user->id
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Unauthorized',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | DO NOT ALLOW CHANGES AFTER TERMINAL STATUS
        |--------------------------------------------------------------------------
        */

        if (
            $report->isTerminalStatus()
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'This report can no longer be modified.',
            ], 422);
        }

        $currentCount =
            $report
                ->images()
                ->count();

        $remaining =
            max(
                0,
                3 - $currentCount
            );

        if ($remaining <= 0) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'This report already has the maximum of 3 images.',
            ], 422);
        }

        $validated =
            $request->validate(
                [
                    'images' =>
                        "required|array|min:1|max:{$remaining}",

                    'images.*' =>
                        'required|file|image|max:5120',
                ],
                [
                    'images.required' =>
                        'At least one image is required.',

                    'images.min' =>
                        'At least one image is required.',

                    'images.max' =>
                        "You can only add {$remaining} more image(s).",

                    'images.*.image' =>
                        'All files must be valid images.',

                    'images.*.max' =>
                        'Images must not exceed 5MB each.',
                ]
            );

        $uploadedImages =
            [];

        $isPrimary =
            !$report
                ->images()
                ->exists();

        foreach (
            $request->file(
                'images',
                []
            )
            as
            $imageFile
        ) {
            $path =
                $imageFile->store(
                    'report-images',
                    'public'
                );

            $image =
                ReportImage::create([
                    'report_id' =>
                        $report->id,

                    'file_path' =>
                        $path,

                    'original_name' =>
                        $imageFile
                            ->getClientOriginalName(),

                    'mime_type' =>
                        $imageFile
                            ->getMimeType(),

                    'file_size' =>
                        $imageFile
                            ->getSize(),

                    'is_primary' =>
                        $isPrimary,
                ]);

            $uploadedImages[] =
                $image;

            $isPrimary =
                false;
        }

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Images uploaded successfully.',

            'data' =>
                $uploadedImages,
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE REPORT IMAGE
    |--------------------------------------------------------------------------
    */

    public function deleteImage(
        Request $request,
        $reportId,
        $imageId
    ) {
        $user =
            $request->user();

        $report =
            Report::findOrFail(
                $reportId
            );

        if (
            (int) $report->student_id !==
            (int) $user->id
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Unauthorized',
            ], 403);
        }

        if (
            $report->isTerminalStatus()
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'This report can no longer be modified.',
            ], 422);
        }

        $image =
            ReportImage::query()
                ->where(
                    'report_id',
                    $report->id
                )
                ->findOrFail(
                    $imageId
                );

        /*
        |--------------------------------------------------------------------------
        | REPORT MUST ALWAYS KEEP AT LEAST ONE IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            $report->requires_images &&
            $report
                ->images()
                ->count() === 1
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Cannot delete the last image. Reports must have at least one image.',
            ], 422);
        }

        $wasPrimary =
            (bool) $image->is_primary;

        Storage::disk(
            'public'
        )->delete(
            $image->file_path
        );

        $image->delete();

        /*
        |--------------------------------------------------------------------------
        | ASSIGN NEW PRIMARY IMAGE
        |--------------------------------------------------------------------------
        */

        if ($wasPrimary) {
            $nextImage =
                $report
                    ->images()
                    ->first();

            if ($nextImage) {
                $nextImage->update([
                    'is_primary' =>
                        true,
                ]);
            }
        }

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Image deleted successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FIND POTENTIAL MATCHES
    |--------------------------------------------------------------------------
    |
    | Matching is performed between:
    |
    | APPROVED lost reports
    |
    | and
    |
    | VERIFIED found reports
    |
    */

    public function matches(
        Request $request,
        $id
    ) {
        $report =
            Report::findOrFail(
                $id
            );

        $user =
            $request->user();

        $isOwner =
            (int) $report->student_id ===
            (int) $user->id;

        /*
        |--------------------------------------------------------------------------
        | Owner can request matches, but their report must already have
        | completed its required approval/verification stage.
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEligibleForMatching(
                $report
            )
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'This report is not eligible for matching yet.',
            ], 422);
        }

        if (
            !$isOwner &&
            !$report->isPubliclyVisible()
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Report not found.',
            ], 404);
        }

        $candidates =
            app(
                PotentialMatchService::class
            )->candidatesFor(
                $report
            );

        /*
        |--------------------------------------------------------------------------
        | SCORE MATCHES
        |--------------------------------------------------------------------------
        */

        $matches =
            $candidates
                ->map(
                    function (
                        Report $candidate
                    ) use ($report) {
                        return [
                            'report' =>
                                $candidate,

                            'score' =>
                                $this->calculateMatchScore(
                                    $report,
                                    $candidate
                                ),
                        ];
                    }
                )
                ->filter(
                    fn ($item) =>
                        $item['score'] > 0
                )
                ->sortByDesc(
                    'score'
                )
                ->values();

        return response()->json([
            'status' =>
                'success',

            'data' =>
                $matches,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM / SUBMIT POTENTIAL MATCH
    |--------------------------------------------------------------------------
    */

    public function confirmMatch(
        Request $request,
        $id,
        $matchId
    ) {
        $user =
            $request->user();

        $report =
            Report::findOrFail(
                $id
            );

        $matchedReport =
            Report::findOrFail(
                $matchId
            );

        if (
            (int) $report->student_id !==
            (int) $user->id
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'You do not own this report.',
            ], 403);
        }

        $service =
            app(
                PotentialMatchService::class
            );

        if (
            !$service->isEligible(
                $report
            ) ||
            !$service->isEligible(
                $matchedReport
            )
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'One of the reports is not eligible for a potential match.',
            ], 422);
        }

        if (
            $report->type ===
            $matchedReport->type
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'Potential matches must contain one lost report and one found report.',
            ], 422);
        }

        try {
            $result =
                $service
                    ->submitSuggestion(
                        $report,
                        $matchedReport
                    );
        } catch (\RuntimeException $exception) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    $exception->getMessage(),
            ], 422);
        }

        $match =
            $result['match'];

        if (
            $match->status ===
            'rejected'
        ) {
            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    'USC previously rejected this report pairing.',
            ], 422);
        }

        if (
            $match->status ===
            'confirmed'
        ) {
            return response()->json([
                'status' =>
                    'success',

                'message' =>
                    'This potential match has already been confirmed by USC.',

                'data' =>
                    $match,
            ]);
        }

        if ($result['created']) {
            Notification::create([
                'type' =>
                    'potential_match',

                'title' =>
                    'Potential Match Needs Review',

                'message' =>
                    "A student suggested a potential Lost/Found match with a similarity score of {$result['score']}%.",

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
        }

        return response()->json([
            'status' =>
                'success',

            'message' =>
                $result['created']
                    ? 'Potential match submitted to USC for review.'
                    : 'This potential match is already waiting for USC review.',

            'data' =>
                $match,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MATCH ELIGIBILITY
    |--------------------------------------------------------------------------
    */

    protected function isEligibleForMatching(
        Report $report
    ): bool {
        return app(
            PotentialMatchService::class
        )->isEligible(
            $report
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE MATCH SCORE
    |--------------------------------------------------------------------------
    |
    | Final matching fields:
    |
    | Category       20
    | Item name      20
    | Description    20
    | Color           15
    | Location        15
    | Date            10
    |
    | TOTAL          100
    |
    */

    protected function calculateMatchScore(
        Report $first,
        Report $second
    ): int {
        return app(
            PotentialMatchService::class
        )->calculateScore(
            $first,
            $second
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXTRACT SEARCH / MATCH KEYWORDS
    |--------------------------------------------------------------------------
    */

    protected function extractKeywords(
        ?string $text
    ): array {
        if (
            empty(
                $text
            )
        ) {
            return [];
        }

        $text =
            Str::lower(
                preg_replace(
                    '/[^a-z0-9\s]+/',
                    ' ',
                    $text
                )
            );

        $words =
            array_filter(
                array_map(
                    'trim',
                    preg_split(
                        '/\s+/',
                        $text
                    )
                ),
                fn ($word) =>
                    strlen(
                        $word
                    ) >= 3
            );

        return array_values(
            array_unique(
                $words
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AWARD SMALL FOUND-REPORT SUBMISSION POINT
    |--------------------------------------------------------------------------
    */

    protected function awardFoundReportSubmissionPoints(
        $student,
        Report $report
    ): array {
        if (
            !$student ||
            !$report->isFound()
        ) {
            return [
                'awarded' => 0,
                'balance' => 0,
            ];
        }

        $points =
            self::FOUND_REPORT_SUBMISSION_POINTS;

        $existing =
            PointTransaction::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'type',
                    'found_report_submission'
                )
                ->where(
                    'reference_type',
                    Report::class
                )
                ->where(
                    'reference_id',
                    $report->id
                )
                ->first();

        $awarded = 0;

        if (!$existing) {
            PointTransaction::create([
                'student_id' =>
                    $student->id,

                'type' =>
                    'found_report_submission',

                'direction' =>
                    'credit',

                'points' =>
                    $points,

                'amount' =>
                    round(
                        $points /
                        self::POINTS_PER_PESO,
                        2
                    ),

                'description' =>
                    'Small recognition award for submitting a found-item report.',

                'reference_type' =>
                    Report::class,

                'reference_id' =>
                    $report->id,
            ]);

            $awarded =
                $points;
        }

        $balance =
            (int)
            PointTransaction::query()
                ->where(
                    'student_id',
                    $student->id
                )
                ->sum('points');

        StudentRecognitionRecord::updateOrCreate(
            [
                'student_id' =>
                    $student->id,
            ],
            [
                'recognition_points' =>
                    $balance,

                'peso_value' =>
                    round(
                        $balance /
                        self::POINTS_PER_PESO,
                        2
                    ),

                'reward_count' =>
                    (int)
                    ($student->reward_count ?? 0),

                'report_count' =>
                    $student
                        ->reports()
                        ->count(),
            ]
        );

        if ($awarded > 0) {
            $this->createStudentNotification([
                'student_id' =>
                    $student->id,

                'type' =>
                    'recognition_points_awarded',

                'title' =>
                    'Recognition Point Awarded',

                'message' =>
                    "You received {$awarded} recognition point for submitting the found-item report '{$report->title}'. Additional category points can be awarded after a successful completed return.",

                'action' =>
                    'recognition_points_awarded',

                'status' =>
                    'pending',

                'recipient_id' =>
                    $student->id,

                'recipient_role' =>
                    'student',

                'subject_type' =>
                    Report::class,

                'subject_id' =>
                    $report->id,

                'data' => [
                    'report_id' =>
                        $report->id,

                    'points_awarded' =>
                        $awarded,

                    'recognition_points_balance' =>
                        $balance,

                    'award_type' =>
                        'found_report_submission',
                ],

                'is_read' =>
                    false,
            ]);

        }

        return [
            'awarded' =>
                $awarded,

            'balance' =>
                $balance,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE STUDENT NOTIFICATION + REMOTE PUSH
    |--------------------------------------------------------------------------
    |
    | The database notification is always created first. Push failure is
    | non-fatal so report/claim workflows continue normally.
    |
    */

    protected function createStudentNotification(
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


    /*
    |--------------------------------------------------------------------------
    | CREATE MATCH NOTIFICATION
    |--------------------------------------------------------------------------
    */

    protected function createMatchNotification(
        Report $report,
        Report $otherReport
    ) {
        $notification =
            $this->createStudentNotification([
                'student_id' =>
                    $report->student_id,

                'title' =>
                    'Potential Match Found',

                'message' =>
                    "Your {$report->type} report '{$report->title}' has a possible match: '{$otherReport->title}'.",

                'type' =>
                    'lost_found_match',

                'action' =>
                    'potential_match_found',

                'status' =>
                    'pending',

                'recipient_id' =>
                    $report->student_id,

                'recipient_role' =>
                    'student',

                'subject_type' =>
                    Report::class,

                'subject_id' =>
                    $report->id,

                'data' => [
                    'report_id' =>
                        $report->id,

                    'matched_report_id' =>
                        $otherReport->id,
                ],

                'is_read' =>
                    false,
            ]);

        return $notification;
    }


}