<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxFileSizeBytes = config('media-library.max_file_size');
        $maxFileSizeKb = (int) ($maxFileSizeBytes / 1024);

        return [
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'content' => 'required|string',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'attachments.*' => "file|max:$maxFileSizeKb"
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please enter a comment'
        ];
    }
}

