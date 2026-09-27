<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurrenderRequest extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | STATUS CONSTANTS
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REJECTED = 'rejected';


    /*
    |--------------------------------------------------------------------------
    | FILLABLE
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'report_id',
        'student_id',
        'status',
        'deadline_at',
        'received_at',
        'received_by_admin_id',
        'notes',
    ];


    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'deadline_at' => 'datetime',
        'received_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | DEFAULT ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];


    /*
    |--------------------------------------------------------------------------
    | REPORT
    |--------------------------------------------------------------------------
    */

    public function report(): BelongsTo
    {
        return $this->belongsTo(
            Report::class,
            'report_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT
    |--------------------------------------------------------------------------
    */

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class,
            'student_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN WHO RECEIVED THE ITEM
    |--------------------------------------------------------------------------
    */

    public function receivedByAdmin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'received_by_admin_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS HELPERS
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_PENDING,
                self::STATUS_OVERDUE,
            ],
            true
        );
    }


    public function isReceived(): bool
    {
        return $this->status ===
            self::STATUS_RECEIVED;
    }


    public function isRejected(): bool
    {
        return $this->status ===
            self::STATUS_REJECTED;
    }


    /*
    |--------------------------------------------------------------------------
    | RECEIVE ITEM
    |--------------------------------------------------------------------------
    */

    public function markAsReceived(
        int $adminId
    ): bool {
        return $this->update([
            'status' =>
                self::STATUS_RECEIVED,

            'received_by_admin_id' =>
                $adminId,

            'received_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT ITEM
    |--------------------------------------------------------------------------
    */

    public function markAsRejected(): bool
    {
        return $this->update([
            'status' =>
                self::STATUS_REJECTED,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MARK OVERDUE
    |--------------------------------------------------------------------------
    */

    public function markAsOverdue(): bool
    {
        return $this->update([
            'status' =>
                self::STATUS_OVERDUE,
        ]);
    }
}