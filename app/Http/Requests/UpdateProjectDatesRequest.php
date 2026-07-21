<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectDatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|exists:projects,id',
            'start_date' => [
                'required',
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
                'required',
                function ($attribute, $value, $fail) {
                    $startDate = $this->input('start_date');
                    $errors = validate_date_format_and_order($startDate, $value, endDateKey: 'end_date');
                    if (!empty($errors['end_date'])) {
                        foreach ($errors['end_date'] as $error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ];
    }
}

