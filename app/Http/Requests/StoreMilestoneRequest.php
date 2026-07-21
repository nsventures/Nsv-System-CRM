<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
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
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'start_date' => [
                'nullable',
                function ($attribute, $value, $fail) use ($isApi) {
                    $endDate = $this->input('end_date');
                    $errors = validate_date_format_and_order($value, $endDate, $isApi ? 'Y-m-d' : null);
                    if (!empty($errors['start_date'])) {
                        foreach ($errors['start_date'] as $error) {
                            $fail($error);
                        }
                    }
                },
            ],
            'end_date' => [
                'nullable',
                function ($attribute, $value, $fail) use ($isApi) {
                    $startDate = $this->input('start_date');
                    $errors = validate_date_format_and_order($startDate, $value, $isApi ? 'Y-m-d' : null);
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
            'description' => 'nullable|string',
        ];
    }
}

