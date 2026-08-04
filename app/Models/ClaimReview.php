<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'claimant_student_id',
        'review_status',
        'evidence',
        'notes',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function claimantStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'claimant_student_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }
}
