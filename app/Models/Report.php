<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int|null $student_id
 * @property string|null $type
 * @property string|null $title
 * @property string|null $status
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class Report extends Model
{
    use HasFactory;

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

    protected $casts = [
        'date_occurred' => 'date',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'published_at' => 'datetime',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'student_full_name',
        'report_type',
        'category',
    ];

    public function getOwnershipProofAttribute()
    {
        return $this->attributes['ownership_proof'] ?? $this->attributes['additional_details'] ?? null;
    }

    public function setOwnershipProofAttribute($value): void
    {
        $this->attributes['ownership_proof'] = $value;
    }

    public function getAdditionalDetailsAttribute()
    {
        return $this->getAttribute('ownership_proof');
    }

    public function setAdditionalDetailsAttribute($value): void
    {
        $this->setAttribute('ownership_proof', $value);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReportImage::class);
    }

    public function visiblePost(): HasOne
    {
        return $this->hasOne(VisiblePostedItem::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function surrenderRequest(): HasOne
    {
        return $this->hasOne(SurrenderRequest::class);
    }

    public function itemVerification(): HasOne
    {
        return $this->hasOne(ItemVerification::class);
    }

    public function claimReview(): HasOne
    {
        return $this->hasOne(ClaimReview::class);
    }

    public function scopeLost($query)
    {
        return $query->where('type', 'lost');
    }

    public function scopeFound($query)
    {
        return $query->where('type', 'found');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Get the student's full name.
     */
    public function getStudentFullNameAttribute(): string
    {
        return $this->student?->full_name ?? 'Unknown Student';
    }

    public function getReportTypeAttribute(): string
    {
        return $this->attributes['report_type'] ?? $this->attributes['type'] ?? 'unknown';
    }

    public function getCategoryAttribute(): ?string
    {
        if (!empty($this->attributes['category'])) {
            return $this->attributes['category'];
        }

        if (!empty($this->attributes['category_id'])) {
            $relation = $this->category()->first();
            return $relation?->name;
        }

        return null;
    }
}
