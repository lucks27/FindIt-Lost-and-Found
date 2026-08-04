<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $role
 * @property int|null $facility_id
 * @property string|null $profile_picture
 */
class Admin extends Authenticatable
{
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted()
    {
        static::creating(function (Admin $admin) {
            if (Admin::query()->exists()) {
                throw new \RuntimeException('Only one admin account can exist.');
            }
        });
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Get the notifications for the admin.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'recipient_admin_id');
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
