<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\FcmToken;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

use Illuminate\Support\Facades\Storage;

class FormatterService
{
    public function formatLeadSource($lead_source)
    {
        return [
            'id' => $lead_source->id,
            'name' => $lead_source->name,
            'created_at' => format_date($lead_source->created_at, false, 'Y-m-d H:i:s', 'Y-m-d'),
            'updated_at' => format_date($lead_source->updated_at, false, 'Y-m-d H:i:s', 'Y-m-d'),
        ];
    }

    public function formatLeadStage($lead_stage)
    {
        return [
            'id' => $lead_stage->id,
            'name' => $lead_stage->name,
            'slug' => $lead_stage->slug,
            'order' => $lead_stage->order,
            'color' => $lead_stage->color,
            'created_at' => format_date($lead_stage->created_at, false, 'Y-m-d H:i:s', 'Y-m-d'),
            'updated_at' => format_date($lead_stage->updated_at, false, 'Y-m-d H:i:s', 'Y-m-d'),
        ];
    }

    public function formatLead($lead)
    {
        $lead->load('source', 'stage', 'assigned_user');
        return [
            'id' => $lead->id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'country_code' => $lead->country_code,
            'country_iso_code' => $lead->country_iso_code,
            'lead_source_id' => $lead->source_id,
            'lead_source' => $lead->source ? $lead->source->name : '-',
            'lead_stage_id' => $lead->stage_id,
            'lead_stage' => $lead->stage ? $lead->stage->name : '-',
            'lead_stage_color' => $lead->stage->color ? $lead->stage->color : '-',
            'assigned_to' => $lead->assigned_to,
            'assigned_user_name' => ucfirst($lead->assigned_user->first_name) . ' ' . ucfirst($lead->assigned_user->last_name),
            'job_title' => $lead->job_title,
            'industry' => $lead->industry,
            'company' => $lead->company,
            'website' => $lead->website,
            'linkedin' => $lead->linkedin,
            'instagram' => $lead->instagram,
            'facebook' => $lead->facebook,
            'pinterest' => $lead->pinterest,
            'city' => $lead->city,
            'state' => $lead->state,
            'zip' => $lead->zip,
            'country' => $lead->country,
            'isConverted' => $lead->is_converted == 1 ? true : false,
            'assigned_user' => [
                'id' => $lead->assigned_user->id,
                'name' => $lead->assigned_user->first_name . " " . $lead->assigned_user->last_name,
                'email' => $lead->assigned_user->email,
                'profile_picture' => ($lead->assigned_user && $lead->assigned_user->photo && Storage::disk('public')->exists($lead->assigned_user->photo)) ? asset('storage/' . $lead->assigned_user->photo) : asset('/photos/1.png'),
            ],
            'follow_ups' => $lead->follow_ups->map(function ($followUp) {
                return [
                    'id' => $followUp->id,
                    'follow_up_at' => $followUp->follow_up_at,
                    'type' => $followUp->type,
                    'status' => $followUp->status,
                    'note' => $followUp->note,
                    'assigned_to' => [
                        'id' => $followUp->assignedTo->id,
                        'name' => $followUp->assignedTo->first_name . " " . $followUp->assignedTo->last_name
                    ],
                    'created_at' => format_date($followUp->created_at, to_format: 'Y-m-d'),
                    'updated_at' => format_date($followUp->updated_at, to_format: 'Y-m-d'),
                ];
            })->toArray(),
            'created_at' => format_date($lead->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($lead->updated_at, to_format: 'Y-m-d'),
        ];
    }

    public function formatLeadUserHtml($lead)
    {
        if (!$lead) {
            return "-";
        }
        // Check if the lead has phone and/or country code
        $makeCallIcon = '';
        if (!empty($lead->phone) || (!empty($lead->phone) && !empty($lead->country_code))) {
            $makeCallLink = 'tel:' . ($lead->country_code ? $lead->country_code . $lead->phone : $lead->phone);
            $makeCallIcon = '<a href="' . $makeCallLink . '" class="text-decoration-none" title="' . get_label('make_call', 'Make Call') . '">
                             <i class="bx bx-phone-call text-primary"></i>
                         </a>';
        }
        // Email & Mail Link
        $sendMailLink = 'mailto:' . $lead->email;
        $sendMailIcon = '<a href="' . $sendMailLink . '" class="text-decoration-none" title="' . get_label('send_mail', 'Send Mail') . '">
                        <i class="bx bx-envelope text-primary"></i>
                     </a>';
        return "<div class='d-flex justify-content-start align-items-center user-name'>
                <div class='d-flex flex-column'>
                    <span class='fw-semibold'>" . ucwords($lead->first_name . ' ' . $lead->last_name) . " {$makeCallIcon}</span>
                    <small class='text-muted'>{$lead->email} {$sendMailIcon}</small>
                </div>
            </div>";
    }

    public function formatLeadFollowUp($followUp)
    {
        return [
            'id' => $followUp->id,
            'lead_id' => $followUp->lead_id,
            'assigned_to' => $followUp->assigned_to,
            'followUp_at' => $followUp->follow_up_data,
            'type' => $followUp->type,
            'status' => $followUp->status,
            'note' => $followUp->note,
            'created_at' => format_date($followUp->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($followUp->updated_at, to_format: 'Y-m-d'),
        ];
    }

    public function formatCustomField($field)
    {
        return [
            'id' => $field->id,
            'module' => $field->module,
            'field_label' => $field->field_label,
            'field_type' => $field->field_type,
            'options' => json_decode($field->options, true),
            'required' => $field->required,
            'show_in_table' => $field->visibility
        ];
    }

    public function formatPayslip($payslip)
    {
        return [
            'id' => $payslip->id,
            'user' => [
                'id' => $payslip->user_id,
                'name' => $payslip->user ? ($payslip->user->full_name ?? ($payslip->user->first_name . ' ' . $payslip->user->last_name)) : '-',
                'email' => $payslip->user->email,
                'profile_picture' =>  ($payslip->user && $payslip->user->photo && Storage::disk('public')->exists($payslip->user->photo)) ? asset('storage/' . $payslip->user->photo) : asset('/photos/1.png'),
            ],
            'month' => $payslip->month,
            'basic_salary' => $payslip->basic_salary,
            'working_days' => $payslip->working_days,
            'lop_days' => $payslip->lop_days,
            'paid_days' => $payslip->paid_days,
            'bonus' => $payslip->bonus,
            'incentives' => $payslip->incentives,
            'leave_deduction' => $payslip->leave_deduction,
            'ot_hours' => $payslip->ot_hours,
            'ot_rate' => $payslip->ot_rate,
            'ot_payment' => $payslip->ot_payment,
            'allowances' => $payslip->allowances->map(function ($allowance) {
                return [
                    'id' => $allowance->id,
                    'title' => $allowance->title,
                    'amount' => $allowance->amount ?? 0,
                ];
            }),
            'total_allowance' => $payslip->total_allowance,
            'deductions' => $payslip->deductions->map(function ($deduction) {
                return [
                    'id' => $deduction->id,
                    'title' => $deduction->title,
                    'amount' => $deduction->amount ?? 0,
                ];
            }),
            'total_deductions' => $payslip->total_deductions,
            'total_earnings' => $payslip->total_earnings,
            'net_pay' => $payslip->net_pay,
            'status' => $payslip->status,
            'status_label' => match ((int)$payslip->status) {
                0 => 'Pending',
                1 => 'Paid',
                default => 'Unknown',
            },
            'payment_method_id' => $payslip->payment_method_id,
            'payment_method' => $payslip->paymentMethod->title ?? '-',
            'payment_date' =>  $payslip->payment_date !== null ? format_date($payslip->payment_date, true, to_format: 'Y-m-d') : '',
            'note' => $payslip->note,
            'created_at_date' => format_date($payslip->created_at, false, to_format: 'Y-m-d'),
            'created_at_time' => format_date($payslip->created_at, false, to_format: 'H:i:s'),
            'updated_at_date' => format_date($payslip->updated_at, false, to_format: 'Y-m-d'),
            'updated_at_time' => format_date($payslip->updated_at, false, to_format: 'H:i:s'),
            'current_date' => format_date(Carbon::now(), false, to_format: 'Y-m-d'),
            'current_time' => format_date(Carbon::now(), false, to_format: 'H:i:s'),
        ];
    }

    public function formatAllowance($allowance)
    {
        return [
            'id' => $allowance->id,
            'title' => $allowance->title,
            'amount' => format_currency($allowance->amount, false),
            'created_at' => format_date($allowance->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($allowance->updated_at, to_format: 'Y-m-d'),
        ];
    }

    public function formatDeduction($deduction)
    {
        return [
            'id' => $deduction->id,
            'title' => $deduction->title,
            'type' => ucfirst($deduction->type),
            'percentage' => $deduction->percentage,
            'amount' => format_currency($deduction->amount, false),
            'created_at' => format_date($deduction->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($deduction->updated_at, to_format: 'Y-m-d')
        ];
    }

    public function formatContract($contract)
    {
        $promisorSign = $contract->promisor_sign;
        $promiseeSign = $contract->promisee_sign;
        $promisor_sign_status = !is_null($promisorSign) ? 'signed' : 'not_signed';
        $promisee_sign_status = !is_null($promiseeSign) ? 'signed' : 'not_signed';
        if (!is_null($promisorSign) && !is_null($promiseeSign)) {
            $status = 'signed';
        } elseif (!is_null($promisorSign) || !is_null($promiseeSign)) {
            $status = 'partially_signed';
        } else {
            $status = 'not_signed';
        }
        if (strpos($contract->created_by, 'u_') === 0) {
            $userId = substr($contract->created_by, 2);
            $user = \App\Models\User::find($userId);
            $createdBy = $user ? [
                'type' => 'user',
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'profile_picture' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('/photos/1.png'),
            ] : null;
        } else {
            $clientId = substr($contract->created_by, 2);
            $client = \App\Models\Client::find($clientId);
            $createdBy = $client ? [
                'type' => 'client',
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'profile_picture' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('/photos/1.png'),
            ] : null;
        }
        return [
            'id' => $contract->id,
            'title' => $contract->title,
            'value' => format_currency($contract->value, 0),
            'start_date' => format_date($contract->start_date, to_format: 'Y-m-d'),
            'end_date' => format_date($contract->end_date, to_format: 'Y-m-d'),
            'client' => [
                'id' => $contract->client->id,
                'name' => $contract->client->first_name . " " . $contract->client->last_name,
                'email' => $contract->client->email,
                'profile_picture' => ($contract->client->photo && Storage::disk('public')->exists($contract->client->photo)) ? asset('storage/' . $contract->client->photo) : asset('storage/photos/no-image.jpg')
            ],
            'created_by' => $createdBy,
            'project' => [
                'id' => $contract->project_id,
                'title' => $contract->project_title
            ],
            'contract_type' => [
                'id' => $contract->contract_type_id,
                'name' => $contract->contract_type
            ],
            'description' => $contract->description,
            'workspace_id' => $contract->workspace_id,
            'created_at' => format_date($contract->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($contract->updated_at, to_format: 'Y-m-d'),
            'status' => $status,
            'signatures' => [
                'promisor' => [
                    'status' => $promisor_sign_status,
                    'url' => $promisorSign && Storage::disk('public')->exists('signatures/' . $promisorSign) ? asset('storage/signatures/' . $promisorSign) : null,
                ],
                'promisee' => [
                    'status' => $promisee_sign_status,
                    'url' => $promiseeSign && Storage::disk('public')->exists('signatures/' . $promiseeSign) ? asset('storage/signatures/' . $promiseeSign) : null,
                ],
            ],
            'signed_pdf_url' => $contract->signed_pdf ? asset('storage/contracts/' . $contract->signed_pdf) : null,
        ];
    }

    public function formatContractType($contract_type)
    {
        return [
            'id' => $contract_type->id,
            'type' => $contract_type->type,
            'created_at' => format_date($contract_type->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($contract_type->updated_at, to_format: 'Y-m-d'),
        ];
    }

    public function formatComment($comment)
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'commenter' => $comment->commenter ? [
                'id' => $comment->commenter->id,
                'first_name' => $comment->commenter->first_name,
                'last_name' => $comment->commenter->last_name,
                'email' => $comment->commenter->email,
                'photo' => ($comment->commenter->photo && Storage::disk('public')->exists($comment->commenter->photo)) ? asset('storage/' . $comment->commenter->photo) : asset('storage/photos/no-image.jpg'),
            ] : null,
            'created_at' => format_date($comment->created_at, to_format: 'Y-m-d H:i:s'),
            'sent_time' => $comment->created_at->diffForHumans(),
            'attachments' => $comment->attachments->map(function ($a) {
                return [
                    'id' => $a->id,
                    'file_name' => $a->file_name,
                    'file_path' => $a->file_path,
                    'file_type' => $a->file_type,
                    'url' => asset('storage/' . $a->file_path),
                ];
            }),
            'children' => $comment->children->map(function ($child) {
                return $this->formatComment($child);
            })->values(),
        ];
    }

    public function formatLeadForm($leadForm)
    {
        return [
            'id' => $leadForm->id,
            'title' => $leadForm->title,
            'description' => $leadForm->description,
            'source' => $leadForm->leadSource ? [
                'id' => $leadForm->leadSource->id,
                'name' => $leadForm->leadSource->name,
            ] : null,
            'stage' => $leadForm->leadStage ? [
                'id' => $leadForm->leadStage->id,
                'name' => $leadForm->leadStage->name,
                'color' => $leadForm->leadStage->color,
            ] : null,
            'assigned_to' => $leadForm->assignedUser ? [
                'id' => $leadForm->assignedUser->id,
                'first_name' => $leadForm->assignedUser->first_name,
                'last_name' => $leadForm->assignedUser->last_name,
                'email' => $leadForm->assignedUser->email,
                'photo' => ($leadForm->assignedUser->photo && Storage::disk('public')->exists($leadForm->assignedUser->photo)) ? asset('storage/' . $leadForm->assignedUser->photo) : asset('storage/photos/no-image.jpg'),
            ] : null,
            'fields' => $leadForm->leadFormFields->map(function ($field) {
                return [
                    'id' => $field->id,
                    'label' => $field->label,
                    'name' => $field->name,
                    'type' => $field->type,
                    'is_required' => (bool) $field->is_required,
                    'is_mapped' => (bool) $field->is_mapped,
                    'options' => is_array($decoded = json_decode($field->options ?? '[]', true)) && !(count($decoded) === 1 && is_null($decoded[0])) ? $decoded : [],
                    'placeholder' => $field->placeholder,
                    'order' => $field->order,
                    'validation_rules' => $field->validation_rules,
                ];
            })->values(),
            'public_url' => $leadForm->public_url,
            'embed_code' => $leadForm->embed_code,
            'leads_count' => $leadForm->leads_count ?? 0,
            'created_at' => format_date($leadForm->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($leadForm->updated_at, to_format: 'Y-m-d'),
        ];
    }

    public function formatLeadFormResponse($lead)
    {
        return [
            'id' => $lead->id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'full_name' => trim($lead->first_name . ' ' . $lead->last_name),
            'email' => $lead->email,
            'phone' => $lead->phone,
            'company' => $lead->company ?? null,
            'source' => $lead->leadSource ? [
                'id' => $lead->leadSource->id,
                'name' => $lead->leadSource->name,
            ] : null,
            'stage' => $lead->leadStage ? [
                'id' => $lead->leadStage->id,
                'name' => $lead->leadStage->name,
                'color' => $lead->leadStage->color,
            ] : null,
            'assigned_to' => $lead->assignedUser ? [
                'id' => $lead->assignedUser->id,
                'first_name' => $lead->assignedUser->first_name,
                'last_name' => $lead->assignedUser->last_name,
                'email' => $lead->assignedUser->email,
                'photo' => ($lead->assignedUser->photo && Storage::disk('public')->exists($lead->assignedUser->photo)) ? asset('storage/' . $lead->assignedUser->photo) : asset('storage/photos/no-image.jpg'),
            ] : null,
            'submitted_at' => format_date($lead->created_at, to_format: "Y-m-d"),
            'sent_time' => $lead->created_at->diffForHumans(),
            'custom_fields' => $lead->custom_fields ?? [],
        ];
    }

    public function formatProject($project)
    {
        $customData = $this->getCustomFieldsData($project, 'project');
        $auth_user = getAuthenticatedUser();
        return [
            'id' => $project->id,
            'title' => $project->title,
            'task_count' => isAdminOrHasAllDataAccess() ? count($project->tasks) : $auth_user->project_tasks($project->id)->count(),
            'status' => $project->status->title,
            'status_id' => $project->status->id,
            'priority' => $project->priority ? $project->priority->title : null,
            'priority_id' => $project->priority ? $project->priority->id : null,
            'users' => $project->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'photo' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'user_id' => $project->users->pluck('id')->toArray(),
            'clients' => $project->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'photo' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'client_id' => $project->clients->pluck('id')->toArray(),
            'tags' => $project->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'title' => $tag->title
                ];
            }),
            'tag_ids' => $project->tags->pluck('id')->toArray(),
            'start_date' => $project->start_date ? format_date($project->start_date, to_format: 'Y-m-d') : null,
            'end_date' => $project->end_date ? format_date($project->end_date, to_format: 'Y-m-d') : null,
            'budget' => $project->budget ?? null,
            'task_accessibility' => $project->task_accessibility,
            'description' => $project->description,
            'note' => $project->note,
            'favorite' => getFavoriteStatus($project->id),
            'pinned' => isset($project->pinned_id) && !is_null($project->pinned_id) ? 1 : (isset($project->pinned_id) ? 0 : getPinnedStatus($project->id)),
            'client_can_discuss' => $project->client_can_discuss,
            'created_at' => format_date($project->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($project->updated_at, to_format: 'Y-m-d'),
            'customFields' => $customData['fields'],
            'customFieldValues' => $customData['values']
        ];
    }

    public function formatTask($task)
    {
        $task->load('reminders', 'recurringTask');
        $reminder = $task->reminders[0] ?? null;
        $recurringTask = $task->recurringTask ?? null;
        $customData = $this->getCustomFieldsData($task, 'task');

        return [
            'id' => $task->id,
            'workspace_id' => $task->workspace_id,
            'title' => $task->title,
            'status' => $task->status->title,
            'status_id' => $task->status->id,
            'priority' => $task->priority ? $task->priority->title : null,
            'priority_id' => $task->priority ? $task->priority->id : null,
            'users' => $task->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'photo' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'user_id' => $task->users->pluck('id')->toArray(),
            'clients' => $task->project->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'photo' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'start_date' => $task->start_date ? format_date($task->start_date, to_format: 'Y-m-d') : null,
            'due_date' => $task->due_date ? format_date($task->due_date, to_format: 'Y-m-d') : null,
            'project' => $task->project->title,
            'project_id' => $task->project->id,
            'description' => $task->description,
            'note' => $task->note,
            'favorite' => getFavoriteStatus($task->id, \App\Models\Task::class),
            'pinned' => getPinnedStatus($task->id, \App\Models\Task::class),
            'client_can_discuss' => $task->client_can_discuss,
            'created_at' => format_date($task->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($task->updated_at, to_format: 'Y-m-d'),
            'enable_reminder' => $reminder ? 1 : 0,
            'last_reminder_sent' => $reminder && $reminder->last_sent_at ? \Carbon\Carbon::parse($reminder->last_sent_at)->diffForHumans() : null,
            'frequency_type' => $reminder ? $reminder->frequency_type : null,
            'day_of_week' => $reminder && $reminder->day_of_week ? (int)$reminder->day_of_week : null,
            'day_of_month' => $reminder && $reminder->day_of_month ? (int)$reminder->day_of_month : null,
            'time_of_day' => $reminder ? $reminder->time_of_day : null,
            'enable_recurring_task' => $recurringTask ? 1 : 0,
            'recurrence_frequency' => $recurringTask ? $recurringTask->frequency : null,
            'recurrence_day_of_week' => $recurringTask && $recurringTask->day_of_week ? (int)$recurringTask->day_of_week : null,
            'recurrence_day_of_month' => $recurringTask && $recurringTask->day_of_month ? (int)$recurringTask->day_of_month : null,
            'recurrence_month_of_year' => $recurringTask && $recurringTask->month_of_year ? (int)$recurringTask->month_of_year : null,
            'recurrence_starts_from' => $recurringTask ? format_date($recurringTask->starts_from, to_format: 'Y-m-d') : null,
            'recurrence_occurrences' => $recurringTask && $recurringTask->number_of_occurrences ? (int)$recurringTask->number_of_occurrences : null,
            'completed_occurrences' => $recurringTask && $recurringTask->completed_occurrences ? (int)$recurringTask->completed_occurrences : null,
            'billing_type' => $task->billing_type,
            'completion_percentage' => $task->completion_percentage,
            'task_list_id' => $task->task_list_id,
            'customFields' => $customData['fields'],
            'customFieldValues' => $customData['values'],
        ];
    }

    public function formatMeeting($meeting)
    {
        $currentDateTime = Carbon::now(config('app.timezone'));
        $status = (($currentDateTime < \Carbon\Carbon::parse($meeting->start_date_time, config('app.timezone'))) ? 'Will start in ' . $currentDateTime->diff(\Carbon\Carbon::parse($meeting->start_date_time, config('app.timezone')))->format('%a days %H hours %I minutes %S seconds') : (($currentDateTime > \Carbon\Carbon::parse($meeting->end_date_time, config('app.timezone')) ? 'Ended before ' . \Carbon\Carbon::parse($meeting->end_date_time, config('app.timezone'))->diff($currentDateTime)->format('%a days %H hours %I minutes %S seconds') : 'Ongoing')));
        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'start_date' => \Carbon\Carbon::parse($meeting->start_date_time)->format('Y-m-d'),
            'start_time' => \Carbon\Carbon::parse($meeting->start_date_time)->format('H:i'),
            'end_date' => \Carbon\Carbon::parse($meeting->end_date_time)->format('Y-m-d'),
            'end_time' => \Carbon\Carbon::parse($meeting->end_date_time)->format('H:i'),
            'users' => $meeting->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'photo' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'user_ids' => $meeting->users->pluck('id')->toArray(),
            'clients' => $meeting->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'photo' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'client_ids' => $meeting->clients->pluck('id')->toArray(),
            'status' => $status,
            'ongoing' => $status == 'Ongoing' ? 1 : 0,
            'join_url' => url('meetings/join/web-view/' . $meeting->id),
            'created_at' => format_date($meeting->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($meeting->updated_at, to_format: 'Y-m-d')
        ];
    }

    public function formatNotification($notification)
    {
        $readAt = isset($notification->notification_user_read_at)
            ? format_date($notification->notification_user_read_at, true)
            : (isset($notification->client_notifications_read_at)
                ? format_date($notification->client_notifications_read_at, true)
                : (isset($notification->pivot) && isset($notification->pivot->read_at)
                    ? format_date($notification->pivot->read_at, true)
                    : null));
        $labelRead = get_label('read', 'Read');
        $labelUnread = get_label('unread', 'Unread');
        $status = is_null($readAt) ? $labelUnread : $labelRead;
        // Handle is_system logic, including pivot
        $isSystem = $notification->notification_user_is_system
            ?? $notification->client_notifications_is_system
            ?? ($notification->pivot->is_system ?? null);
        // Handle is_push logic, including pivot
        $isPush = $notification->notification_user_is_push
            ?? $notification->client_notifications_is_push
            ?? ($notification->pivot->is_push ?? null);
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'users' => $notification->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'photo' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'clients' => $notification->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'photo' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'type' => ucfirst(str_replace('_', ' ', $notification->type)),
            'type_id' => $notification->type_id,
            'message' => $notification->message,
            'status' => $status,
            'is_system' => $isSystem,
            'is_push' => $isPush,
            'read_at' => $readAt,
            'created_at' => format_date($notification->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($notification->updated_at, to_format: 'Y-m-d')
        ];
    }

    public function formatUser($user, $isSignup = false)
    {
        $fcmToken = FcmToken::where('user_id', $user->id)->latest()->value('fcm_token');
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->getRoleNames()->count() > 0 ? $user->getRoleNames()->first() : null,
            'role_id' => $user->roles()->count() > 0 ? $user->roles()->first()->id : null,
            'email' => $user->email,
            'phone' => $user->phone,
            'country_code' => $user->country_code,
            'country_iso_code' => $user->country_iso_code,
            'password' => $user->password,
            'password_confirmation' => $user->password,
            'type' => 'member',
            'dob' => $user->dob ? format_date($user->dob, to_format: 'Y-m-d') : null,
            'doj' => $user->doj ? format_date($user->doj, to_format: 'Y-m-d') : null,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state,
            'country' => $user->country,
            'zip' => $user->zip,
            'profile' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg'),
            'status' => $user->status,
            'fcm_token' => $fcmToken,
            'created_at' => format_date($user->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($user->updated_at, to_format: 'Y-m-d'),
            'assigned' => $isSignup ? [
                'projects' => 0,
                'tasks' => 0
            ] : (
                isAdminOrHasAllDataAccess('user', $user->id) ? [
                    'projects' => Workspace::find(getWorkspaceId())->projects()->count(),
                    'tasks' => Workspace::find(getWorkspaceId())->tasks()->count(),
                ] : [
                    'projects' => $user->projects()->count(),
                    'tasks' => $user->tasks()->count()
                ]
            )
        ];
    }

    public function formatClient($client, $isSignup = false)
    {
        return [
            'id' => $client->id,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'role' => $client->getRoleNames()->first(),
            'company' => $client->company,
            'email' => $client->email,
            'phone' => $client->phone,
            'country_code' => $client->country_code,
            'country_iso_code' => $client->country_iso_code,
            'password' => $client->password,
            'password_confirmation' => $client->password,
            'type' => 'client',
            'dob' => $client->dob ? format_date($client->dob, to_format: 'Y-m-d') : null,
            'doj' => $client->doj ? format_date($client->doj, to_format: 'Y-m-d') : null,
            'address' => $client->address ? $client->address : null,
            'city' => $client->city,
            'state' => $client->state,
            'country' => $client->country,
            'zip' => $client->zip,
            'profile' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg'),
            'status' => $client->status,
            'fcm_token' => $client->fcm_token,
            'internal_purpose' => $client->internal_purpose,
            'email_verification_mail_sent' => $client->email_verification_mail_sent,
            'email_verified_at' => $client->email_verified_at,
            'created_at' => format_date($client->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($client->updated_at, to_format: 'Y-m-d'),
            'assigned' => $isSignup ? [
                'projects' => 0,
                'tasks' => 0
            ] : (
                isAdminOrHasAllDataAccess('client', $client->id) ? [
                    'projects' => Workspace::find(getWorkspaceId())->projects()->count(),
                    'tasks' => Workspace::find(getWorkspaceId())->tasks()->count(),
                ] : [
                    'projects' => $client->projects()->count(),
                    'tasks' => $client->tasks()->count()
                ]
            )
        ];
    }

    public function formatWorkspace($workspace)
    {
        $authUser = getAuthenticatedUser();
        return [
            'id' => $workspace->id,
            'title' => $workspace->title,
            'primaryWorkspace' => $workspace->is_primary,
            'defaultWorkspace' => $authUser->default_workspace_id == $workspace->id ? 1 : 0,
            'users' => $workspace->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'photo' => ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'user_ids' => $workspace->users->pluck('id')->toArray(),
            'clients' => $workspace->clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,
                    'photo' => ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')
                ];
            }),
            'client_ids' => $workspace->clients->pluck('id')->toArray(),
            'created_at' => format_date($workspace->created_at, to_format: 'Y-m-d'),
            'updated_at' => format_date($workspace->updated_at, to_format: 'Y-m-d')
        ];
    }

    public function getStatusCounts($statuses, $auth_user, $type = 'projects')
    {
        $statusCounts = [];
        $totalCount = 0;
        foreach ($statuses as $status) {
            $count = isAdminOrHasAllDataAccess()
                ? count($status->$type)
                : $auth_user->{"status_{$type}"}($status->id)->count();
            $statusCounts[$status->id] = $count;
            $totalCount += $count;
        }
        arsort($statusCounts);
        return [$statusCounts, $totalCount];
    }

    public function numberToWords($number)
    {
        if ($number == 0) {
            return 'Zero';
        }

        $ones = array(
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        );

        $tens = array(
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        );

        $original_number = $number;
        $number = (string)$number;
        if (strpos($number, '.') !== false) {
            list($number, $decimal) = explode('.', $number);
        }

        $number = (int)$number;

        if ($number < 20) {
            return $ones[$number];
        } elseif ($number < 100) {
            $tens_digit = floor($number / 10);
            $ones_digit = $number % 10;
            return $tens[$tens_digit] . ($ones_digit > 0 ? ' ' . $ones[$ones_digit] : '');
        } elseif ($number < 1000) {
            $hundreds_digit = floor($number / 100);
            $remainder = $number % 100;
            return $ones[$hundreds_digit] . ' Hundred' . ($remainder > 0 ? ' ' . $this->numberToWords($remainder) : '');
        } elseif ($number < 100000) {
            $thousands_digit = floor($number / 1000);
            $remainder = $number % 1000;
            return $this->numberToWords($thousands_digit) . ' Thousand' . ($remainder > 0 ? ' ' . $this->numberToWords($remainder) : '');
        } elseif ($number < 10000000) {
            $lakhs_digit = floor($number / 100000);
            $remainder = $number % 100000;
            return $this->numberToWords($lakhs_digit) . ' Lakh' . ($lakhs_digit > 1 ? 's' : '') . ($remainder > 0 ? ' ' . $this->numberToWords($remainder) : '');
        } else {
            $crores_digit = floor($number / 10000000);
            $remainder = $number % 10000000;
            return $this->numberToWords($crores_digit) . ' Crore' . ($crores_digit > 1 ? 's' : '') . ($remainder > 0 ? ' ' . $this->numberToWords($remainder) : '');
        }
    }

    public function imageToBase64($path)
    {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    protected function getCustomFieldsData($model, $module)
    {
        $customFields = CustomField::where('module', $module)->get();
        $customFields->transform(function ($field) {
            if (is_string($field->options)) {
                $field->options = json_decode($field->options);
            }
            return $field;
        });

        $customFieldValues = [];
        foreach ($model->customFieldValues as $fieldValue) {
            $value = $fieldValue->value;
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                array_walk_recursive($decoded, function (&$item) {
                    if (is_null($item)) {
                        $item = '';
                    }
                });
                $customFieldValues[$fieldValue->custom_field_id] = $decoded;
            } else {
                $customFieldValues[$fieldValue->custom_field_id] = [is_null($value) ? '' : $value];
            }
        }

        return [
            'fields' => $customFields,
            'values' => $customFieldValues
        ];
    }
    public function formatUserHtml($user)
    {
        if (!$user) {
            return "-";
        }
        // Get the authenticated user
        $authenticatedUser = getAuthenticatedUser();
        // Get the guard name (web or client)
        $guardName = getGuardName();
        // Check if the authenticated user is the same as the user being displayed
        if (
            ($guardName === 'web' && $authenticatedUser->id === $user->id) ||
            ($guardName === 'client' && $authenticatedUser->id === $user->id)
        ) {
            // Don't show the "Make Call" option if it's the logged-in user
            $makeCallIcon = '';
        } else {
            // Check if the phone number or both phone and country code exist
            $makeCallIcon = '';
            if (!empty($user->phone) || (!empty($user->phone) && !empty($user->country_code))) {
                $makeCallLink = 'tel:' . ($user->country_code ? $user->country_code . $user->phone : $user->phone);
                $makeCallIcon = '<a href="' . $makeCallLink . '" class="text-decoration-none" title="' . get_label('make_call', 'Make Call') . '">
                                     <i class="bx bx-phone-call text-primary"></i>
                                   </a>';
            }
        }
        // If the user has 'manage_users' permission, return the full HTML with links
        $profileLink = route('users.profile', ['id' => $user->id]);
        $photoUrl = ($user->photo && Storage::disk('public')->exists($user->photo)) ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg');
        // Create the Send Mail link
        $sendMailLink = 'mailto:' . $user->email;
        $sendMailIcon = '<a href="' . $sendMailLink . '" class="text-decoration-none" title="' . get_label('send_mail', 'Send Mail') . '">
                            <i class="bx bx-envelope text-primary"></i>
                          </a>';
        return "<div class='d-flex justify-content-start align-items-center user-name'>
                    <div class='avatar-wrapper me-3'>
                        <div class='avatar avatar-sm pull-up'>
                            <a href='{$profileLink}' target='_blank'>
                                <img src='{$photoUrl}' alt='Photo' class='rounded-circle'>
                            </a>
                        </div>
                    </div>
                    <div class='d-flex flex-column'>
                        <span class='fw-semibold'>{$user->first_name} {$user->last_name} {$makeCallIcon}</span>
                        <small class='text-muted'>{$user->email} {$sendMailIcon}</small>
                    </div>
                </div>";
    }

    public function formatClientHtml($client)
    {
        if (!$client) {
            return "-";
        }
        // Get the authenticated user
        $authenticatedUser = getAuthenticatedUser();
        // Get the guard name (web or client)
        $guardName = getGuardName();
        // Check if the authenticated user is the same as the client being displayed
        if (
            ($guardName === 'web' && $authenticatedUser->id === $client->id) ||
            ($guardName === 'client' && $authenticatedUser->id === $client->id)
        ) {
            // Don't show the "Make Call" option if it's the logged-in client
            $makeCallIcon = '';
        } else {
            // Check if the phone number or both phone and country code exist
            $makeCallIcon = '';
            if (!empty($client->phone) || (!empty($client->phone) && !empty($client->country_code))) {
                $makeCallLink = 'tel:' . ($client->country_code ? $client->country_code . $client->phone : $client->phone);
                $makeCallIcon = '<a href="' . $makeCallLink . '" class="text-decoration-none" title="' . get_label('make_call', 'Make Call') . '">
                                     <i class="bx bx-phone-call text-primary"></i>
                                   </a>';
            }
        }
        // If the user has 'manage_clients' permission, return the full HTML with links
        $profileLink = route('clients.profile', ['id' => $client->id]);
        $photoUrl = ($client->photo && Storage::disk('public')->exists($client->photo)) ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg');
        // Create the Send Mail link
        $sendMailLink = 'mailto:' . $client->email;
        $sendMailIcon = '<a href="' . $sendMailLink . '" class="text-decoration-none" title="' . get_label('send_mail', 'Send Mail') . '">
                            <i class="bx bx-envelope text-primary"></i>
                          </a>';
        return "<div class='d-flex justify-content-start align-items-center user-name'>
                    <div class='avatar-wrapper me-3'>
                        <div class='avatar avatar-sm pull-up'>
                            <a href='{$profileLink}' target='_blank'>
                                <img src='{$photoUrl}' alt='Photo' class='rounded-circle'>
                            </a>
                        </div>
                    </div>
                    <div class='d-flex flex-column'>
                        <span class='fw-semibold'>{$client->first_name} {$client->last_name} {$makeCallIcon}</span>
                        <small class='text-muted'>{$client->email} {$sendMailIcon}</small>
                    </div>
                </div>";
    }
}
