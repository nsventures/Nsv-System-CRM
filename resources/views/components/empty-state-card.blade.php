@php
    $flag =
        (Request::segment(1) == 'home' || Request::segment(1) == 'users' || Request::segment(1) == 'clients') &&
        (strtolower($type) == 'projects' || strtolower($type) == 'tasks')
            ? 0
            : 1;
    $currentPath = request()->path();
    $showCreateButton = !in_array($currentPath, ['projects/list/favorite', 'projects/favorite']);
@endphp
<div class="<?= $flag == 1 ? 'card ' : '' ?>text-center empty-state">
    @if ($flag == 1)
        <div class="card-body">
    @endif
    <div class="empty">
        <div class="empty-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h4l2 3h6l2-3h4M3 13l3-7h12l3 7M3 13v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6"/></svg>
        </div>
        <div class="empty-title"><?= get_label(strtolower($type), $type) . ' ' . get_label('not_found', 'Not Found') ?></div>
        <div class="empty-sub"><?= get_label('data_does_not_exists', 'Data does not exists') ?>.</div>
        <div class="empty-actions">
        @if ($type != 'Notifications' && $showCreateButton)
            @php
                $typeSlug = strtolower(str_replace(' ', '-', $type));
                $modalMap = [
                    'todos' => ['target' => '#create_todo_modal', 'toggle' => 'modal'],
                    'tags' => ['target' => '#create_tag_modal', 'toggle' => 'modal'],
                    'status' => ['target' => '#create_status_modal', 'toggle' => 'modal'],
                    'leave-requests' => ['target' => '#create_leave_request_modal', 'toggle' => 'modal'],
                    'contract-types' => ['target' => '#create_contract_type_modal', 'toggle' => 'modal'],
                    'contracts' => ['target' => '#create_contract_modal', 'toggle' => 'modal'],
                    'payment-methods' => ['target' => '#create_pm_modal', 'toggle' => 'modal'],
                    'allowances' => ['target' => '#create_allowance_modal', 'toggle' => 'modal'],
                    'deductions' => ['target' => '#create_deduction_modal', 'toggle' => 'modal'],
                    'notes' => ['target' => '#create_note_modal', 'toggle' => 'modal'],
                    'timesheet' => ['target' => '#timerModal', 'toggle' => 'modal'],
                    'taxes' => ['target' => '#create_tax_modal', 'toggle' => 'modal'],
                    'units' => ['target' => '#create_unit_modal', 'toggle' => 'modal'],
                    'items' => ['target' => '#create_item_modal', 'toggle' => 'modal'],
                    'expense-types' => ['target' => '#create_expense_type_modal', 'toggle' => 'modal'],
                    'expenses' => ['target' => '#create_expense_modal', 'toggle' => 'modal'],
                    'payments' => ['target' => '#create_payment_modal', 'toggle' => 'modal'],
                    'languages' => ['target' => '#create_language_modal', 'toggle' => 'modal'],
                    'tasks' => ['target' => '#create_task_offcanvas', 'toggle' => 'offcanvas'], // ✅ offcanvas
                    'projects' => ['target' => '#create_project_offcanvas', 'toggle' => 'offcanvas'], // ✅ offcanvas
                    'priorities' => ['target' => '#create_priority_modal', 'toggle' => 'modal'],
                    'workspaces' => ['target' => '#createWorkspaceModal', 'toggle' => 'modal'],
                    'meetings' => ['target' => '#createMeetingModal', 'toggle' => 'modal'],
                    'task-lists' => ['target' => '#create_task_list_modal', 'toggle' => 'modal'],
                    'lead-sources' => ['target' => '#create_lead_source_modal', 'toggle' => 'modal'],
                    'lead-stages' => ['target' => '#create_lead_stage_modal', 'toggle' => 'modal'],
                    'candidates' => ['target' => '#candidateModal', 'toggle' => 'modal'],
                    'interview' => ['target' => '#createInterviewModal', 'toggle' => 'modal'],
                    'email-templates' => ['target' => '#createTemplateOffcanvas', 'toggle' => 'offcanvas'],
                ];

                $hasModal = array_key_exists($typeSlug, $modalMap);
                $href = $hasModal ? 'javascript:void(0)' : $link ?? url($typeSlug . '/create');
                $modalAttribute = $hasModal
                    ? 'data-bs-toggle="' .
                        $modalMap[$typeSlug]['toggle'] .
                        '" data-bs-target="' .
                        $modalMap[$typeSlug]['target'] .
                        '"'
                    : '';
            @endphp

            <a href="{{ $href }}" {!! $modalAttribute !!} class="btn btn-primary m-1">
                {{ get_label('create_now', 'Create now') }}
            </a>
        @endif
        </div>
    </div>
    @if ($flag == 1)
</div>
@endif
</div>
