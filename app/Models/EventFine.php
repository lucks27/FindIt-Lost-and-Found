<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'report_id',
        'event_name',
        'reference_number',
        'amount',
        'status',
        'reason',
        'issued_by_admin_id',
        'due_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function issuedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'issued_by_admin_id');
    }
}
