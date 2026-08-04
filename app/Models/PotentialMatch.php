<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PotentialMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_report_id',
        'matched_report_id',
        'score',
        'status',
        'notes',
    ];

    public function sourceReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'source_report_id');
    }

    public function matchedReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'matched_report_id');
    }
}
