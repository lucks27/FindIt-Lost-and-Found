<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_category',
        'base_points',
        'active',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'base_points' => 'integer',
        'active' => 'boolean',
    ];

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
