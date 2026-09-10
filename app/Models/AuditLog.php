<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $action
 * @property string|null $ip_address
 * @property string|null $subject_type
 * @property string|null $subject_label
 * @property array<string, mixed>|null $details
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_admin_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'old_values',
        'new_values',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
        'details' => 'array',
    ];

    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_id');
    }
}
