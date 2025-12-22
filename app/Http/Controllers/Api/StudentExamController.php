<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use App\Services\ExamAccessService;
use App\Services\ExamAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentExamController extends Controller
{
    protected ExamAccessService $accessService;
    protected ExamAttemptService $attemptService;

    public function __construct(
        ExamAccessService $accessService,
        ExamAttemptService $attemptService
    ) {
        $this->accessService = $accessService;
        $this->attemptService = $attemptService;
    }

    /**
     * Get available exams for student
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        // Get batches student belongs to
        $batchIds = $student->batches()->pluck('batches.id');

        // Get published exams for those batches
        $exams = Exam::published()
            ->whereIn('batch_id', $batchIds)
            ->with('paper', 'batch')
            ->get()
            ->map(function ($exam) use ($student) {
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'batch' => $exam->batch->name,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'duration_minutes' => $exam->duration_minutes,
                    'total_marks' => $exam->paper->total_marks,
                    'is_accessible' => $exam->isAccessible(),
                    'has_attempted' => $this->accessService->hasExistingAttempt($exam, $student),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $exams,
        ]);
    }

    /**
     * Get exam questions (only during active window)
     * BUSINESS RULE: Access only between start_time and end_time
     */
    public function show(Exam $exam, Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        if (!$this->accessService->canAccessExam($exam, $student)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this exam or it is not currently active',
            ], 403);
        }

        $exam->load('paper.questions.options');

        // Don't reveal correct answers
        $paper = $exam->paper;
        $paper->questions->each(function ($question) {
            $question->options->each(function ($option) {
                unset($option->is_correct);
            });
        });

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => $exam,
                'paper' => $paper,
            ],
        ]);
    }

    /**
     * Start exam attempt
     * BUSINESS RULE: Check access, prevent duplicate attempts
     */
    public function start(Exam $exam, Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        try {
            $attempt = $this->attemptService->startAttempt($exam, $student);

            return response()->json([
                'success' => true,
                'message' => 'Exam started successfully',
                'data' => [
                    'attempt' => $attempt,
                    'time_remaining' => $attempt->time_remaining,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Save answer during exam
     */
    public function saveAnswer(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'attempt_id' => ['required', 'exists:exam_attempts,id'],
            'question_id' => ['required', 'exists:questions,id'],
            'selected_option_id' => ['nullable', 'exists:question_options,id'],
            'answer_text' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $student = $user->studentProfile;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        try {
            $attempt = $student->examAttempts()->findOrFail($request->attempt_id);

            $answer = $this->attemptService->saveAnswer(
                $attempt,
                $request->question_id,
                $request->only(['selected_option_id', 'answer_text'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Answer saved successfully',
                'data' => [
                    'answer' => $answer,
                    'time_remaining' => $attempt->time_remaining,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Submit exam
     * BUSINESS RULE: Auto-grade MCQ, calculate score
     */
    public function submit(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'attempt_id' => ['required', 'exists:exam_attempts,id'],
        ]);

        $user = $request->user();
        $student = $user->studentProfile;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        try {
            $attempt = $student->examAttempts()->findOrFail($request->attempt_id);
            $submitted = $this->attemptService->submitAttempt($attempt);

            return response()->json([
                'success' => true,
                'message' => 'Exam submitted successfully',
                'data' => [
                    'attempt' => $submitted,
                    'score' => $submitted->score,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
