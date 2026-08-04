<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRelease extends Model
{
    use HasFactory;

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

    protected $casts = [
        'checklist' => 'array',
        'scheduled_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function claimantStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'claimant_student_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }
}
