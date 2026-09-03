<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $archive_type
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class ArchiveItem extends Model
{
    use HasFactory;

    protected $table = 'archive_items';

    protected $fillable = [
        'report_id',
        'status',
        'archive_type',
        'reason',
        'archived_by_admin_id',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function archivedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'archived_by_admin_id');
    }
}
