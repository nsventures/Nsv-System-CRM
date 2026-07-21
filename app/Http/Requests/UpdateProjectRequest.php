<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
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
            'id' => 'required|exists:projects,id',
            'title' => 'required',
            'status_id' => 'required',
            'priority_id' => 'nullable|exists:priorities,id',
            'budget' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $error = validate_currency_format($value, 'budget');
                    if ($error) {
                        $fail($error);
                    }
                }
            ],
            'task_accessibility' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value !== 'project_users' && $value !== 'assigned_users') {
                        $fail('The task accessibility must be either project_users or assigned_users.');
                    }
                }
            ],
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
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'user_id' => 'nullable|array',
            'user_id.*' => 'exists:users,id',
            'client_id' => 'nullable|array',
            'client_id.*' => 'exists:clients,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'enable_tasks_time_entries' => 'boolean',
            'custom_fields' => 'nullable|array',
            'clientCanDiscuss' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status_id.required' => 'The status field is required.',
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
            'end_date.after_or_equal' => 'The end date must be today or a future date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert priority_id = 0 to null
        if ($this->has('priority_id') && $this->input('priority_id') == 0) {
            $this->merge(['priority_id' => null]);
        }
    }
}

