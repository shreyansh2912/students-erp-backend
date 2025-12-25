<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paper_id' => ['required', 'exists:question_papers,id'],
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:mcq,short_answer'],
            'marks' => ['required', 'integer', 'min:1'],
            'options' => ['required_if:question_type,mcq', 'array', 'min:2'],
            'options.*.text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paper_id.required' => 'Question paper is required',
            'paper_id.exists' => 'Selected question paper does not exist',
            'question_text.required' => 'Question text is required',
            'question_type.required' => 'Question type is required',
            'question_type.in' => 'Question type must be either mcq or short_answer',
            'marks.required' => 'Marks is required',
            'marks.min' => 'Marks must be at least 1',
            'options.required_if' => 'MCQ questions must have options',
            'options.min' => 'MCQ questions must have at least 2 options',
            'options.*.text.required_with' => 'Option text is required',
            'options.*.is_correct.required_with' => 'Option correct flag is required',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // For MCQ, ensure exactly one correct answer
            if ($this->question_type === 'mcq' && $this->options) {
                $correctCount = collect($this->options)->where('is_correct', true)->count();
                
                if ($correctCount === 0) {
                    $validator->errors()->add('options', 'MCQ must have at least one correct answer');
                } elseif ($correctCount > 1) {
                    $validator->errors()->add('options', 'MCQ can only have one correct answer');
                }
            }
        });
    }
}
