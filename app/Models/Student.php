<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'students';
    protected $primaryKey = 'id';

    protected $fillable = [
        'student_id',
        'name',
        'course',
        'email',
        'password',
        'year_level',
        'department',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // --- ADD THIS SECTION HERE ---
    protected $casts = [
        'password' => 'hashed',
    ];
    // -----------------------------

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_student_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Automatically hash passwords when set.
     */
    public function setPasswordAttribute($value)
    {
        if (!$value) {
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }
}
