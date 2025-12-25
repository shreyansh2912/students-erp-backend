<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionPaper;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /**
     * Create a new question with options (for MCQ)
     *
     * @param array $data
     * @return Question
     */
    public function createQuestion(array $data): Question
    {
        // Check if paper is locked
        $paper = QuestionPaper::findOrFail($data['paper_id']);
        if ($paper->isLocked()) {
            throw new \Exception('Cannot add questions to a locked question paper');
        }

        return DB::transaction(function () use ($data) {
            // Create question
            $question = Question::create([
                'paper_id' => $data['paper_id'],
                'question_text' => $data['question_text'],
                'question_type' => $data['question_type'],
                'marks' => $data['marks'],
            ]);

            // Create options for MCQ
            if ($data['question_type'] === 'mcq' && isset($data['options'])) {
                foreach ($data['options'] as $option) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['text'],
                        'is_correct' => $option['is_correct'],
                    ]);
                }
            }

            return $question->load('options');
        });
    }

    /**
     * Update a question
     * BUSINESS RULE: Cannot update if paper is locked
     *
     * @param Question $question
     * @param array $data
     * @return Question
     */
    public function updateQuestion(Question $question, array $data): Question
    {
        // Check if paper is locked
        if ($question->paper->isLocked()) {
            throw new \Exception('Cannot update questions in a locked question paper');
        }

        return DB::transaction(function () use ($question, $data) {
            // Update question
            $question->update([
                'question_text' => $data['question_text'],
                'question_type' => $data['question_type'],
                'marks' => $data['marks'],
            ]);

            // Update options for MCQ
            if ($data['question_type'] === 'mcq' && isset($data['options'])) {
                // Delete existing options
                $question->options()->delete();

                // Create new options
                foreach ($data['options'] as $option) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $option['text'],
                        'is_correct' => $option['is_correct'],
                    ]);
                }
            } elseif ($data['question_type'] === 'short_answer') {
                // Remove options if changing to short_answer
                $question->options()->delete();
            }

            return $question->fresh()->load('options');
        });
    }

    /**
     * Delete a question
     * BUSINESS RULE: Cannot delete if paper is locked
     *
     * @param Question $question
     * @return void
     */
    public function deleteQuestion(Question $question): void
    {
        // Check if paper is locked
        if ($question->paper->isLocked()) {
            throw new \Exception('Cannot delete questions from a locked question paper');
        }

        DB::transaction(function () use ($question) {
            $question->options()->delete();
            $question->delete();
        });
    }

    /**
     * Validate question type and options
     *
     * @param string $type
     * @param array|null $options
     * @return void
     */
    protected function validateQuestionType(string $type, ?array $options): void
    {
        if ($type === 'mcq') {
            if (empty($options) || count($options) < 2) {
                throw new \Exception('MCQ questions must have at least 2 options');
            }

            $correctCount = collect($options)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                throw new \Exception('MCQ questions must have exactly one correct answer');
            }
        }
    }
}
