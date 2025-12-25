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

        return response()->json([
            'success' => true,
            'data' => [
                'paper' => $paper,
                'questions' => $questions,
                'total_questions' => $questions->count(),
                'total_marks' => $questions->sum('marks'),
            ],
        ]);
    }

    /**
     * Create a new question
     */
    public function store(QuestionRequest $request): JsonResponse
    {
        try {
            $question = $this->questionService->createQuestion($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data' => $question,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get question details
     */
    public function show(Question $question): JsonResponse
    {
        $question->load(['options', 'paper']);

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }

    /**
     * Update question
     * BUSINESS RULE: Cannot update if paper is locked
     */
    public function update(QuestionRequest $request, Question $question): JsonResponse
    {
        try {
            $updated = $this->questionService->updateQuestion($question, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
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

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
