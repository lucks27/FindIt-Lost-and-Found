<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'student_name',
        'school_id',
        'report_id',
        'event_fine_event_id',
        'event_name',
        'reference_number',
        'amount',
        'original_amount',
        'amount_to_pay',
        'paid_amount',
        'paid',
        'remaining_amount',
        'status',
        'reason',
        'issued_by_admin_id',
        'due_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'amount_to_pay' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(EventFineEvent::class, 'event_fine_event_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function issuedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'issued_by_admin_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(EventFineConversion::class, 'event_fine_id');
    }
}
