<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFineConversion extends Model
{
    use HasFactory;

    protected $table = 'event_fine_conversions';

    protected $fillable = [
        'event_fine_id',
        'student_id',
        'requested_points',
        'conversion_rate',
        'credit_amount',
        'total_original_amount',
        'converted_points',
        'peso_value_converted',
        'remaining_balance',
        'status',
        'review_notes',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_points' => 'integer',
        'conversion_rate' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'total_original_amount' => 'decimal:2',
        'converted_points' => 'integer',
        'peso_value_converted' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function eventFine(): BelongsTo
    {
        return $this->belongsTo(EventFine::class, 'event_fine_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }
}
