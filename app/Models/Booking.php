<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $student_id
 * @property int|null $facility_id
 * @property string|null $date
 * @property string|null $time_in
 * @property string|null $time_out
 * @property string|null $year_section
 * @property string|null $status
 * @property string|null $purpose
 */
class Booking extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_id',
        'facility_id',
        'year_section',
        'date',
        'time_in',
        'time_out',
        'purpose',
        'status',
        'administrator_name'
    ];

    /**
     * Define a relationship with the Student model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student(): BelongsTo
    {
        // Links the student_id in bookings to the id in students table
        return $this->belongsTo(Student::class);
    }

    /**
     * Define a relationship with the Facility model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function facility(): BelongsTo
    {
        // Links the facility_id in bookings to the id in facilities table
        return $this->belongsTo(Facility::class, 'facility_id');
    }

    /**
     * Define a method to set the status to "Pending" by default if not provided.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            // If status is not set, set it to 'PENDING' by default
            if (empty($booking->status)) {
                $booking->status = 'PENDING';
            }
        });
    }
}
