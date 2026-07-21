<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string'],
            'start_date' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $endDate = $this->input('end_date');
                    $errors = validate_date_format_and_order($value, $endDate);
                    if (!empty($errors['start_date'])) {
                        foreach ($errors['start_date'] as $error) {
                            $fail($error);
                        }
                    }
                },
            ],
            'end_date' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $startDate = $this->input('start_date');
                    $errors = validate_date_format_and_order($startDate, $value);
                    if (!empty($errors['end_date'])) {
                        foreach ($errors['end_date'] as $error) {
                            $fail($error);
                        }
                    }
                },
            ],
            'cost' => [
                'required',
                function ($attribute, $value, $fail) {
                    $error = validate_currency_format($value, 'cost');
                    if ($error) {
                        $fail($error);
                    }
                }
            ],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }
}

