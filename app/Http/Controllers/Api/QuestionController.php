<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use App\Models\QuestionPaper;
use App\Services\QuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    protected QuestionService $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Get all questions for a paper
     */
    public function index(QuestionPaper $paper): JsonResponse
    {
        $questions = $paper->questions()->with('options')->get();

        return apiSuccess([
            'paper' => $paper,
            'questions' => $questions,
            'total_questions' => $questions->count(),
            'total_marks' => $questions->sum('marks'),
        ]);
    }

    /**
     * Create a new question
     */
    public function store(QuestionRequest $request): JsonResponse
    {
        try {
            $question = $this->questionService->createQuestion($request->validated());

            return apiSuccess(
                $question,
                'Question created successfully',
                201
            );
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }
    }

    /**
     * Get question details
     */
    public function show(Question $question): JsonResponse
    {
        $question->load(['options', 'paper']);

        return apiSuccess($question);
    }

    /**
     * Update question
     * BUSINESS RULE: Cannot update if paper is locked
     */
    public function update(QuestionRequest $request, Question $question): JsonResponse
    {
        try {
            $updated = $this->questionService->updateQuestion($question, $request->validated());

            return apiSuccess(
                $updated,
                'Question updated successfully'
            );
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }
    }

    /**
     * Delete question
     * BUSINESS RULE: Cannot delete if paper is locked
     */
    public function destroy(Question $question): JsonResponse
    {
        try {
            $this->questionService->deleteQuestion($question);

            return apiSuccess(null, 'Question deleted successfully');
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }
    }
}
