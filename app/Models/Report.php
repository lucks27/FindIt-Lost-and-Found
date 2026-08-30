<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'category_id',
        'type',
        'title',
        'description',
        'color',
        'date_occurred',
        'location',
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
    ];

    protected $casts = [
        'date_occurred' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'published_at' => 'datetime',
        'completed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'student_full_name',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReportImage::class);
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
}
