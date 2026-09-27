<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Student Model
 *
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

    /*
    |--------------------------------------------------------------------------
    | TABLE CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $table = 'students';

    protected $primaryKey = 'id';

    /*
    |--------------------------------------------------------------------------
    | MASS ASSIGNABLE FIELDS
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'student_id_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'course',
        'department',
        'department_id',
        'year_level',
        'profile_photo',
        'status',
        'reward_count',
    ];

    /*
    |--------------------------------------------------------------------------
    | HIDDEN FIELDS
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | APPENDED ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'full_name',
        'name',
    ];

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE CASTS
    |--------------------------------------------------------------------------
    |
    | Laravel automatically hashes the password when a plain-text password
    | is assigned.
    |
    */

    protected $casts = [
        'password' => 'hashed',
        'reward_count' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function bookings()
    {
        return $this->hasMany(
            Booking::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    public function notifications(): HasMany
    {
        return $this
            ->hasMany(
                Notification::class,
                'recipient_id'
            )
            ->where(
                'recipient_role',
                'student'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    public function reports()
    {
        return $this->hasMany(
            Report::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REWARDS
    |--------------------------------------------------------------------------
    */

    public function rewards()
    {
        return $this->hasMany(
            Reward::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | POINT TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(
            PointTransaction::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT FINES
    |--------------------------------------------------------------------------
    */

    public function eventFines(): HasMany
    {
        return $this->hasMany(
            EventFine::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FINE CONVERSIONS
    |--------------------------------------------------------------------------
    */

    public function fineConversions(): HasMany
    {
        return $this->hasMany(
            EventFineConversion::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RECOGNITION RECORD
    |--------------------------------------------------------------------------
    */

    public function recognitionRecord(): HasOne
    {
        return $this->hasOne(
            StudentRecognitionRecord::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DEVICE TOKENS
    |--------------------------------------------------------------------------
    */

    public function deviceTokens()
    {
        return $this->hasMany(
            DeviceToken::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function department()
    {
        return $this->belongsTo(
            Department::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL NORMALIZATION
    |--------------------------------------------------------------------------
    |
    | All student emails are automatically saved in lowercase.
    |
    */

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] =
            strtolower(
                trim(
                    (string) $value
                )
            );
    }

    /*
    |--------------------------------------------------------------------------
    | FULL NAME
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        $names = [];

        if (!empty($this->first_name)) {
            $names[] =
                trim(
                    $this->first_name
                );
        }

        if (!empty($this->middle_name)) {
            $names[] =
                trim(
                    $this->middle_name
                );
        }

        if (!empty($this->last_name)) {
            $names[] =
                trim(
                    $this->last_name
                );
        }

        return implode(
            ' ',
            $names
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NAME
    |--------------------------------------------------------------------------
    */

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }
}