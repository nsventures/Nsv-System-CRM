<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * We keep the same behaviour as before by not adding any new
     * authorization checks here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Intentionally keep date validation minimal to avoid changing
     * existing behaviour around defaulting/format handling.
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable'],
            'end_date' => ['nullable'],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer'],
        ];
    }
}


