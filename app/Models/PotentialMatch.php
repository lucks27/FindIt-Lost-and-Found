<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotentialMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'lost_report_id',
        'found_report_id',
        'score',
        'status',
        'review_notes',
        'source',
        'reviewed_by_admin_id',
        'reviewed_at',
        'created_by_student_id',
    ];

    public function lostReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'lost_report_id');
    }

    public function foundReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'found_report_id');
    }
}
