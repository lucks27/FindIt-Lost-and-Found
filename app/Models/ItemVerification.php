<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'verifier_admin_id',
        'status',
        'checklist',
        'notes',
        'verified_at',
    ];

    protected $casts = [
        'checklist' => 'array',
        'verified_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function verifierAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verifier_admin_id');
    }
}
