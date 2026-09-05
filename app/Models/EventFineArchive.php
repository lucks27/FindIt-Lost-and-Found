<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFineArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_fine_id',
        'student_id',
        'student_name',
        'school_id',
        'event_name',
        'original_amount',
        'amount_to_pay',
        'paid_amount',
        'remaining_amount',
        'status',
        'reason',
        'paid_at',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'amount_to_pay' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(EventFine::class, 'event_fine_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
