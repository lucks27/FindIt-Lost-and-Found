<?php

namespace App\Http\Controllers;

use App\Models\ClaimReview;
use App\Models\DeviceToken;
use App\Models\ItemVerification;
use App\Models\Notification;
use App\Models\PotentialMatch;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\Reward;
use App\Models\SurrenderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Minishlink\WebPush\WebPush;

class ReportController extends Controller
{
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
                    | ONLY VERIFIED FOUND ITEMS MAY BE CLAIMED
                    |--------------------------------------------------------------------------
                    */

                    $claimableStatuses = [
                        Report::STATUS_VERIFIED,
                        Report::STATUS_POTENTIAL_MATCH_FOUND,
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
                                'Only verified found items can be claimed.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Finder cannot claim their own submitted found item.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $report->student_id ===
                        (int) $user->id
                    ) {
                        return response()->json([
                            'status' =>
                                'error',

                            'message' =>
                                'You cannot submit a claim on your own report.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT DUPLICATE ACTIVE CLAIM
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

                    /*
                    |--------------------------------------------------------------------------
                    | EVIDENCE
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE CLAIM
                    |--------------------------------------------------------------------------
                    */

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

                    /*
                    |--------------------------------------------------------------------------
                    | REPORT NOW UNDER CLAIM REVIEW
                    |--------------------------------------------------------------------------
                    */

                    $report->update([
                        'status' =>
                            Report::STATUS_UNDER_CLAIM_REVIEW,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | NOTIFY ADMIN
                    |--------------------------------------------------------------------------
                    */

                    Notification::create([
                        'type' =>
                            'claim_submitted',

                        'title' =>
                            'New Claim Submitted',

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

                        'is_read' =>
                            false,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | ITEM VERIFICATION RECORD
                    |--------------------------------------------------------------------------
                    */

                    ItemVerification::firstOrCreate(
                        [
                            'report_id' =>
                                $report->id,
                        ],
                        [
                            'status' =>
                                'pending',

                            'notes' =>
                                'Pending USC ownership claim review.',
                        ]
                    );

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            'Claim request submitted successfully. Please wait for USC verification.',

                        'data' =>
                            $claim->load(
                                'report'
                            ),
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
    | CREATE REPORT
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {
        $user =
            $request->user();

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
                        'required|date|before_or_equal:today',

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

                'report_type' =>
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

            Notification::create([
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

        $oppositeType =
            $report->isLost()
                ? Report::TYPE_FOUND
                : Report::TYPE_LOST;

        $requiredStatus =
            $oppositeType ===
            Report::TYPE_FOUND
                ? Report::STATUS_VERIFIED
                : Report::STATUS_APPROVED;

        $candidates =
            Report::query()
                ->where(
                    'type',
                    $oppositeType
                )
                ->where(
                    'status',
                    $requiredStatus
                )
                ->where(
                    'id',
                    '<>',
                    $report->id
                )
                ->get();

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
                    'You do not own this report.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | MUST BE OPPOSITE TYPES
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | BASE ELIGIBILITY
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEligibleForMatching(
                $report
            ) ||
            !$this->isEligibleForMatching(
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

        /*
        |--------------------------------------------------------------------------
        | IDENTIFY LOST AND FOUND REPORT CORRECTLY
        |--------------------------------------------------------------------------
        */

        $lostReport =
            $report->isLost()
                ? $report
                : $matchedReport;

        $foundReport =
            $report->isFound()
                ? $report
                : $matchedReport;

        $existingMatch =
            PotentialMatch::query()
                ->where(
                    'lost_report_id',
                    $lostReport->id
                )
                ->where(
                    'found_report_id',
                    $foundReport->id
                )
                ->first();

        $score =
            $this->calculateMatchScore(
                $lostReport,
                $foundReport
            );

        if (!$existingMatch) {
            $existingMatch =
                PotentialMatch::create([
                    'lost_report_id' =>
                        $lostReport->id,

                    'found_report_id' =>
                        $foundReport->id,

                    'score' =>
                        min(
                            100,
                            $score
                        ),

                    'status' =>
                        'pending',

                    'review_notes' =>
                        'Potential match suggested by student.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE REPORT WORKFLOW STATUS
        |--------------------------------------------------------------------------
        */

        $lostReport->update([
            'status' =>
                Report::STATUS_POTENTIAL_MATCH_FOUND,
        ]);

        $foundReport->update([
            'status' =>
                Report::STATUS_POTENTIAL_MATCH_FOUND,
        ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFY BOTH STUDENTS
        |--------------------------------------------------------------------------
        */

        $this->createMatchNotification(
            $lostReport,
            $foundReport
        );

        $this->createMatchNotification(
            $foundReport,
            $lostReport
        );

        /*
        |--------------------------------------------------------------------------
        | ADMIN NOTIFICATION
        |--------------------------------------------------------------------------
        */

        Notification::create([
            'type' =>
                'potential_match_found',

            'title' =>
                'Potential Match Found',

            'message' =>
                "A potential match was detected between '{$lostReport->title}' and '{$foundReport->title}'.",

            'action' =>
                'potential_match_found',

            'status' =>
                'pending',

            'recipient_role' =>
                'admin',

            'subject_type' =>
                PotentialMatch::class,

            'subject_id' =>
                $existingMatch->id,

            'data' => [
                'lost_report_id' =>
                    $lostReport->id,

                'found_report_id' =>
                    $foundReport->id,

                'score' =>
                    min(
                        100,
                        $score
                    ),
            ],

            'is_read' =>
                false,
        ]);

        return response()->json([
            'status' =>
                'success',

            'message' =>
                'Potential match submitted for USC review.',

            'data' =>
                $existingMatch,
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
        if ($report->isLost()) {
            return in_array(
                $report->status,
                [
                    Report::STATUS_APPROVED,
                    Report::STATUS_POTENTIAL_MATCH_FOUND,
                ],
                true
            );
        }

        if ($report->isFound()) {
            return in_array(
                $report->status,
                [
                    Report::STATUS_VERIFIED,
                    Report::STATUS_POTENTIAL_MATCH_FOUND,
                ],
                true
            );
        }

        return false;
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
        $score =
            0;

        /*
        |--------------------------------------------------------------------------
        | CATEGORY - 20
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $first->category
            ) &&
            !empty(
                $second->category
            ) &&
            Str::lower(
                trim(
                    $first->category
                )
            ) ===
            Str::lower(
                trim(
                    $second->category
                )
            )
        ) {
            $score +=
                20;
        }

        /*
        |--------------------------------------------------------------------------
        | ITEM NAME - 20
        |--------------------------------------------------------------------------
        */

        $firstTitle =
            Str::lower(
                trim(
                    (string)
                    $first->title
                )
            );

        $secondTitle =
            Str::lower(
                trim(
                    (string)
                    $second->title
                )
            );

        if (
            $firstTitle !== '' &&
            $secondTitle !== ''
        ) {
            if (
                $firstTitle ===
                $secondTitle
            ) {
                $score +=
                    20;
            } elseif (
                Str::contains(
                    $firstTitle,
                    $secondTitle
                ) ||
                Str::contains(
                    $secondTitle,
                    $firstTitle
                )
            ) {
                $score +=
                    15;
            } else {
                $firstTitleWords =
                    $this->extractKeywords(
                        $firstTitle
                    );

                $secondTitleWords =
                    $this->extractKeywords(
                        $secondTitle
                    );

                $commonTitleWords =
                    array_intersect(
                        $firstTitleWords,
                        $secondTitleWords
                    );

                $score +=
                    min(
                        10,
                        count(
                            $commonTitleWords
                        ) * 5
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION - 20
        |--------------------------------------------------------------------------
        */

        $firstDescription =
            $this->extractKeywords(
                (string)
                $first->description
            );

        $secondDescription =
            $this->extractKeywords(
                (string)
                $second->description
            );

        $descriptionMatches =
            array_intersect(
                $firstDescription,
                $secondDescription
            );

        $score +=
            min(
                20,
                count(
                    $descriptionMatches
                ) * 4
            );

        /*
        |--------------------------------------------------------------------------
        | COLOR - 15
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $first->color
            ) &&
            !empty(
                $second->color
            )
        ) {
            $firstColor =
                Str::lower(
                    trim(
                        $first->color
                    )
                );

            $secondColor =
                Str::lower(
                    trim(
                        $second->color
                    )
                );

            if (
                $firstColor ===
                $secondColor
            ) {
                $score +=
                    15;
            } elseif (
                Str::contains(
                    $firstColor,
                    $secondColor
                ) ||
                Str::contains(
                    $secondColor,
                    $firstColor
                )
            ) {
                $score +=
                    10;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | LOCATION - 15
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $first->location
            ) &&
            !empty(
                $second->location
            )
        ) {
            $firstLocation =
                Str::lower(
                    trim(
                        $first->location
                    )
                );

            $secondLocation =
                Str::lower(
                    trim(
                        $second->location
                    )
                );

            if (
                $firstLocation ===
                $secondLocation
            ) {
                $score +=
                    15;
            } elseif (
                Str::contains(
                    $firstLocation,
                    $secondLocation
                ) ||
                Str::contains(
                    $secondLocation,
                    $firstLocation
                )
            ) {
                $score +=
                    10;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DATE - 10
        |--------------------------------------------------------------------------
        */

        if (
            $first->date_occurred &&
            $second->date_occurred
        ) {
            $difference =
                abs(
                    $first
                        ->date_occurred
                        ->diffInDays(
                            $second
                                ->date_occurred,
                            false
                        )
                );

            if ($difference === 0) {
                $score +=
                    10;
            } elseif (
                $difference <= 1
            ) {
                $score +=
                    8;
            } elseif (
                $difference <= 3
            ) {
                $score +=
                    5;
            } elseif (
                $difference <= 7
            ) {
                $score +=
                    2;
            }
        }

        return min(
            100,
            $score
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
    | CREATE MATCH NOTIFICATION
    |--------------------------------------------------------------------------
    */

    protected function createMatchNotification(
        Report $report,
        Report $otherReport
    ) {
        $notification =
            Notification::create([
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

        /*
        |--------------------------------------------------------------------------
        | Push sending failure must NOT break the workflow.
        |--------------------------------------------------------------------------
        */

        try {
            $this->sendPushNotificationToUser(
                (int)
                $report->student_id,

                'Potential Match Found',

                "A possible match was found for '{$report->title}'.",

                [
                    'action' =>
                        'potential_match_found',

                    'report_id' =>
                        $report->id,

                    'matched_report_id' =>
                        $otherReport->id,
                ]
            );
        } catch (\Throwable $exception) {
            report(
                $exception
            );
        }

        return $notification;
    }


    /*
    |--------------------------------------------------------------------------
    | SEND PUSH NOTIFICATION
    |--------------------------------------------------------------------------
    |
    | Push notification failures do not affect normal API workflow.
    |
    */

    protected function sendPushNotificationToUser(
        int $studentId,
        string $title,
        string $body,
        array $data = []
    ) {
        $deviceTokens =
            DeviceToken::where(
                'student_id',
                $studentId
            )->get();

        if (
            $deviceTokens->isEmpty()
        ) {
            return false;
        }

        $expoTokens =
            [];

        $fcmTokens =
            [];

        $webSubscriptions =
            [];

        foreach (
            $deviceTokens
            as
            $deviceToken
        ) {
            $tokenValue =
                (string)
                $deviceToken->token;

            if (
                $deviceToken->platform ===
                'web'
            ) {
                $subscription =
                    json_decode(
                        $tokenValue,
                        true
                    );

                if (
                    is_array(
                        $subscription
                    ) &&
                    !empty(
                        $subscription[
                            'endpoint'
                        ]
                    )
                ) {
                    $subscriptionObject =
                        null;

                    if (
                        method_exists(
                            \Minishlink\WebPush\Subscription::class,
                            'create'
                        )
                    ) {
                        $subscriptionObject =
                            \Minishlink\WebPush\Subscription::create(
                                $subscription
                            );
                    }

                    if (
                        !$subscriptionObject instanceof
                        \Minishlink\WebPush\SubscriptionInterface
                    ) {
                        $subscriptionObject =
                            new \Minishlink\WebPush\Subscription(
                                (string) (
                                    $subscription[
                                        'endpoint'
                                    ] ??
                                    ''
                                ),
                                (string) (
                                    $subscription[
                                        'keys'
                                    ][
                                        'p256dh'
                                    ] ??
                                    ''
                                ),
                                (string) (
                                    $subscription[
                                        'keys'
                                    ][
                                        'auth'
                                    ] ??
                                    ''
                                )
                            );
                    }

                    if (
                        $subscriptionObject instanceof
                        \Minishlink\WebPush\SubscriptionInterface
                    ) {
                        $webSubscriptions[] =
                            $subscriptionObject;
                    }
                }

                continue;
            }

            if (
                Str::startsWith(
                    $tokenValue,
                    'ExponentPushToken'
                )
            ) {
                $expoTokens[] =
                    $tokenValue;
            } else {
                $fcmTokens[] =
                    $tokenValue;
            }
        }

        $success =
            true;

        /*
        |--------------------------------------------------------------------------
        | EXPO PUSH
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $expoTokens
            )
        ) {
            $expoPayloads =
                array_map(
                    fn ($token) => [
                        'to' =>
                            $token,

                        'title' =>
                            $title,

                        'body' =>
                            $body,

                        'data' =>
                            $data,
                    ],
                    $expoTokens
                );

            try {
                $expoResponse =
                    Http::withHeaders([
                        'Accept' =>
                            'application/json',

                        'Content-Type' =>
                            'application/json',
                    ])->post(
                        'https://exp.host/--/api/v2/push/send',
                        $expoPayloads
                    );

                if (
                    !$expoResponse->successful()
                ) {
                    $success =
                        false;
                }
            } catch (\Throwable $exception) {
                report(
                    $exception
                );

                $success =
                    false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FCM
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $fcmTokens
            )
        ) {
            $serverKey =
                env(
                    'FCM_SERVER_KEY'
                );

            if (
                !empty(
                    $serverKey
                )
            ) {
                try {
                    $fcmPayload = [
                        'registration_ids' =>
                            $fcmTokens,

                        'notification' => [
                            'title' =>
                                $title,

                            'body' =>
                                $body,
                        ],

                        'data' =>
                            $data,
                    ];

                    $fcmResponse =
                        Http::withHeaders([
                            'Authorization' =>
                                "key={$serverKey}",

                            'Content-Type' =>
                                'application/json',
                        ])->post(
                            'https://fcm.googleapis.com/fcm/send',
                            $fcmPayload
                        );

                    if (
                        !$fcmResponse->successful()
                    ) {
                        $success =
                            false;
                    }
                } catch (\Throwable $exception) {
                    report(
                        $exception
                    );

                    $success =
                        false;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | WEB PUSH
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $webSubscriptions
            )
        ) {
            $publicKey =
                env(
                    'VAPID_PUBLIC_KEY'
                );

            $privateKey =
                env(
                    'VAPID_PRIVATE_KEY'
                );

            if (
                !empty(
                    $publicKey
                ) &&
                !empty(
                    $privateKey
                )
            ) {
                try {
                    $webPush =
                        new WebPush([
                            'VAPID' => [
                                'subject' =>
                                    env(
                                        'VAPID_SUBJECT',
                                        'mailto:admin@ssct.edu.ph'
                                    ),

                                'publicKey' =>
                                    $publicKey,

                                'privateKey' =>
                                    $privateKey,
                            ],
                        ]);

                    foreach (
                        $webSubscriptions
                        as
                        $subscription
                    ) {
                        try {
                            $webPush->sendOneNotification(
                                $subscription,
                                json_encode([
                                    'title' =>
                                        $title,

                                    'body' =>
                                        $body,

                                    'data' =>
                                        $data,

                                    'tag' =>
                                        'findit-notification',
                                ]),
                                [
                                    'TTL' =>
                                        86400,
                                ]
                            );
                        } catch (
                            \Throwable $exception
                        ) {
                            report(
                                $exception
                            );

                            $success =
                                false;
                        }
                    }

                    $webPush->flush();
                } catch (
                    \Throwable $exception
                ) {
                    report(
                        $exception
                    );

                    $success =
                        false;
                }
            }
        }

        return $success;
    }
}