<?php

namespace App\Services;

use App\Models\Exam;
use Illuminate\Support\Facades\DB;

class ExamService
{
    /**
     * Create a new exam
     *
     * @param array $data
     * @return Exam
     */
    public function createExam(array $data): Exam
    {
        return Exam::create([
            'organization_id' => $data['organization_id'],
            'batch_id' => $data['batch_id'],
            'paper_id' => $data['paper_id'],
            'title' => $data['title'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $data['duration_minutes'],
            'status' => 'draft',
        ]);
    }

    /**
     * Publish an exam
     * BUSINESS RULE: Once published, paper cannot be edited
     *
     * @param Exam $exam
     * @return Exam
     */
    public function publishExam(Exam $exam): Exam
    {
        if ($exam->status !== 'draft') {
            throw new \Exception('Only draft exams can be published');
        }

        // Validate exam has a paper with questions
        if ($exam->paper->questions()->count() === 0) {
            throw new \Exception('Cannot publish exam with no questions');
        }

        $exam->update(['status' => 'published']);

        return $exam->fresh();
    }

    /**
     * Complete an exam
     *
     * @param Exam $exam
     * @return Exam
     */
    public function completeExam(Exam $exam): Exam
    {
        if ($exam->status !== 'published') {
            throw new \Exception('Only published exams can be completed');
        }

        $exam->update(['status' => 'completed']);

        return $exam->fresh();
    }

    /**
     * Delete an exam
     * BUSINESS RULE: Cannot delete if any attempts exist
     *
     * @param Exam $exam
     * @return void
     */
    public function deleteExam(Exam $exam): void
    {
        if (!$exam->canBeDeleted()) {
            throw new \Exception('Cannot delete exam with existing attempts');
        }

        $exam->delete();
    }
}
