<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'batch_id',
        'paper_id',
        'title',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the batch
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the question paper
     */
    public function paper()
    {
        return $this->belongsTo(QuestionPaper::class, 'paper_id');
    }

    /**
     * Get exam attempts
     */
    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Scope: Published exams only
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Active exams (within time window)
     * BUSINESS RULE: Students can only access exam between start_time and end_time
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                     ->where('start_time', '<=', now())
                     ->where('end_time', '>=', now());
    }

    /**
     * Check if exam is currently accessible
     */
    public function isAccessible(): bool
    {
        return $this->status === 'published' 
               && Carbon::now()->between($this->start_time, $this->end_time);
    }

    /**
     * Check if exam has started
     */
    public function hasStarted(): bool
    {
        return Carbon::now()->gte($this->start_time);
    }

    /**
     * Check if exam has ended
     */
    public function hasEnded(): bool
    {
        return Carbon::now()->gt($this->end_time);
    }

    /**
     * BUSINESS RULE: Cannot delete exam if any attempts exist
     */
    public function canBeDeleted(): bool
    {
        return $this->attempts()->count() === 0;
    }
}
