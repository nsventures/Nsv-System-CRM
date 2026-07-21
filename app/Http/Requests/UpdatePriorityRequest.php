<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:projects,id',
            'priorityId' => 'nullable|exists:priorities,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('priorityId') == 0) {
            $this->merge(['priorityId' => null]);
        }
    }
}

