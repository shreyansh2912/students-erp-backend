<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use App\Services\ResultCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    protected ResultCalculationService $resultService;

    public function __construct(ResultCalculationService $resultService)
    {
        $this->resultService = $resultService;
    }

    /**
     * Get all results for an exam (teachers only)
     */
    public function examResults(Exam $exam): JsonResponse
    {
        $results = $this->resultService->getExamResults($exam);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get student's result for a specific exam
     */
    public function studentExamResult(Exam $exam, Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->studentProfile;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        $attempt = $exam->attempts()
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'auto_submitted'])
            ->with('answers.question', 'answers.selectedOption')
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'No submitted attempt found for this exam',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $attempt,
                'score' => $attempt->score,
                'total_marks' => $exam->paper->total_marks,
                'percentage' => ($attempt->score / $exam->paper->total_marks) * 100,
                'status' => $attempt->status,
                'submitted_at' => $attempt->submitted_at,
            ],
        ]);
    }

    /**
     * Get student's overall performance
     */
    public function studentPerformance(Request $request, ?int $studentId = null): JsonResponse
    {
        $user = $request->user();

        // If student_id is provided and user is teacher, get that student's performance
        // Otherwise get the authenticated student's performance
        if ($studentId && $user->isTeacher()) {
            $student = Student::findOrFail($studentId);
        } else {
            $student = $user->studentProfile;
        }

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found',
            ], 404);
        }

        $performance = $this->resultService->getStudentPerformance($student, null);

        return response()->json([
            'success' => true,
            'data' => $performance,
        ]);
    }
}
