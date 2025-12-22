<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\Organization;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Get all students in an organization
     */
    public function index(Organization $organization): JsonResponse
    {
        $students = $organization->students()->with('user', 'batches')->get();

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    /**
     * Create a new student
     * BUSINESS RULE: Auto-creates user if doesn't exist
     */
    public function store(StudentRequest $request, Organization $organization): JsonResponse
    {
        try {
            $student = $this->studentService->createStudent(
                $organization,
                $request->validated()
            );

            $student->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => $student,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get student details
     */
    public function show(Student $student): JsonResponse
    {
        $student->load(['user', 'organization', 'batches', 'examAttempts.exam']);

        return response()->json([
            'success' => true,
            'data' => $student,
        ]);
    }

    /**
     * Update student
     */
    public function update(StudentRequest $request, Student $student): JsonResponse
    {
        try {
            $updated = $this->studentService->updateStudent($student, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
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
     * Delete student
     */
    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully',
        ]);
    }
}
