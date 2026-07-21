<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskDatesRequest extends FormRequest
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
        $isApi = $this->get('isApi', false);

        return [
            'id' => 'required|exists:tasks,id',
            'start_date' => [
                'required',
                function ($attribute, $value, $fail) use ($isApi) {
                    $endDate = $this->input('due_date');
                    $errors = validate_date_format_and_order($value, $endDate, $isApi ? 'Y-m-d' : null);
                    if (!empty($errors['start_date'])) {
                        foreach ($errors['start_date'] as $error) {
                            $fail($error);
                        }
                    }
                },
            ],
            'due_date' => [
                'required',
                function ($attribute, $value, $fail) use ($isApi) {
                    $startDate = $this->input('start_date');
                    $errors = validate_date_format_and_order($startDate, $value, $isApi ? 'Y-m-d' : null, endDateKey: 'due_date');
                    if (!empty($errors['due_date'])) {
                        foreach ($errors['due_date'] as $error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ];
    }
}











