<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRelease extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | RELEASE STATUS CONSTANTS
    |--------------------------------------------------------------------------
    |
    | Current workflow:
    |
    | pending
    |    ↓
    | approved
    |    ↓
    | released
    |
    | NOTE:
    | Your controller currently allows the final release action while the
    | release record is either "pending" or "approved".
    |
    */

    public const STATUS_PENDING =
        'pending';

    public const STATUS_APPROVED =
        'approved';

    public const STATUS_RELEASED =
        'released';


    /*
    |--------------------------------------------------------------------------
    | VALID STATUSES
    |--------------------------------------------------------------------------
    */

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_RELEASED,
    ];


    /*
    |--------------------------------------------------------------------------
    | REQUIRED RELEASE CHECKLIST
    |--------------------------------------------------------------------------
    |
    | These names match the checklist keys already used by your
    | AdminWorkflowController.php.
    |
    */

    public const CHECKLIST_CLAIM_APPROVED =
        'claim_approved';

    public const CHECKLIST_IDENTITY_CHECKED =
        'claimant_identity_checked';

    public const CHECKLIST_OWNERSHIP_CONFIRMED =
        'ownership_proof_confirmed';

    public const CHECKLIST_ITEM_CONDITION_CHECKED =
        'physical_item_condition_checked';

    public const CHECKLIST_ACKNOWLEDGMENT_COMPLETED =
        'claimant_acknowledgment_completed';


    /*
    |--------------------------------------------------------------------------
    | REQUIRED CHECKLIST KEYS
    |--------------------------------------------------------------------------
    */

    public const REQUIRED_CHECKLIST = [
        self::CHECKLIST_CLAIM_APPROVED,
        self::CHECKLIST_IDENTITY_CHECKED,
        self::CHECKLIST_OWNERSHIP_CONFIRMED,
        self::CHECKLIST_ITEM_CONDITION_CHECKED,
        self::CHECKLIST_ACKNOWLEDGMENT_COMPLETED,
    ];


    /*
    |--------------------------------------------------------------------------
    | FILLABLE
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'report_id',
        'claimant_student_id',
        'approved_by_admin_id',
        'release_code',
        'status',
        'scheduled_at',
        'released_at',
        'checklist',
        'receipt_number',
    ];


    /*
    |--------------------------------------------------------------------------
    | DEFAULT ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    protected $attributes = [
        'status' =>
            self::STATUS_PENDING,
    ];


    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'checklist' =>
            'array',

        'scheduled_at' =>
            'datetime',

        'released_at' =>
            'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | REPORT RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function report(): BelongsTo
    {
        return $this->belongsTo(
            Report::class,
            'report_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLAIMANT STUDENT
    |--------------------------------------------------------------------------
    */

    public function claimantStudent(): BelongsTo
    {
        return $this->belongsTo(
            Student::class,
            'claimant_student_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVING ADMIN
    |--------------------------------------------------------------------------
    */

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'approved_by_admin_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePending(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_PENDING
        );
    }


    public function scopeApproved(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_APPROVED
        );
    }


    public function scopeReleased(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_RELEASED
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVE RELEASE QUEUE
    |--------------------------------------------------------------------------
    |
    | Both pending and approved releases can still require admin action.
    |
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'status',
            [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS HELPERS
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status ===
            self::STATUS_PENDING;
    }


    public function isApproved(): bool
    {
        return $this->status ===
            self::STATUS_APPROVED;
    }


    public function isReleased(): bool
    {
        return $this->status ===
            self::STATUS_RELEASED;
    }


    /*
    |--------------------------------------------------------------------------
    | CAN THIS RELEASE STILL BE PROCESSED?
    |--------------------------------------------------------------------------
    */

    public function isActionable(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS VALIDATION
    |--------------------------------------------------------------------------
    */

    public static function isValidStatus(
        ?string $status
    ): bool {
        return in_array(
            $status,
            self::STATUSES,
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DEFAULT RELEASE CHECKLIST
    |--------------------------------------------------------------------------
    */

    public static function defaultChecklist(): array
    {
        return [
            self::CHECKLIST_CLAIM_APPROVED =>
                true,

            self::CHECKLIST_IDENTITY_CHECKED =>
                false,

            self::CHECKLIST_OWNERSHIP_CONFIRMED =>
                false,

            self::CHECKLIST_ITEM_CONDITION_CHECKED =>
                false,

            self::CHECKLIST_ACKNOWLEDGMENT_COMPLETED =>
                false,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GET CURRENT CHECKLIST
    |--------------------------------------------------------------------------
    */

    public function getReleaseChecklist(): array
    {
        $current =
            is_array($this->checklist)
                ? $this->checklist
                : [];

        return array_merge(
            self::defaultChecklist(),
            $current
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK SINGLE CHECKLIST ITEM
    |--------------------------------------------------------------------------
    */

    public function checklistItemCompleted(
        string $key
    ): bool {
        $checklist =
            $this->getReleaseChecklist();

        return (bool) (
            $checklist[$key] ??
            false
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SINGLE CHECKLIST ITEM
    |--------------------------------------------------------------------------
    */

    public function setChecklistItem(
        string $key,
        bool $completed = true
    ): bool {
        if (
            !in_array(
                $key,
                self::REQUIRED_CHECKLIST,
                true
            )
        ) {
            return false;
        }

        $checklist =
            $this->getReleaseChecklist();

        $checklist[$key] =
            $completed;

        return $this->update([
            'checklist' =>
                $checklist,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE ENTIRE CHECKLIST
    |--------------------------------------------------------------------------
    */

    public function updateChecklist(
        array $values
    ): bool {
        $checklist =
            $this->getReleaseChecklist();

        foreach (
            self::REQUIRED_CHECKLIST
            as $key
        ) {
            if (
                array_key_exists(
                    $key,
                    $values
                )
            ) {
                $checklist[$key] =
                    (bool) $values[$key];
            }
        }

        return $this->update([
            'checklist' =>
                $checklist,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | IS CHECKLIST COMPLETE?
    |--------------------------------------------------------------------------
    */

    public function hasCompletedChecklist(): bool
    {
        $checklist =
            $this->getReleaseChecklist();

        foreach (
            self::REQUIRED_CHECKLIST
            as $key
        ) {
            if (
                !(
                    $checklist[$key] ??
                    false
                )
            ) {
                return false;
            }
        }

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETED CHECKLIST COUNT
    |--------------------------------------------------------------------------
    */

    public function completedChecklistCount(): int
    {
        $checklist =
            $this->getReleaseChecklist();

        $completed =
            0;

        foreach (
            self::REQUIRED_CHECKLIST
            as $key
        ) {
            if (
                $checklist[$key] ??
                false
            ) {
                $completed++;
            }
        }

        return $completed;
    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL CHECKLIST COUNT
    |--------------------------------------------------------------------------
    */

    public function checklistCount(): int
    {
        return count(
            self::REQUIRED_CHECKLIST
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECKLIST PROGRESS
    |--------------------------------------------------------------------------
    |
    | Returns a value from 0 to 100.
    |
    */

    public function checklistProgress(): int
    {
        $total =
            $this->checklistCount();

        if ($total === 0) {
            return 100;
        }

        return (int) round(
            (
                $this->completedChecklistCount()
                /
                $total
            ) *
            100
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MARK RELEASE APPROVED
    |--------------------------------------------------------------------------
    */

    public function markAsApproved(
        ?int $adminId = null
    ): bool {
        $payload = [
            'status' =>
                self::STATUS_APPROVED,
        ];

        if ($adminId) {
            $payload[
                'approved_by_admin_id'
            ] =
                $adminId;
        }

        return $this->update(
            $payload
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ITEM RELEASED
    |--------------------------------------------------------------------------
    */

    public function markAsReleased(
        ?int $adminId = null
    ): bool {
        $payload = [
            'status' =>
                self::STATUS_RELEASED,

            'released_at' =>
                now(),
        ];

        if ($adminId) {
            $payload[
                'approved_by_admin_id'
            ] =
                $adminId;
        }

        return $this->update(
            $payload
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELEASE CODE HELPER
    |--------------------------------------------------------------------------
    */

    public function hasReleaseCode(): bool
    {
        return !empty(
            $this->release_code
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RECEIPT HELPER
    |--------------------------------------------------------------------------
    */

    public function hasReceiptNumber(): bool
    {
        return !empty(
            $this->receipt_number
        );
    }
}