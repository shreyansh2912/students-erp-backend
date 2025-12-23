<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateQuestionsRequest;
use App\Models\Question;
use App\Models\QuestionPaper;
use App\Services\QuestionGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    protected QuestionGenerationService $questionService;

    public function __construct(QuestionGenerationService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Generate questions using AI
     *
     * @param GenerateQuestionsRequest $request
     * @return JsonResponse
     */
    public function generateQuestions(GenerateQuestionsRequest $request): JsonResponse
    {
        try {
            $params = [
                'topic' => $request->topic,
                'subject' => $request->subject,
                'type' => $request->type,
                'difficulty' => $request->difficulty,
                'count' => $request->count,
                'marks' => $request->marks,
                'context' => $request->context,
            ];

            // Get paper if provided and user owns it
            $paper = null;
            if ($request->paper_id && $request->save_to_paper !== false) {
                $paper = QuestionPaper::where('id', $request->paper_id)
                    ->where('organization_id', $request->user()->organization_id)
                    ->firstOrFail();

                // Check if paper is locked
                if ($paper->isLocked()) {
                    return errorJson(
                        'Cannot add questions to a locked paper (used in published exams)',
                        null,
                        422
                    );
                }
            }

            // Generate questions
            $questions = $this->questionService->generateQuestions($params, $paper);

            $message = $paper 
                ? "Generated {$request->count} questions and saved to paper"
                : "Generated {$request->count} questions";

            return successJson([
                'questions' => $questions,
                'paper_id' => $paper?->id,
                'total_questions' => count($questions),
            ], $message);

        } catch (\InvalidArgumentException $e) {
            return errorJson($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            Log::error('Question generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'params' => $request->all()
            ]);

            return errorJson(
                'Failed to generate questions. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Refine an existing question using AI
     *
     * @param Request $request
     * @param Question $question
     * @return JsonResponse
     */
    public function refineQuestion(Request $request, Question $question): JsonResponse
    {
        $request->validate([
            'instructions' => 'nullable|string|max:500',
        ]);

        try {
            // Check if user owns the question
            $paper = $question->paper;
            if ($paper->organization_id !== $request->user()->organization_id) {
                return errorJson('Unauthorized', null, 403);
            }

            // Check if paper is locked
            if ($paper->isLocked()) {
                return errorJson(
                    'Cannot modify questions in a locked paper (used in published exams)',
                    null,
                    422
                );
            }

            $refinedText = $this->questionService->refineQuestion(
                $question,
                $request->instructions
            );

            return successJson([
                'original_text' => $question->question_text,
                'refined_text' => $refinedText,
            ], 'Question refined successfully');

        } catch (\Exception $e) {
            Log::error('Question refinement failed', [
                'error' => $e->getMessage(),
                'question_id' => $question->id,
                'user_id' => $request->user()->id,
            ]);

            return errorJson(
                'Failed to refine question. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Generate MCQ options for a question
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateOptions(Request $request): JsonResponse
    {
        $request->validate([
            'question_text' => 'required|string|max:1000',
            'option_count' => 'nullable|integer|min:2|max:6',
            'correct_count' => 'nullable|integer|min:1|max:2',
        ]);

        try {
            $options = $this->questionService->generateOptions(
                $request->question_text,
                $request->option_count ?? 4,
                $request->correct_count ?? 1
            );

            if (empty($options)) {
                return errorJson('Failed to generate options', null, 500);
            }

            return successJson([
                'options' => $options,
                'total_options' => count($options),
            ], 'Options generated successfully');

        } catch (\Exception $e) {
            Log::error('Option generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return errorJson(
                'Failed to generate options. Please try again.',
                null,
                500
            );
        }
    }
}
