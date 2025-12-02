<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // only authenticated users
    }

    public function rules(): array
    {
        return [
            'rating'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'context' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Please enter your feedback.',
            'message.min' => 'Feedback must be at least 5 characters.',
            'rating.min' => 'Rating must be between 1 and 5.',
            'rating.max' => 'Rating must be between 1 and 5.',
        ];
    }
}
