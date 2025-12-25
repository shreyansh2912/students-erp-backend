<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestionPaperRequest extends FormRequest
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
            'organization_id' => ['required', 'exists:organizations,id'],
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:100'],
            'total_marks' => ['required', 'integer', 'min:1'],
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
            'organization_id.required' => 'Organization is required',
            'organization_id.exists' => 'Selected organization does not exist',
            'title.required' => 'Question paper title is required',
            'title.max' => 'Title cannot exceed 255 characters',
            'subject.required' => 'Subject is required',
            'subject.max' => 'Subject cannot exceed 100 characters',
            'total_marks.required' => 'Total marks is required',
            'total_marks.min' => 'Total marks must be at least 1',
        ];
    }
}
