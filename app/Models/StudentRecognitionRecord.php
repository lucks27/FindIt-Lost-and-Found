<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRecognitionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'recognition_points',
        'peso_value',
        'reward_count',
        'report_count',
    ];

    protected $casts = [
        'recognition_points' => 'integer',
        'peso_value' => 'decimal:2',
        'reward_count' => 'integer',
        'report_count' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}