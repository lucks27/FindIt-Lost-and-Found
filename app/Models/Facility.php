<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string|null $administrator_name
 * @property string|null $assigned_admin
 * @property string|null $facility_name
 * @property string|null $type
 * @property string|null $location
 * @property string|null $description
 * @property string|null $time_open
 * @property string|null $image
 * @property string|null $status
 */
class Facility extends Model
{
    use HasFactory;

    public $table = 'facilities';

    protected $fillable = [
        'administrator_name',
        'assigned_admin',
        'facility_name',
        'type',
        'location',
        'description',
        'time_open',
        'time_close',
        'operating_hours',
        'image',
        'status',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function admins()
    {
        return $this->hasMany(Admin::class);
    }

}
