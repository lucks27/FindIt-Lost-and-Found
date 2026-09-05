<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventFineEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fine_amount',
        'is_active',
        'created_by_admin_id',
    ];

    protected $casts = [
        'fine_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EventFine::class, 'event_fine_event_id');
    }
}
