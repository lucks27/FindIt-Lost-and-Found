<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimReview extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | CLAIM STATUS CONSTANTS
    |--------------------------------------------------------------------------
    |
    | Ownership claim workflow:
    |
    | pending
    |      ↓
    | additional_information_required
    |      ↓
    | pending / additional_information_required
    |      ↓
    | approved
    |      ↓
    | item_released
    |      ↓
    | completed
    |
    | Or:
    |
    | pending
    |      ↓
    | rejected
    |
    */

    public const STATUS_PENDING =
        'pending';

    public const STATUS_ADDITIONAL_INFORMATION_REQUIRED =
        'additional_information_required';

    public const STATUS_APPROVED =
        'approved';

    public const STATUS_REJECTED =
        'rejected';

    public const STATUS_ITEM_RELEASED =
        'item_released';

    public const STATUS_COMPLETED =
        'completed';


    /*
    |--------------------------------------------------------------------------
    | VALID CLAIM STATUSES
    |--------------------------------------------------------------------------
    */

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ADDITIONAL_INFORMATION_REQUIRED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ITEM_RELEASED,
        self::STATUS_COMPLETED,
    ];


    /*
    |--------------------------------------------------------------------------
    | FILLABLE
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'report_id',
        'claimant_student_id',
        'review_status',
        'evidence',
        'notes',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];


    /*
    |--------------------------------------------------------------------------
    | DEFAULT ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    protected $attributes = [
        'review_status' =>
            self::STATUS_PENDING,
    ];


    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'evidence' =>
            'array',

        'reviewed_at' =>
            'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | REPORT
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
    | ADMIN WHO REVIEWED THE CLAIM
    |--------------------------------------------------------------------------
    */

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'reviewed_by_admin_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePending(
        Builder $query
    ): Builder {
        return $query->where(
            'review_status',
            self::STATUS_PENDING
        );
    }


    public function scopeAdditionalInformationRequired(
        Builder $query
    ): Builder {
        return $query->where(
            'review_status',
            self::STATUS_ADDITIONAL_INFORMATION_REQUIRED
        );
    }


    public function scopeApproved(
        Builder $query
    ): Builder {
        return $query->where(
            'review_status',
            self::STATUS_APPROVED
        );
    }


    public function scopeRejected(
        Builder $query
    ): Builder {
        return $query->where(
            'review_status',
            self::STATUS_REJECTED
        );
    }


    public function scopeItemReleased(
        Builder $query
    ): Builder {
        return $query->where(
            'review_status',
            self::STATUS_ITEM_RELEASED
        );
    }


    public function scopeCompleted(
        Builder $query
    ): Builder {
        return $query->where(
            'review_status',
            self::STATUS_COMPLETED
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVE ADMIN CLAIM QUEUE
    |--------------------------------------------------------------------------
    |
    | These claims still require admin action.
    |
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'review_status',
            [
                self::STATUS_PENDING,
                self::STATUS_ADDITIONAL_INFORMATION_REQUIRED,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN CLAIM
    |--------------------------------------------------------------------------
    |
    | Claims which have not reached a final state.
    |
    */

    public function scopeOpen(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'review_status',
            [
                self::STATUS_PENDING,
                self::STATUS_ADDITIONAL_INFORMATION_REQUIRED,
                self::STATUS_APPROVED,
                self::STATUS_ITEM_RELEASED,
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
        return $this->review_status ===
            self::STATUS_PENDING;
    }


    public function needsMoreInformation(): bool
    {
        return $this->review_status ===
            self::STATUS_ADDITIONAL_INFORMATION_REQUIRED;
    }


    public function isApproved(): bool
    {
        return $this->review_status ===
            self::STATUS_APPROVED;
    }


    public function isRejected(): bool
    {
        return $this->review_status ===
            self::STATUS_REJECTED;
    }


    public function isItemReleased(): bool
    {
        return $this->review_status ===
            self::STATUS_ITEM_RELEASED;
    }


    public function isCompleted(): bool
    {
        return $this->review_status ===
            self::STATUS_COMPLETED;
    }


    /*
    |--------------------------------------------------------------------------
    | CAN ADMIN REVIEW?
    |--------------------------------------------------------------------------
    */

    public function isReviewable(): bool
    {
        return in_array(
            $this->review_status,
            [
                self::STATUS_PENDING,
                self::STATUS_ADDITIONAL_INFORMATION_REQUIRED,
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FINAL STATUS?
    |--------------------------------------------------------------------------
    */

    public function isFinal(): bool
    {
        return in_array(
            $this->review_status,
            [
                self::STATUS_REJECTED,
                self::STATUS_COMPLETED,
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
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
    | MARK AS PENDING
    |--------------------------------------------------------------------------
    */

    public function markAsPending(): bool
    {
        return $this->update([
            'review_status' =>
                self::STATUS_PENDING,

            'reviewed_by_admin_id' =>
                null,

            'reviewed_at' =>
                null,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST ADDITIONAL INFORMATION
    |--------------------------------------------------------------------------
    */

    public function requestAdditionalInformation(
        int $adminId,
        ?string $notes = null
    ): bool {
        return $this->update([
            'review_status' =>
                self::STATUS_ADDITIONAL_INFORMATION_REQUIRED,

            'notes' =>
                $notes ?? $this->notes,

            'reviewed_by_admin_id' =>
                $adminId,

            'reviewed_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE CLAIM
    |--------------------------------------------------------------------------
    */

    public function approve(
        int $adminId,
        ?string $notes = null
    ): bool {
        return $this->update([
            'review_status' =>
                self::STATUS_APPROVED,

            'notes' =>
                $notes ?? $this->notes,

            'reviewed_by_admin_id' =>
                $adminId,

            'reviewed_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT CLAIM
    |--------------------------------------------------------------------------
    */

    public function reject(
        int $adminId,
        ?string $notes = null
    ): bool {
        return $this->update([
            'review_status' =>
                self::STATUS_REJECTED,

            'notes' =>
                $notes ?? $this->notes,

            'reviewed_by_admin_id' =>
                $adminId,

            'reviewed_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MARK ITEM RELEASED
    |--------------------------------------------------------------------------
    */

    public function markItemReleased(
        ?int $adminId = null
    ): bool {
        $payload = [
            'review_status' =>
                self::STATUS_ITEM_RELEASED,
        ];

        if ($adminId) {
            $payload['reviewed_by_admin_id'] =
                $adminId;

            $payload['reviewed_at'] =
                now();
        }

        return $this->update(
            $payload
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETE CLAIM
    |--------------------------------------------------------------------------
    */

    public function complete(
        ?int $adminId = null
    ): bool {
        $payload = [
            'review_status' =>
                self::STATUS_COMPLETED,
        ];

        if ($adminId) {
            $payload['reviewed_by_admin_id'] =
                $adminId;

            $payload['reviewed_at'] =
                now();
        }

        return $this->update(
            $payload
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLAIM EVIDENCE HELPERS
    |--------------------------------------------------------------------------
    */

    public function getProofDescription(): ?string
    {
        $evidence =
            is_array($this->evidence)
                ? $this->evidence
                : [];

        return $evidence[
            'proof_description'
        ] ?? null;
    }


    public function getProofPhoto(): ?string
    {
        $evidence =
            is_array($this->evidence)
                ? $this->evidence
                : [];

        return $evidence[
            'proof_photo'
        ] ?? null;
    }


    public function hasProofPhoto(): bool
    {
        return !empty(
            $this->getProofPhoto()
        );
    }


    public function hasEvidence(): bool
    {
        $evidence =
            is_array($this->evidence)
                ? $this->evidence
                : [];

        return !empty(
            array_filter(
                $evidence
            )
        );
    }
}