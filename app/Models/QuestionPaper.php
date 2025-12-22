<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionPaper extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'title',
        'subject',
        'total_marks',
        'created_by',
    ];

    /**
     * Get the organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the creator (teacher)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get questions in this paper
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'paper_id');
    }

    /**
     * Get exams using this paper
     */
    public function exams()
    {
        return $this->hasMany(Exam::class, 'paper_id');
    }

    /**
     * Check if paper is locked (used in published exam)
     * BUSINESS RULE: Papers cannot be edited once linked to a published exam
     */
    public function isLocked(): bool
    {
        return $this->exams()->where('status', '!=', 'draft')->exists();
    }
}
