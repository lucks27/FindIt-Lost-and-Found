<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemVerification extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | VERIFICATION STATUS CONSTANTS
    |--------------------------------------------------------------------------
    |
    | Workflow:
    |
    | pending
    |     ↓
    | needs_more_information
    |     ↓
    | pending
    |     ↓
    | verified
    |     ↓
    | completed
    |     ↓
    | archived
    |
    */

    public const STATUS_PENDING = 'pending';

    public const STATUS_NEEDS_MORE_INFORMATION =
        'needs_more_information';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ARCHIVED = 'archived';


    /*
    |--------------------------------------------------------------------------
    | ALL VALID STATUSES
    |--------------------------------------------------------------------------
    */

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_NEEDS_MORE_INFORMATION,
        self::STATUS_VERIFIED,
        self::STATUS_COMPLETED,
        self::STATUS_ARCHIVED,
    ];


    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNMENT
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'report_id',
        'verifier_admin_id',
        'status',
        'checklist',
        'notes',
        'verified_at',
    ];


    /*
    |--------------------------------------------------------------------------
    | DEFAULT VALUES
    |--------------------------------------------------------------------------
    */

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];


    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'checklist' => 'array',
        'verified_at' => 'datetime',
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
    | ADMIN WHO VERIFIED THE ITEM
    |--------------------------------------------------------------------------
    */

    public function verifierAdmin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'verifier_admin_id'
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
            'status',
            self::STATUS_PENDING
        );
    }


    public function scopeNeedsMoreInformation(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_NEEDS_MORE_INFORMATION
        );
    }


    public function scopeVerified(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_VERIFIED
        );
    }


    public function scopeCompleted(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_COMPLETED
        );
    }


    public function scopeArchived(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_ARCHIVED
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVE VERIFICATION QUEUE
    |--------------------------------------------------------------------------
    |
    | These records may still require admin action.
    |
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'status',
            [
                self::STATUS_PENDING,
                self::STATUS_NEEDS_MORE_INFORMATION,
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


    public function needsMoreInformation(): bool
    {
        return $this->status ===
            self::STATUS_NEEDS_MORE_INFORMATION;
    }


    public function isVerified(): bool
    {
        return $this->status ===
            self::STATUS_VERIFIED;
    }


    public function isCompleted(): bool
    {
        return $this->status ===
            self::STATUS_COMPLETED;
    }


    public function isArchived(): bool
    {
        return $this->status ===
            self::STATUS_ARCHIVED;
    }


    /*
    |--------------------------------------------------------------------------
    | CAN ADMIN STILL REVIEW THIS?
    |--------------------------------------------------------------------------
    */

    public function isActionable(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_PENDING,
                self::STATUS_NEEDS_MORE_INFORMATION,
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS VALIDATION HELPER
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
            'status' =>
                self::STATUS_PENDING,

            'verifier_admin_id' =>
                null,

            'verified_at' =>
                null,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST MORE INFORMATION
    |--------------------------------------------------------------------------
    */

    public function markAsNeedsMoreInformation(
        ?string $notes = null
    ): bool {
        return $this->update([
            'status' =>
                self::STATUS_NEEDS_MORE_INFORMATION,

            'notes' =>
                $notes ?? $this->notes,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MARK AS VERIFIED
    |--------------------------------------------------------------------------
    */

    public function markAsVerified(
        int $adminId,
        ?string $notes = null,
        array $checklist = []
    ): bool {
        $currentChecklist =
            is_array($this->checklist)
                ? $this->checklist
                : [];

        return $this->update([
            'status' =>
                self::STATUS_VERIFIED,

            'verifier_admin_id' =>
                $adminId,

            'verified_at' =>
                now(),

            'notes' =>
                $notes ?? $this->notes,

            'checklist' =>
                array_merge(
                    $currentChecklist,
                    $checklist,
                    [
                        'verified_by_admin' =>
                            $adminId,
                    ]
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MARK TRANSACTION COMPLETED
    |--------------------------------------------------------------------------
    */

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' =>
                self::STATUS_COMPLETED,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE VERIFICATION
    |--------------------------------------------------------------------------
    */

    public function markAsArchived(): bool
    {
        return $this->update([
            'status' =>
                self::STATUS_ARCHIVED,
        ]);
    }
}