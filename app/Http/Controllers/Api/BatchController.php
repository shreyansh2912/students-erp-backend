<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchRequest;
use App\Models\Batch;
use App\Models\Organization;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Get all batches
     */
    public function index(Request $request): JsonResponse
    {
        $query = Batch::with('organization', 'students');

        // Filter by organization if provided
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $batches = $query->get();

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    /**
     * Create a new batch
     */
    public function store(BatchRequest $request): JsonResponse
    {
        $batch = Batch::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Batch created successfully',
            'data' => $batch,
        ], 201);
    }

    /**
     * Get batch details
     */
    public function show(Batch $batch): JsonResponse
    {
        $batch->load(['organization', 'students.user', 'exams']);

        return response()->json([
            'success' => true,
            'data' => $batch,
        ]);
    }

    /**
     * Update batch
     */
    public function update(BatchRequest $request, Batch $batch): JsonResponse
    {
        $batch->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Batch updated successfully',
            'data' => $batch,
        ]);
    }

    /**
     * Delete batch
     */
    public function destroy(Batch $batch): JsonResponse
    {
        $batch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully',
        ]);
    }

    /**
     * Add student to batch
     */
    public function addStudent(Request $request, Batch $batch): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        try {
            $student = Student::findOrFail($request->student_id);
            $this->studentService->addToBatch($student, $batch);

            return response()->json([
                'success' => true,
                'message' => 'Student added to batch successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove student from batch
     */
    public function removeStudent(Request $request, Batch $batch): JsonResponse
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $student = Student::findOrFail($request->student_id);
        $this->studentService->removeFromBatch($student, $batch);

        return response()->json([
            'success' => true,
            'message' => 'Student removed from batch successfully',
        ]);
    }
}
