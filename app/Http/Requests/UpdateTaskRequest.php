<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'title' => 'required',
            'status_id' => 'required|exists:statuses,id',
            'priority_id' => 'nullable|exists:priorities,id',
            'start_date' => [
                'nullable',
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
                'nullable',
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
            'description' => 'nullable|string',
            'note' => 'nullable|string',
            'user_id' => 'nullable|array',
            'user_id.*' => 'exists:users,id',
            // Reminder Tasks
            'enable_reminder' => 'nullable|in:on',
            'frequency_type' => 'nullable|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|between:1,7',
            'day_of_month' => 'nullable|integer|between:1,31',
            'time_of_day' => 'nullable|date_format:H:i',
            // Recurring task validation rules
            'enable_recurring_task' => 'nullable|in:on,off',
            'recurrence_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'recurrence_day_of_week' => 'nullable|integer|min:1|max:7',
            'recurrence_day_of_month' => 'nullable|integer|min:1|max:31',
            'recurrence_month_of_year' => 'nullable|integer|min:1|max:12',
            'recurrence_starts_from' => 'nullable|date|after_or_equal:today',
            'recurrence_occurrences' => 'nullable|integer|min:1',
            'billing_type' => 'nullable|in:none,billable,non-billable',
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100', 'in:0,10,20,30,40,50,60,70,80,90,100'],
            'task_list_id' => 'nullable|exists:task_lists,id',
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











