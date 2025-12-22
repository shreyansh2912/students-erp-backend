<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Student;
use Carbon\Carbon;

class ExamAccessService
{
    /**
     * Check if student can access exam
     * BUSINESS RULE: Student must be in batch AND within time window
     *
     * @param Exam $exam
     * @param Student $student
     * @return bool
     */
    public function canAccessExam(Exam $exam, Student $student): bool
    {
        // Check if student is in the exam's batch
        if (!$exam->batch->students()->where('student_id', $student->id)->exists()) {
            return false;
        }

        // Check if exam is published
        if ($exam->status !== 'published') {
            return false;
        }

        // Check time window
        return $this->validateTimeWindow($exam);
    }

    /**
     * Validate if current time is within exam window
     * BUSINESS RULE: Access only between start_time and end_time
     *
     * @param Exam $exam
     * @return bool
     */
    public function validateTimeWindow(Exam $exam): bool
    {
        return Carbon::now()->between($exam->start_time, $exam->end_time);
    }

    /**
     * Get remaining time for an attempt in minutes
     * BUSINESS RULE: Auto-submit when duration_minutes elapsed
     *
     * @param \App\Models\ExamAttempt $attempt
     * @return int
     */
    public function getRemainingTime(\App\Models\ExamAttempt $attempt): int
    {
        return $attempt->time_remaining;
    }

    /**
     * Check if student already has an attempt for this exam
     * BUSINESS RULE: Prevent multiple attempts
     *
     * @param Exam $exam
     * @param Student $student
     * @return bool
     */
    public function hasExistingAttempt(Exam $exam, Student $student): bool
    {
        return $exam->attempts()->where('student_id', $student->id)->exists();
    }
}
