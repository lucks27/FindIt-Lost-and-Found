<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportImage extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | FILLABLE
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'report_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'is_primary',
    ];


    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
    ];


    /*
    |--------------------------------------------------------------------------
    | APPENDED ATTRIBUTES
    |--------------------------------------------------------------------------
    |
    | This makes every image returned by the API include:
    |
    | "url": "/storage/report-images/example.jpg"
    |
    */

    protected $appends = [
        'url',
    ];


    /*
    |--------------------------------------------------------------------------
    | REPORT RELATIONSHIP
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
    | IMAGE URL
    |--------------------------------------------------------------------------
    */

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Already a complete URL
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with($this->file_path, 'http://') ||
            str_starts_with($this->file_path, 'https://')
        ) {
            return $this->file_path;
        }


        /*
        |--------------------------------------------------------------------------
        | Remove accidental prefixes
        |--------------------------------------------------------------------------
        */

        $path = ltrim(
            $this->file_path,
            '/'
        );

        if (
            str_starts_with(
                $path,
                'storage/'
            )
        ) {
            $path = substr(
                $path,
                strlen('storage/')
            );
        }

        if (
            str_starts_with(
                $path,
                'public/'
            )
        ) {
            $path = substr(
                $path,
                strlen('public/')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Laravel Public Storage URL
        |--------------------------------------------------------------------------
        */

        return Storage::disk(
            'public'
        )->url(
            $path
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FILE EXISTS
    |--------------------------------------------------------------------------
    */

    public function fileExists(): bool
    {
        if (!$this->file_path) {
            return false;
        }

        $path = ltrim(
            $this->file_path,
            '/'
        );

        if (
            str_starts_with(
                $path,
                'storage/'
            )
        ) {
            $path = substr(
                $path,
                strlen('storage/')
            );
        }

        if (
            str_starts_with(
                $path,
                'public/'
            )
        ) {
            $path = substr(
                $path,
                strlen('public/')
            );
        }

        return Storage::disk(
            'public'
        )->exists(
            $path
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRIMARY IMAGE
    |--------------------------------------------------------------------------
    */

    public function isPrimary(): bool
    {
        return (bool) $this->is_primary;
    }
}