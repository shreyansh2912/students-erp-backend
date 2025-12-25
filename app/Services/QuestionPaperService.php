<?php

namespace App\Services;

use App\Models\QuestionPaper;
use Illuminate\Support\Facades\DB;

class QuestionPaperService
{
    /**
     * Create a new question paper
     *
     * @param array $data
     * @return QuestionPaper
     */
    public function createQuestionPaper(array $data): QuestionPaper
    {
        return QuestionPaper::create([
            'organization_id' => $data['organization_id'],
            'title' => $data['title'],
            'subject' => $data['subject'],
            'total_marks' => $data['total_marks'],
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update question paper
     * BUSINESS RULE: Cannot update if locked (linked to published exam)
     *
     * @param QuestionPaper $paper
     * @param array $data
     * @return QuestionPaper
     */
    public function updateQuestionPaper(QuestionPaper $paper, array $data): QuestionPaper
    {
        if ($paper->isLocked()) {
            throw new \Exception('Cannot update question paper that is linked to a published exam');
        }

        $paper->update([
            'title' => $data['title'],
            'subject' => $data['subject'],
            'total_marks' => $data['total_marks'],
        ]);

        return $paper->fresh();
    }

    /**
     * Delete question paper
     * BUSINESS RULE: Cannot delete if linked to published exam
     *
     * @param QuestionPaper $paper
     * @return void
     */
    public function deleteQuestionPaper(QuestionPaper $paper): void
    {
        if ($paper->isLocked()) {
            throw new \Exception('Cannot delete question paper that is linked to a published exam');
        }

        // Delete all questions and their options
        DB::transaction(function () use ($paper) {
            foreach ($paper->questions as $question) {
                $question->options()->delete();
                $question->delete();
            }
            $paper->delete();
        });
    }

    /**
     * Calculate total marks from questions
     *
     * @param QuestionPaper $paper
     * @return int
     */
    public function calculateTotalMarks(QuestionPaper $paper): int
    {
        return $paper->questions()->sum('marks');
    }

    /**
     * Recalculate and update total marks
     *
     * @param QuestionPaper $paper
     * @return QuestionPaper
     */
    public function recalculateTotalMarks(QuestionPaper $paper): QuestionPaper
    {
        $totalMarks = $this->calculateTotalMarks($paper);
        $paper->update(['total_marks' => $totalMarks]);
        
        return $paper->fresh();
    }
}
