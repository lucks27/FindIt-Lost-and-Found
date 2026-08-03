<?php

namespace App\Models;

use App\Models\Facility;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'facility_id', // Links admin to a specific facility
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
        static::created(function (Admin $admin) {
            if ($admin->role === 'admin' && $admin->facility_id) {
                Facility::where('id', $admin->facility_id)
                    ->update([
                        'assigned_admin' => $admin->name,
                        'administrator_name' => $admin->name,
                    ]);
            }
        });

        static::updated(function (Admin $admin) {
            $newName = $admin->name;
            $oldName = $admin->getOriginal('name');
            $oldFacilityId = $admin->getOriginal('facility_id');
            $newFacilityId = $admin->facility_id;

            if ($admin->role === 'admin') {
                if ($oldFacilityId && $oldFacilityId !== $newFacilityId) {
                    Facility::where('id', $oldFacilityId)->update([
                        'assigned_admin' => null,
                        'administrator_name' => null,
                    ]);
                }

                if ($newFacilityId) {
                    Facility::where('id', $newFacilityId)->update([
                        'assigned_admin' => $newName,
                        'administrator_name' => $newName,
                    ]);
                }

                if ($admin->wasChanged('name') && $newFacilityId) {
                    Facility::where('id', $newFacilityId)->update([
                        'assigned_admin' => $newName,
                        'administrator_name' => $newName,
                    ]);
                }
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
     * Get the facility associated with the admin.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id');
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
