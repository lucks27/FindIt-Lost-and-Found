<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int|null $student_id
 * @property int|null $category_id
 * @property string|null $type
 * @property string|null $title
 * @property string|null $status
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class Report extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | REPORT TYPES
    |--------------------------------------------------------------------------
    */

    public const TYPE_LOST = 'lost';

    public const TYPE_FOUND = 'found';


    /*
    |--------------------------------------------------------------------------
    | LOST ITEM STATUSES
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING_APPROVAL =
        'pending_approval';

    public const STATUS_APPROVED =
        'approved';


    /*
    |--------------------------------------------------------------------------
    | FOUND ITEM STATUSES
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING_SURRENDER =
        'pending_surrender';

    public const STATUS_PENDING_VERIFICATION =
        'pending_verification';

    public const STATUS_VERIFIED =
        'verified';


    /*
    |--------------------------------------------------------------------------
    | SHARED WORKFLOW STATUSES
    |--------------------------------------------------------------------------
    */

    public const STATUS_POTENTIAL_MATCH_FOUND =
        'potential_match_found';

    public const STATUS_UNDER_CLAIM_REVIEW =
        'under_claim_review';

    public const STATUS_READY_FOR_RELEASE =
        'ready_for_release';

    public const STATUS_COMPLETED_RETURN =
        'completed_return';

    public const STATUS_ARCHIVED =
        'archived';

    public const STATUS_REJECTED =
        'rejected';


    /*
    |--------------------------------------------------------------------------
    | PUBLIC LOST ITEM STATUSES
    |--------------------------------------------------------------------------
    |
    | Only reports with these statuses may appear in the public mobile feed.
    |
    */

    public const PUBLIC_LOST_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_POTENTIAL_MATCH_FOUND,
        self::STATUS_UNDER_CLAIM_REVIEW,
    ];


    /*
    |--------------------------------------------------------------------------
    | PUBLIC FOUND ITEM STATUSES
    |--------------------------------------------------------------------------
    |
    | Found items must be verified before appearing publicly.
    |
    */

    public const PUBLIC_FOUND_STATUSES = [
        self::STATUS_VERIFIED,
        self::STATUS_POTENTIAL_MATCH_FOUND,
        self::STATUS_UNDER_CLAIM_REVIEW,
        self::STATUS_READY_FOR_RELEASE,
    ];


    /*
    |--------------------------------------------------------------------------
    | ALL LOST ITEM STATUSES
    |--------------------------------------------------------------------------
    */

    public const LOST_STATUSES = [
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_POTENTIAL_MATCH_FOUND,
        self::STATUS_UNDER_CLAIM_REVIEW,
        self::STATUS_COMPLETED_RETURN,
        self::STATUS_ARCHIVED,
        self::STATUS_REJECTED,
    ];


    /*
    |--------------------------------------------------------------------------
    | ALL FOUND ITEM STATUSES
    |--------------------------------------------------------------------------
    */

    public const FOUND_STATUSES = [
        self::STATUS_PENDING_SURRENDER,
        self::STATUS_PENDING_VERIFICATION,
        self::STATUS_VERIFIED,
        self::STATUS_POTENTIAL_MATCH_FOUND,
        self::STATUS_UNDER_CLAIM_REVIEW,
        self::STATUS_READY_FOR_RELEASE,
        self::STATUS_COMPLETED_RETURN,
        self::STATUS_ARCHIVED,
        self::STATUS_REJECTED,
    ];


    /*
    |--------------------------------------------------------------------------
    | FILLABLE
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'student_id',

        'category_id',
        'category',

        'type',
        'report_type',

        'title',
        'description',
        'color',

        'date_occurred',
        'time_found',

        'location',

        'ownership_proof',
        'additional_details',
        'private_identifying_details',

        'status',

        'review_notes',

        'approved_by_admin_id',
        'approved_at',

        'rejected_by_admin_id',
        'rejected_at',

        'published_at',
        'completed_at',
        'archived_at',

        'requires_images',

        'metadata',
    ];


    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'date_occurred' =>
            'date',

        'metadata' =>
            'array',

        'approved_at' =>
            'datetime',

        'rejected_at' =>
            'datetime',

        'published_at' =>
            'datetime',

        'completed_at' =>
            'datetime',

        'archived_at' =>
            'datetime',

        'requires_images' =>
            'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | APPENDED ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'student_full_name',
        'report_type',
        'category',
    ];


    /*
    |--------------------------------------------------------------------------
    | INITIAL STATUS
    |--------------------------------------------------------------------------
    |
    | Lost:
    |
    | pending_approval
    |
    | Found:
    |
    | pending_surrender
    |
    | The mobile application should never decide its own starting status.
    |
    */

    public static function initialStatusForType(
        ?string $type
    ): string {
        $type =
            strtolower(
                trim(
                    (string) $type
                )
            );

        if ($type === self::TYPE_FOUND) {
            return self::STATUS_PENDING_SURRENDER;
        }

        return self::STATUS_PENDING_APPROVAL;
    }


    /*
    |--------------------------------------------------------------------------
    | VALID STATUSES FOR REPORT TYPE
    |--------------------------------------------------------------------------
    */

    public static function statusesForType(
        ?string $type
    ): array {
        $type =
            strtolower(
                trim(
                    (string) $type
                )
            );

        if ($type === self::TYPE_FOUND) {
            return self::FOUND_STATUSES;
        }

        return self::LOST_STATUSES;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK VALID STATUS
    |--------------------------------------------------------------------------
    */

    public static function isValidStatusForType(
        ?string $type,
        ?string $status
    ): bool {
        return in_array(
            strtolower(
                trim(
                    (string) $status
                )
            ),
            self::statusesForType(
                $type
            ),
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OWNERSHIP PROOF
    |--------------------------------------------------------------------------
    */

    public function getOwnershipProofAttribute()
    {
        return
            $this->attributes['ownership_proof']
            ??
            $this->attributes['additional_details']
            ??
            null;
    }

    public function setOwnershipProofAttribute(
        $value
    ): void {
        $this->attributes['ownership_proof'] =
            $value;
    }


    /*
    |--------------------------------------------------------------------------
    | ADDITIONAL DETAILS
    |--------------------------------------------------------------------------
    |
    | Kept as an alias of ownership_proof for backward compatibility.
    |
    */

    public function getAdditionalDetailsAttribute()
    {
        return $this->ownership_proof;
    }

    public function setAdditionalDetailsAttribute(
        $value
    ): void {
        $this->ownership_proof =
            $value;
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(
            ItemCategory::class,
            'category_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IMAGES
    |--------------------------------------------------------------------------
    */

    public function images(): HasMany
    {
        return $this->hasMany(
            ReportImage::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VISIBLE POST
    |--------------------------------------------------------------------------
    */

    public function visiblePost(): HasOne
    {
        return $this->hasOne(
            VisiblePostedItem::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REWARDS
    |--------------------------------------------------------------------------
    */

    public function rewards(): HasMany
    {
        return $this->hasMany(
            Reward::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SURRENDER REQUEST
    |--------------------------------------------------------------------------
    */

    public function surrenderRequest(): HasOne
    {
        return $this->hasOne(
            SurrenderRequest::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ITEM VERIFICATION
    |--------------------------------------------------------------------------
    */

    public function itemVerification(): HasOne
    {
        return $this->hasOne(
            ItemVerification::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLAIM REVIEW
    |--------------------------------------------------------------------------
    */

    public function claimReview(): HasOne
    {
        return $this->hasOne(
            ClaimReview::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOST REPORT QUERY
    |--------------------------------------------------------------------------
    */

    public function scopeLost(
        Builder $query
    ): Builder {
        return $query->where(
            'type',
            self::TYPE_LOST
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FOUND REPORT QUERY
    |--------------------------------------------------------------------------
    */

    public function scopeFound(
        Builder $query
    ): Builder {
        return $query->where(
            'type',
            self::TYPE_FOUND
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT REPORTS
    |--------------------------------------------------------------------------
    */

    public function scopeForStudent(
        Builder $query,
        int $studentId
    ): Builder {
        return $query->where(
            'student_id',
            $studentId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PENDING REPORTS
    |--------------------------------------------------------------------------
    |
    | Lost:
    | pending_approval
    |
    | Found:
    | pending_surrender / pending_verification
    |
    */

    public function scopePending(
        Builder $query
    ): Builder {
        return $query->where(
            function (
                Builder $statusQuery
            ) {
                $statusQuery
                    ->where(
                        function (
                            Builder $lostQuery
                        ) {
                            $lostQuery
                                ->where(
                                    'type',
                                    self::TYPE_LOST
                                )
                                ->where(
                                    'status',
                                    self::STATUS_PENDING_APPROVAL
                                );
                        }
                    )
                    ->orWhere(
                        function (
                            Builder $foundQuery
                        ) {
                            $foundQuery
                                ->where(
                                    'type',
                                    self::TYPE_FOUND
                                )
                                ->whereIn(
                                    'status',
                                    [
                                        self::STATUS_PENDING_SURRENDER,
                                        self::STATUS_PENDING_VERIFICATION,
                                    ]
                                );
                        }
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN / ACTIVE REPORTS
    |--------------------------------------------------------------------------
    |
    | An active report has not yet been completed, archived, or rejected.
    |
    */

    public function scopeOpen(
        Builder $query
    ): Builder {
        return $query->whereNotIn(
            'status',
            [
                self::STATUS_COMPLETED_RETURN,
                self::STATUS_ARCHIVED,
                self::STATUS_REJECTED,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PUBLIC MOBILE FEED
    |--------------------------------------------------------------------------
    |
    | LOST:
    |
    | approved
    | potential_match_found
    | under_claim_review
    |
    | FOUND:
    |
    | verified
    | potential_match_found
    | under_claim_review
    | ready_for_release
    |
    | Pending, rejected, completed, and archived reports are excluded.
    |
    */

    public function scopePubliclyVisible(
        Builder $query
    ): Builder {
        return $query->where(
            function (
                Builder $publicQuery
            ) {
                $publicQuery
                    ->where(
                        function (
                            Builder $lostQuery
                        ) {
                            $lostQuery
                                ->where(
                                    'type',
                                    self::TYPE_LOST
                                )
                                ->whereIn(
                                    'status',
                                    self::PUBLIC_LOST_STATUSES
                                );
                        }
                    )
                    ->orWhere(
                        function (
                            Builder $foundQuery
                        ) {
                            $foundQuery
                                ->where(
                                    'type',
                                    self::TYPE_FOUND
                                )
                                ->whereIn(
                                    'status',
                                    self::PUBLIC_FOUND_STATUSES
                                );
                        }
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMPLETED REPORTS
    |--------------------------------------------------------------------------
    */

    public function scopeCompleted(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_COMPLETED_RETURN
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVED REPORTS
    |--------------------------------------------------------------------------
    */

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
    | REJECTED REPORTS
    |--------------------------------------------------------------------------
    */

    public function scopeRejected(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_REJECTED
        );
    }


    /*
    |--------------------------------------------------------------------------
    | IS LOST
    |--------------------------------------------------------------------------
    */

    public function isLost(): bool
    {
        return strtolower(
            (string) $this->type
        ) === self::TYPE_LOST;
    }


    /*
    |--------------------------------------------------------------------------
    | IS FOUND
    |--------------------------------------------------------------------------
    */

    public function isFound(): bool
    {
        return strtolower(
            (string) $this->type
        ) === self::TYPE_FOUND;
    }


    /*
    |--------------------------------------------------------------------------
    | IS PUBLICLY VISIBLE
    |--------------------------------------------------------------------------
    */

    public function isPubliclyVisible(): bool
    {
        if ($this->isLost()) {
            return in_array(
                $this->status,
                self::PUBLIC_LOST_STATUSES,
                true
            );
        }

        if ($this->isFound()) {
            return in_array(
                $this->status,
                self::PUBLIC_FOUND_STATUSES,
                true
            );
        }

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | IS TERMINAL
    |--------------------------------------------------------------------------
    */

    public function isTerminalStatus(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_COMPLETED_RETURN,
                self::STATUS_ARCHIVED,
                self::STATUS_REJECTED,
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT FULL NAME
    |--------------------------------------------------------------------------
    */

    public function getStudentFullNameAttribute(): string
    {
        return
            $this->student?->full_name
            ??
            'Unknown Student';
    }


    /*
    |--------------------------------------------------------------------------
    | REPORT TYPE ACCESSOR
    |--------------------------------------------------------------------------
    */

    public function getReportTypeAttribute(): string
    {
        return
            $this->attributes['report_type']
            ??
            $this->attributes['type']
            ??
            'unknown';
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY ACCESSOR
    |--------------------------------------------------------------------------
    |
    | Prefer the direct category column.
    |
    | If it is empty, fall back to ItemCategory using category_id.
    |
    */

    public function getCategoryAttribute(): ?string
    {
        if (
            !empty(
                $this->attributes['category']
            )
        ) {
            return
                $this->attributes['category'];
        }

        if (
            !empty(
                $this->attributes['category_id']
            )
        ) {
            return
                $this
                    ->categoryRelation()
                    ->first()
                    ?->name;
        }

        return null;
    }
}