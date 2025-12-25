<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionPaperRequest;
use App\Models\QuestionPaper;
use App\Services\QuestionPaperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionPaperController extends Controller
{
    protected QuestionPaperService $paperService;

    public function __construct(QuestionPaperService $paperService)
    {
        $this->paperService = $paperService;
    }

    /**
     * Get all question papers
     */
    public function index(Request $request): JsonResponse
    {
        $query = QuestionPaper::with(['creator', 'organization']);

        // Filter by organization
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filter by subject
        if ($request->has('subject')) {
            $query->where('subject', $request->subject);
        }

        $papers = $query->orderBy('created_at', 'desc')->get();

        // Add metadata
        $papers->each(function ($paper) {
            $paper->is_locked = $paper->isLocked();
            $paper->question_count = $paper->questions()->count();
        });

        return apiSuccess($papers);
    }

    /**
     * Create a new question paper
     */
    public function store(QuestionPaperRequest $request): JsonResponse
    {
        try {
            $paper = $this->paperService->createQuestionPaper($request->validated());

            return apiSuccess(
                $paper->load(['creator', 'organization']),
                'Question paper created successfully',
                201
            );
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }
    }

    /**
     * Get question paper details
     */
    public function show(QuestionPaper $paper): JsonResponse
    {
        $paper->load([
            'creator',
            'organization',
            'questions.options',
            'exams'
        ]);

        $paper->is_locked = $paper->isLocked();
        $paper->question_count = $paper->questions->count();
        $paper->calculated_total_marks = $this->paperService->calculateTotalMarks($paper);

        return apiSuccess($paper);
    }

    /**
     * Update question paper
     * BUSINESS RULE: Cannot update if locked
     */
    public function update(QuestionPaperRequest $request, QuestionPaper $paper): JsonResponse
    {
        try {
            $updated = $this->paperService->updateQuestionPaper($paper, $request->validated());

            return apiSuccess(
                $updated->load(['creator', 'organization']),
                'Question paper updated successfully'
            );
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }
    }

    /**
     * Delete question paper
     * BUSINESS RULE: Cannot delete if linked to published exam
     */
    public function destroy(QuestionPaper $paper): JsonResponse
    {
        try {
            $this->paperService->deleteQuestionPaper($paper);

            return apiSuccess(null, 'Question paper deleted successfully');
        } catch (\Exception $e) {
            return apiError($e->getMessage());
        }
    }
}
