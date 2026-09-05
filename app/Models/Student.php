<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string|null $student_id
 * @property string|null $student_id_number
 * @property string|null $name
 * @property string|null $email
 * @property string|null $profile_photo
 * @property int|null $reports_count
 */
class Student extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'students';
    protected $primaryKey = 'id';

    protected $fillable = [
        'student_id_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'course',
        'department',
        'year_level',
        'profile_photo',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'full_name',
        'name',
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

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'recipient_id')
            ->where('recipient_role', 'student');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
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

    /**
     * Get the student's full name (first name to last name).
     */
    public function getFullNameAttribute(): string
    {
        $names = [$this->first_name];

        if ($this->middle_name) {
            $names[] = $this->middle_name;
        }

        $names[] = $this->last_name;

        return implode(' ', $names);
    }

    public function getNameAttribute(): string
    {
        return $this->full_name ?? trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ], fn($value) => $value !== null && $value !== '')));
    }
}
