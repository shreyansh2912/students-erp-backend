<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ExamAttemptService
{
    protected ExamAccessService $accessService;
    protected ResultCalculationService $resultService;

    public function __construct(
        ExamAccessService $accessService,
        ResultCalculationService $resultService
    ) {
        $this->accessService = $accessService;
        $this->resultService = $resultService;
    }

    /**
     * Start an exam attempt
     * BUSINESS RULE: Check access, prevent duplicate attempts
     *
     * @param Exam $exam
     * @param Student $student
     * @return ExamAttempt
     */
    public function startAttempt(Exam $exam, Student $student): ExamAttempt
    {
        // Validate access
        if (!$this->accessService->canAccessExam($exam, $student)) {
            throw new \Exception('You do not have access to this exam or it is not currently active');
        }

        // Check for existing attempt
        if ($this->accessService->hasExistingAttempt($exam, $student)) {
            throw new \Exception('You have already attempted this exam');
        }

        // Create attempt
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Save or update an answer
     *
     * @param ExamAttempt $attempt
     * @param int $questionId
     * @param array $data Contains: selected_option_id OR answer_text
     * @return ExamAnswer
     */
    public function saveAnswer(ExamAttempt $attempt, int $questionId, array $data): ExamAnswer
    {
        if (!$attempt->isInProgress()) {
            throw new \Exception('Cannot modify answers for a submitted exam');
        }

        // Check if time expired
        if ($attempt->hasExpired()) {
            $this->autoSubmitAttempt($attempt);
            throw new \Exception('Time has expired, exam has been auto-submitted');
        }

        // Update or create answer
        return ExamAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'question_id' => $questionId,
            ],
            [
                'selected_option_id' => $data['selected_option_id'] ?? null,
                'answer_text' => $data['answer_text'] ?? null,
            ]
        );
    }

    /**
     * Submit exam attempt
     * BUSINESS RULE: Calculate score and mark as submitted
     *
     * @param ExamAttempt $attempt
     * @return ExamAttempt
     */
    public function submitAttempt(ExamAttempt $attempt): ExamAttempt
    {
        if (!$attempt->isInProgress()) {
            throw new \Exception('Exam has already been submitted');
        }

        return DB::transaction(function () use ($attempt) {
            // Auto-grade MCQ questions
            $this->resultService->autoGradeMcqAnswers($attempt);

            // Calculate total score
            $score = $this->resultService->calculateScore($attempt);

            // Update attempt
            $attempt->update([
                'submitted_at' => now(),
                'score' => $score,
                'status' => 'submitted',
            ]);

            return $attempt->fresh();
        });
    }

    /**
     * Auto-submit exam when time expires
     * BUSINESS RULE: Auto-submit after duration_minutes
     *
     * @param ExamAttempt $attempt
     * @return ExamAttempt
     */
    public function autoSubmitAttempt(ExamAttempt $attempt): ExamAttempt
    {
        if (!$attempt->isInProgress()) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt) {
            // Auto-grade MCQ questions
            $this->resultService->autoGradeMcqAnswers($attempt);

            // Calculate total score
            $score = $this->resultService->calculateScore($attempt);

            // Update attempt
            $attempt->update([
                'submitted_at' => now(),
                'score' => $score,
                'status' => 'auto_submitted',
            ]);

            return $attempt->fresh();
        });
    }
}
