<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only teachers and admins can generate questions
        return $this->user() && in_array($this->user()->role, ['admin', 'teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $minQuestions = config('ai.question_generation.min_questions', 1);
        $maxQuestions = config('ai.question_generation.max_questions', 50);

        return [
            'paper_id' => 'nullable|exists:question_papers,id',
            'topic' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'type' => 'required|in:mcq,short_answer',
            'difficulty' => 'required|in:easy,medium,hard',
            'count' => "required|integer|min:{$minQuestions}|max:{$maxQuestions}",
            'marks' => 'required|integer|min:1|max:100',
            'context' => 'nullable|string|max:1000',
            'save_to_paper' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'topic.required' => 'Please provide a topic for question generation',
            'type.required' => 'Please specify the question type (MCQ or short answer)',
            'type.in' => 'Question type must be either "mcq" or "short_answer"',
            'difficulty.required' => 'Please specify the difficulty level',
            'difficulty.in' => 'Difficulty must be "easy", "medium", or "hard"',
            'count.required' => 'Please specify how many questions to generate',
            'count.min' => 'Minimum :min question(s) required',
            'count.max' => 'Maximum :max questions allowed',
            'marks.required' => 'Please specify marks per question',
            'paper_id.exists' => 'The selected question paper does not exist',
        ];
    }
}
