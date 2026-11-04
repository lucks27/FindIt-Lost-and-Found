<?php

namespace App\Http\Controllers;

use App\Models\ClaimReview;
use App\Models\ItemRelease;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemReleaseController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ITEM RELEASE QUEUE
    |--------------------------------------------------------------------------
    |
    | Shows pending, approved, and completed release records for the USC
    | administrator. The frontend may filter the returned collection.
    |
    */

    public function index(Request $request)
    {
        $admin =
            Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $items =
            ItemRelease::query()
                ->with([
                    'report.student',
                    'report.images',
                    'report.categoryRelation',
                    'report.visiblePost',
                    'claimantStudent',
                    'approvedByAdmin',
                ])
                ->latest()
                ->get();

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE PHYSICAL RELEASE CHECKLIST
    |--------------------------------------------------------------------------
    |
    | Official SNSU release flow:
    |
    | 1. Claim approved
    | 2. Claimant identity checked
    | 3. Ownership proof confirmed
    | 4. Physical item condition checked
    | 5. Claimant acknowledgment completed
    |
    | The item may only move to final completion after every required item is
    | checked.
    |
    */

    public function updateChecklist(
        Request $request,
        $id
    ) {
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
                'checklist' =>
                    'required|array',

                'checklist.claimant_identity_checked' =>
                    'required|boolean',

                'checklist.ownership_proof_confirmed' =>
                    'required|boolean',

                'checklist.physical_item_condition_checked' =>
                    'required|boolean',

                'checklist.claimant_acknowledgment_completed' =>
                    'required|boolean',

                'receipt_number' =>
                    'nullable|string|max:120',
            ]);

        try {
            return DB::transaction(
                function () use (
                    $validated,
                    $id,
                    $admin
                ) {
                    $release =
                        ItemRelease::query()
                            ->whereKey($id)
                            ->whereIn(
                                'status',
                                [
                                    ItemRelease::STATUS_PENDING,
                                    ItemRelease::STATUS_APPROVED,
                                ]
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

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
                                'This found item is not currently ready for release.',
                        ], 422);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | THE CLAIM MUST STILL BE THE APPROVED CLAIM
                    |--------------------------------------------------------------------------
                    */

                    $approvedClaim =
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
                            ->first();

                    if (!$approvedClaim) {
                        return response()->json([
                            'status' => 'error',
                            'message' =>
                                'The approved ownership claim for this release could not be found.',
                        ], 422);
                    }

                    $checklist = [
                        ItemRelease::CHECKLIST_CLAIM_APPROVED =>
                            true,

                        ItemRelease::CHECKLIST_IDENTITY_CHECKED =>
                            (bool)
                            $validated['checklist'][
                                'claimant_identity_checked'
                            ],

                        ItemRelease::CHECKLIST_OWNERSHIP_CONFIRMED =>
                            (bool)
                            $validated['checklist'][
                                'ownership_proof_confirmed'
                            ],

                        ItemRelease::CHECKLIST_ITEM_CONDITION_CHECKED =>
                            (bool)
                            $validated['checklist'][
                                'physical_item_condition_checked'
                            ],

                        ItemRelease::CHECKLIST_ACKNOWLEDGMENT_COMPLETED =>
                            (bool)
                            $validated['checklist'][
                                'claimant_acknowledgment_completed'
                            ],
                    ];

                    $release->update([
                        'checklist' =>
                            $checklist,

                        'receipt_number' =>
                            isset(
                                $validated['receipt_number']
                            )
                                ? trim(
                                    (string)
                                    $validated['receipt_number']
                                )
                                : null,

                        'approved_by_admin_id' =>
                            $admin->id,
                    ]);

                    $allCompleted =
                        $release
                            ->fresh()
                            ->hasCompletedChecklist();

                    if ($allCompleted) {
                        $release->markAsApproved(
                            $admin->id
                        );
                    } else {
                        $release->update([
                            'status' =>
                                ItemRelease::STATUS_PENDING,
                        ]);
                    }

                    return response()->json([
                        'status' =>
                            'success',

                        'message' =>
                            $allCompleted
                                ? 'Release checklist completed. The item is ready for final return.'
                                : 'Release checklist saved.',

                        'data' =>
                            $release->fresh([
                                'report.student',
                                'report.images',
                                'report.categoryRelation',
                                'report.visiblePost',
                                'claimantStudent',
                                'approvedByAdmin',
                            ]),

                        'checklist_complete' =>
                            $allCompleted,

                        'checklist_progress' =>
                            $release
                                ->fresh()
                                ->checklistProgress(),
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
}
