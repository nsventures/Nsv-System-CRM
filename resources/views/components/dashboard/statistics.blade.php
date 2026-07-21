@php
    $auth_user = getAuthenticatedUser();
@endphp
<div id="" class="row">
    @if ($auth_user->can('manage_projects'))
        <div class="col-md-4 col-sm-12 " id="project-statistics" data-id="project-statistics">
            <x-dashboard.card :title="get_label('project_statistics', 'Project statistics')" chart-id="projectStatisticsChart">
                <x-dashboard.status-list :statusCounts="[]" :statuses="[]" :totalCount="0" type="projects" />
            </x-dashboard.card>
        </div>
    @endif
    @if ($auth_user->can('manage_tasks'))
        <div class="col-md-4 col-sm-12 " id="task-statistics" data-id="task-statistics">
            <x-dashboard.card :title="get_label('task_statistics', 'Task statistics')" chart-id="taskStatisticsChart">
                <x-dashboard.status-list :statusCounts="[]" :statuses="[]" :totalCount="0" type="tasks" />
            </x-dashboard.card>
        </div>
    @endif
    <div class="col-md-4 col-sm-12 " id="todos-overview" data-id="todos-overview">
        <x-dashboard.card :title="get_label('todos_overview', 'Todos overview')" chart-id="todoStatisticsChart">
            <div class="d-flex justify-content-between mb-3">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#create_todo_modal">
                    <i class='bx bx-plus'></i> {{ get_label('create_todo', 'Create Todo') }}
                </button>
                <a href="{{ url('todos') }}" class="btn btn-sm btn-primary">
                    <i class="bx bx-list-ul"></i> {{ get_label('view_more', 'View more') }}
                </a>
            </div>
            <x-dashboard.todo-list :todos="[]" />
        </x-dashboard.card>
    </div>
    @if ($auth_user->hasRole('admin'))
        <div class="col-md-6 "  data-id="income-vs-expense">
            <x-dashboard.card :title="get_label('income_vs_expense', 'Income vs Expense')" chart-id="income-expense-chart">
                <!-- Income vs Expense chart populated via AJAX -->
            </x-dashboard.card>
        </div>
        <div class="col-md-6 " id="recent-activities" data-id="recent-activities">
            @php
                $cardId = 'recent-activity';
            @endphp
            <x-dashboard.card :title="get_label('recent_activities', 'Recent Activities')" icon="bx bx-bar-chart-alt-2" :cardId="$cardId">
                <x-dashboard.timeline :activities="[]" />
            </x-dashboard.card>
        </div>
    @endif
</div>
