<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:projects,id',
            'statusId' => 'required|exists:statuses,id',
            'note' => 'nullable|string',
        ];
    }
}

