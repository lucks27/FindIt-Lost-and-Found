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
        'report_type',
        'category',
        'title',
        'description',
        'location',
        'event_date',
        'status',
        'reward_amount',
        'reward_type',
        'image',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reward_amount' => 'decimal:2',
        'event_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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
        return $query->where('report_type', 'lost');
    }

    public function scopeFound($query)
    {
        return $query->where('report_type', 'found');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
