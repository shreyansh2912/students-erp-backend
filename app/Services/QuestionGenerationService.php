<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionPaper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Question Generation Service
 * 
 * Handles business logic for AI-powered question generation and management
 */
class QuestionGenerationService
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate questions and optionally save to a question paper
     *
     * @param array $params Generation parameters
     * @param QuestionPaper|null $paper Optional paper to save questions to
     * @return array Generated questions (with IDs if saved)
     */
    public function generateQuestions(array $params, ?QuestionPaper $paper = null): array
    {
        // Validate parameters
        $this->validateParams($params);

        // Generate questions using AI
        $generatedQuestions = $this->aiService->generateQuestions($params);

        // Save to database if paper is provided
        if ($paper) {
            $generatedQuestions = $this->saveQuestionsToPaper($generatedQuestions, $paper);
        }

        return $generatedQuestions;
    }

    /**
     * Generate questions by topic for a specific paper
     *
     * @param QuestionPaper $paper The question paper
     * @param string $topic Topic for questions
     * @param string $type Question type (mcq|short_answer)
     * @param string $difficulty Difficulty level
     * @param int $count Number of questions
     * @param int $marks Marks per question
     * @return array Saved questions with IDs
     */
    public function generateByTopic(
        QuestionPaper $paper,
        string $topic,
        string $type,
        string $difficulty,
        int $count,
        int $marks
    ): array {
        $params = [
            'topic' => $topic,
            'subject' => $paper->subject,
            'type' => $type,
            'difficulty' => $difficulty,
            'count' => $count,
            'marks' => $marks,
        ];

        return $this->generateQuestions($params, $paper);
    }

    /**
     * Refine an existing question using AI
     *
     * @param Question $question The question to refine
     * @param string|null $instructions Optional refinement instructions
     * @return string Refined question text
     */
    public function refineQuestion(Question $question, ?string $instructions = null): string
    {
        $prompt = "Refine and improve the following exam question:\n\n";
        $prompt .= "Question: {$question->question_text}\n";
        $prompt .= "Type: {$question->type}\n";
        $prompt .= "Marks: {$question->marks}\n";

        if ($instructions) {
            $prompt .= "\nInstructions: {$instructions}\n";
        }

        $prompt .= "\nProvide only the improved question text, nothing else.";

        return $this->aiService->chat($prompt);
    }

    /**
     * Generate MCQ options for a question
     *
     * @param string $questionText The question text
     * @param int $optionCount Number of options (default: 4)
     * @param int $correctCount Number of correct options (default: 1)
     * @return array Array of options with is_correct flag
     */
    public function generateOptions(
        string $questionText,
        int $optionCount = 4,
        int $correctCount = 1
    ): array {
        $prompt = "For the following multiple choice question, generate {$optionCount} options ";
        $prompt .= "({$correctCount} correct, " . ($optionCount - $correctCount) . " incorrect):\n\n";
        $prompt .= "Question: {$questionText}\n\n";
        $prompt .= "Respond ONLY with a JSON array in this format:\n";
        $prompt .= <<<JSON
[
  {"option_text": "Option A", "is_correct": false},
  {"option_text": "Option B", "is_correct": true},
  {"option_text": "Option C", "is_correct": false},
  {"option_text": "Option D", "is_correct": false}
]
JSON;

        $response = $this->aiService->chat($prompt);
        
        // Parse JSON response
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*/', '', $response);
        $response = trim($response);

        try {
            $options = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            return is_array($options) ? $options : [];
        } catch (\JsonException $e) {
            Log::error('Failed to parse option generation response', [
                'response' => $response,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Save generated questions to a question paper
     *
     * @param array $questions Generated questions
     * @param QuestionPaper $paper Target paper
     * @return array Saved questions with IDs
     */
    protected function saveQuestionsToPaper(array $questions, QuestionPaper $paper): array
    {
        DB::beginTransaction();

        try {
            $savedQuestions = [];
            $totalMarks = 0;

            foreach ($questions as $questionData) {
                // Create question
                $question = Question::create([
                    'paper_id' => $paper->id,
                    'type' => $questionData['type'],
                    'question_text' => $questionData['question_text'],
                    'marks' => $questionData['marks'],
                ]);

                $totalMarks += $questionData['marks'];

                // Create options for MCQ
                if ($questionData['type'] === 'mcq' && isset($questionData['options'])) {
                    foreach ($questionData['options'] as $optionData) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $optionData['option_text'],
                            'is_correct' => $optionData['is_correct'] ?? false,
                        ]);
                    }

                    // Load options relationship
                    $question->load('options');
                }

                $savedQuestions[] = $question;
            }

            // Update paper's total marks
            $paper->update(['total_marks' => $paper->total_marks + $totalMarks]);

            DB::commit();

            return $savedQuestions;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to save questions to paper', [
                'paper_id' => $paper->id,
                'error' => $e->getMessage()
            ]);
            
            throw new \RuntimeException('Failed to save questions: ' . $e->getMessage());
        }
    }

    /**
     * Validate question generation parameters
     *
     * @param array $params
     * @throws \InvalidArgumentException
     */
    protected function validateParams(array $params): void
    {
        $requiredFields = ['topic', 'type', 'difficulty', 'count', 'marks'];
        
        foreach ($requiredFields as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required parameter: {$field}");
            }
        }

        $validTypes = ['mcq', 'short_answer'];
        if (!in_array($params['type'], $validTypes)) {
            throw new \InvalidArgumentException("Invalid type. Must be one of: " . implode(', ', $validTypes));
        }

        $validDifficulties = ['easy', 'medium', 'hard'];
        if (!in_array($params['difficulty'], $validDifficulties)) {
            throw new \InvalidArgumentException("Invalid difficulty. Must be one of: " . implode(', ', $validDifficulties));
        }

        $minQuestions = config('ai.question_generation.min_questions', 1);
        $maxQuestions = config('ai.question_generation.max_questions', 50);
        
        if ($params['count'] < $minQuestions || $params['count'] > $maxQuestions) {
            throw new \InvalidArgumentException("Question count must be between {$minQuestions} and {$maxQuestions}");
        }
    }
}
