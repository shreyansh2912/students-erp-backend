<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'paper_id',
        'type',
        'question_text',
        'marks',
    ];

    /**
     * Get the paper
     */
    public function paper()
    {
        return $this->belongsTo(QuestionPaper::class, 'paper_id');
    }

    /**
     * Get options (for MCQ only)
     */
    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    /**
     * Get student answers
     */
    public function answers()
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * Check if question is MCQ
     */
    public function isMcq(): bool
    {
        return $this->type === 'mcq';
    }

    /**
     * Check if question is short answer
     */
    public function isShortAnswer(): bool
    {
        return $this->type === 'short_answer';
    }
}
