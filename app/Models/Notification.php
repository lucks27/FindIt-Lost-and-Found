<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $student_id
 * @property int|null $booking_id
 * @property int|null $facility_id
 * @property string|null $type
 * @property string|null $title
 * @property string|null $message
 * @property string|null $action
 * @property string|null $review_status
 * @property array<string,mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property int|null $recipient_id
 * @property string|null $recipient_role
 * @property int|null $actor_admin_id
 * @property bool|null $is_read
 */
class Notification extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'booking_id',
        'type',
        'title',
        'message',
        'action',
        'status',
        'recipient_id',
        'facility_id',
        'facility_name',
        'booking_date',
        'time_in',
        'time_out',
        'actor_admin_id',
        'recipient_role',
        'subject_type',
        'subject_id',
        'review_status',
        'data',
        'is_read',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array', // Essential for the profile update changes we added
    ];

    /**
     * Append booking details to JSON response
     */
    protected $appends = [
        'facility_name',
        'booking_date',
        'time_in',
        'time_out',
        'status'
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }

    // =====================
    // ACCESSORS (Getters)
    // =====================

    /**
     * Get facility name from booking or direct field
     */
    public function getFacilityNameAttribute()
    {
        // Try to get from booking relationship first
        if ($this->booking && $this->booking->facility) {
            return $this->booking->facility->facility_name;
        }
        // Fall back to facility_name field if set directly
        return $this->attributes['facility_name'] ?? null;
    }

    /**
     * Get booking date from booking relationship
     */
    public function getBookingDateAttribute()
    {
        // Try to get from booking relationship first
        if ($this->booking) {
            return $this->booking->date;
        }
        // Fall back to date field if available
        return null;
    }

    /**
     * Get time_in from booking relationship
     */
    public function getTimeInAttribute()
    {
        if ($this->booking) {
            return $this->booking->time_in;
        }
        return $this->attributes['time_in'] ?? null;
    }

    /**
     * Get time_out from booking relationship
     */
    public function getTimeOutAttribute()
    {
        if ($this->booking) {
            return $this->booking->time_out;
        }
        return $this->attributes['time_out'] ?? null;
    }

    /**
     * Get status from booking relationship
     */
    public function getStatusAttribute()
    {
        if ($this->booking) {
            return $this->booking->status;
        }
        return $this->attributes['status'] ?? null;
    }
}
