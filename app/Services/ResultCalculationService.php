<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\Student;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class ResultCalculationService
{
    /**
     * Auto-grade all MCQ answers for an attempt
     *
     * @param ExamAttempt $attempt
     * @return void
     */
    public function autoGradeMcqAnswers(ExamAttempt $attempt): void
    {
        $answers = $attempt->answers()->with(['question', 'selectedOption'])->get();

        foreach ($answers as $answer) {
            if ($answer->question->isMcq() && $answer->selected_option_id) {
                $this->autoGradeMcq($answer);
            }
        }
    }

    /**
     * Auto-grade a single MCQ answer
     *
     * @param ExamAnswer $answer
     * @return void
     */
    public function autoGradeMcq(ExamAnswer $answer): void
    {
        if (!$answer->question->isMcq()) {
            return;
        }

        $isCorrect = $answer->selectedOption && $answer->selectedOption->is_correct;
        
        $answer->update([
            'marks_awarded' => $isCorrect ? $answer->question->marks : 0,
        ]);
    }

    /**
     * Calculate total score for an attempt
     *
     * @param ExamAttempt $attempt
     * @return float
     */
    public function calculateScore(ExamAttempt $attempt): float
    {
        return $attempt->answers()->sum('marks_awarded') ?? 0;
    }

    /**
     * Get student performance metrics
     *
     * @param Student $student
     * @param Organization|null $organization
     * @return array
     */
    public function getStudentPerformance(Student $student, ?Organization $organization = null): array
    {
        $query = $student->examAttempts()->with('exam');

        if ($organization) {
            $query->whereHas('exam', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });
        }

        $attempts = $query->whereIn('status', ['submitted', 'auto_submitted'])->get();

        return [
            'total_exams' => $attempts->count(),
            'average_score' => $attempts->avg('score'),
            'highest_score' => $attempts->max('score'),
            'lowest_score' => $attempts->min('score'),
            'recent_attempts' => $attempts->sortByDesc('submitted_at')->take(5)->values(),
        ];
    }

    /**
     * Get exam results summary
     *
     * @param \App\Models\Exam $exam
     * @return array
     */
    public function getExamResults(\App\Models\Exam $exam): array
    {
        $attempts = $exam->attempts()
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->with('student')
            ->get();

        return [
            'total_students' => $exam->batch->students()->count(),
            'total_attempts' => $attempts->count(),
            'average_score' => $attempts->avg('score'),
            'highest_score' => $attempts->max('score'),
            'lowest_score' => $attempts->min('score'),
            'pass_rate' => $this->calculatePassRate($attempts),
            'attempts' => $attempts,
        ];
    }

    /**
     * Calculate pass rate (assuming 40% is passing)
     *
     * @param \Illuminate\Database\Eloquent\Collection $attempts
     * @return float
     */
    protected function calculatePassRate($attempts): float
    {
        if ($attempts->isEmpty()) {
            return 0;
        }

        $totalMarks = $attempts->first()->exam->paper->total_marks;
        $passingMarks = $totalMarks * 0.4;

        $passedCount = $attempts->filter(function ($attempt) use ($passingMarks) {
            return $attempt->score >= $passingMarks;
        })->count();

        return ($passedCount / $attempts->count()) * 100;
    }
}
