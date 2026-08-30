<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisiblePostedItem extends Model
{
    use HasFactory;

    protected $table = 'visible_posted_items';

    protected $fillable = [
        'report_id',
        'post_type',
        'is_visible',
        'posted_at',
        'expires_at',
        'posted_by_admin_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function postedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'posted_by_admin_id');
    }
}
