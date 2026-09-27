<?php

namespace App\Http\Controllers;

use App\Models\ItemRelease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItemReleaseController extends Controller
{
    /**
     * Display item release records for the admin portal.
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $items = ItemRelease::query()
            ->with([
                'report.student',
                'report.images',
                'report.categoryRelation',
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

    /**
     * Save the USC physical-release checklist before completing the return.
     */
    public function updateChecklist(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'checklist' => 'required|array',

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
            return DB::transaction(function () use (
                $validated,
                $id,
                $admin
            ) {
                $release = ItemRelease::query()
                    ->whereKey($id)
                    ->whereIn('status', [
                        'pending',
                        'approved',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                $checklist = [
                    'claim_approved' =>
                        true,

                    'claimant_identity_checked' =>
                        (bool) $validated['checklist']['claimant_identity_checked'],

                    'ownership_proof_confirmed' =>
                        (bool) $validated['checklist']['ownership_proof_confirmed'],

                    'physical_item_condition_checked' =>
                        (bool) $validated['checklist']['physical_item_condition_checked'],

                    'claimant_acknowledgment_completed' =>
                        (bool) $validated['checklist']['claimant_acknowledgment_completed'],
                ];

                $allCompleted =
                    collect($checklist)
                        ->every(
                            fn ($value) =>
                                $value === true
                        );

                $release->update([
                    'checklist' =>
                        $checklist,

                    'receipt_number' =>
                        $validated['receipt_number'] ?? null,

                    'status' =>
                        $allCompleted
                            ? 'approved'
                            : 'pending',

                    'approved_by_admin_id' =>
                        $admin->id,
                ]);

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
                            'claimantStudent',
                            'approvedByAdmin',
                        ]),

                    'checklist_complete' =>
                        $allCompleted,
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
