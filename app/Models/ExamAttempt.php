<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'started_at',
        'submitted_at',
        'score',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    /**
     * Get the exam
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get answers
     */
    public function answers()
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * Get time remaining in minutes
     * BUSINESS RULE: Auto-submit after duration_minutes
     */
    public function getTimeRemainingAttribute(): int
    {
        if ($this->status !== 'in_progress') {
            return 0;
        }

        $elapsedMinutes = $this->started_at->diffInMinutes(now());
        $remaining = $this->exam->duration_minutes - $elapsedMinutes;

        return max(0, $remaining);
    }

    /**
     * Check if time has expired
     */
    public function hasExpired(): bool
    {
        return $this->time_remaining <= 0;
    }

    /**
     * Check if attempt is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if attempt is submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'auto_submitted']);
    }
}
