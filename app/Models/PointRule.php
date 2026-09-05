<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $category_name
 * @property string|null $item_category
 * @property bool|null $active
 * @property bool|null $is_active
 */
class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'item_category',
        'category_id',
        'base_points',
        'active',
        'is_active',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'base_points' => 'integer',
        'active' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
