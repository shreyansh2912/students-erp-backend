<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamRequest;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * Get all exams
     */
    public function index(Request $request): JsonResponse
    {
        $query = Exam::with('organization', 'batch', 'paper');

        // Filter by organization
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filter by batch
        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $exams = $query->orderBy('start_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $exams,
        ]);
    }

    /**
     * Create a new exam
     */
    public function store(ExamRequest $request): JsonResponse
    {
        try {
            $exam = $this->examService->createExam($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Exam created successfully',
                'data' => $exam,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get exam details
     */
    public function show(Exam $exam): JsonResponse
    {
        $exam->load(['organization', 'batch', 'paper.questions.options', 'attempts']);

        return response()->json([
            'success' => true,
            'data' => $exam,
        ]);
    }

    /**
     * Update exam
     */
    public function update(ExamRequest $request, Exam $exam): JsonResponse
    {
        if ($exam->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update published or completed exams',
            ], 400);
        }

        $exam->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully',
            'data' => $exam,
        ]);
    }

    /**
     * Delete exam
     * BUSINESS RULE: Cannot delete if attempts exist
     */
    public function destroy(Exam $exam): JsonResponse
    {
        try {
            $this->examService->deleteExam($exam);

            return response()->json([
                'success' => true,
                'message' => 'Exam deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Publish exam
     * BUSINESS RULE: Locks the paper
     */
    public function publish(Exam $exam): JsonResponse
    {
        try {
            $published = $this->examService->publishExam($exam);

            return response()->json([
                'success' => true,
                'message' => 'Exam published successfully',
                'data' => $published,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
