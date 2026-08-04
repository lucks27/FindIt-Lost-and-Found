<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurrenderRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'student_id',
        'status',
        'deadline_at',
        'received_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receivedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'received_by_admin_id');
    }
}
