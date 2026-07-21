<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Chatify\ChatifyMessenger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class MenuService
{
    public function getMenus()
    {
        $user = getAuthenticatedUser();
        $current_workspace_id = getWorkspaceId();
        $messenger = new ChatifyMessenger();
        $unread = $messenger->totalUnseenMessages();
        $pending_todos_count = $user->todos(0)->count();
        $ongoing_meetings_count = $user->meetings('ongoing')->count();
        $query = LeaveRequest::where('status', 'pending')
            ->where('workspace_id', $current_workspace_id);
        if (!is_admin_or_leave_editor()) {
            $query->where('user_id', $user->id);
        }
        $pendingLeaveRequestsCount = $query->count();
        return [
            [
                'id' => 'dashboard',
                'label' => get_label('dashboard', 'Dashboard'),
                'url' => url('home'),
                'icon' => 'bx bx-home-circle',
                'class' => 'menu-item' . (Request::is('home') ? ' active' : ''),
                'category' => 'dashboard',
            ],
            [
                'id' => 'projects',
                'label' => get_label('projects', 'Projects'),
                'url' => url('projects'),
                'icon' => 'bx bx-briefcase-alt-2',
                'class' => 'menu-item' . (Request::is('projects') || Request::is('tags/*') || Request::is('projects/*') ? ' active open' : ''),
                'category' => 'projects_and_task_management',
                'show' => ($user->can('manage_projects') || $user->can('manage_tags')) ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'manage_projects',
                        'label' => get_label('manage_projects', 'Manage projects'),
                        'url' => url(getUserPreferences('projects', 'default_view')),
                        'icon' => 'bx bx-list-ul',
                        'class' => 'menu-item' . (Request::is('projects') || (Request::is('projects/*') && !Request::is('projects/*/favorite') && !Request::is('projects/favorite') && !Request::is('projects/bulk-upload')) ? ' active' : ''),
                        'show' => ($user->can('manage_projects')) ? 1 : 0
                    ],
                    [
                        'id' => 'favorite_projects',
                        'label' => get_label('favorite_projects', 'Favorite projects'),
                        'url' => url('/projects/list/favorite?is_favorites=1'),
                        'icon' => 'bx bx-star',
                        'class' => 'menu-item' . (Request::is('projects/favorite') || Request::is('projects/list/favorite') || Request::is('projects/kanban/favorite') ? ' active' : ''),
                        'show' => ($user->can('manage_projects')) ? 1 : 0
                    ],
                    [
                        'id' => 'projects_bulk_upload',
                        'label' => get_label('bulk_upload', 'Bulk Upload'),
                        'url' => route('projects.showBulkUploadForm'),
                        'icon' => 'bx bx-upload',
                        'class' => 'menu-item' . (Request::is('projects/bulk-upload') ? ' active' : ''),
                        'show' => ($user->can('manage_projects') && $user->can('create_projects')) ? 1 : 0
                    ],
                    [
                        'id' => 'tags',
                        'label' => get_label('tags', 'Tags'),
                        'url' => url('tags/manage'),
                        'icon' => 'bx bx-purchase-tag',
                        'class' => 'menu-item' . (Request::is('tags/*') ? ' active' : ''),
                        'show' => ($user->can('manage_tags')) ? 1 : 0
                    ],
                    [
                        'id' => 'task-lists',
                        'label' => get_label('task_lists', 'Task lists'),
                        'url' => url('/task-lists'),
                        'icon' => 'bx bx-list-check',
                        'class' => 'menu-item' . (Request::is('task-lists/*') ? ' active' : ''),
                        'show' =>  1
                    ],
                ],
            ],
            [
                'id' => 'tasks',
                'label' => get_label('tasks', 'Tasks'),
                'url' => url('tasks'),
                'icon' => 'bx bx-task',
                'class' => 'menu-item' . (Request::is('tasks') || Request::is('tasks/*') ? ' active open' : ''),
                'show' => $user->can('manage_tasks') ? 1 : 0,
                'category' => 'projects_and_task_management',
                'submenus' => [
                    [
                        'id' => 'manage_tasks',
                        'label' => get_label('manage_tasks', 'Manage Tasks'),
                        'url' => url(getUserPreferences('tasks', 'default_view')),
                        'icon' => 'bx bx-task',
                        'class' => 'menu-item' . (!(request()->query('favorite')) && (Request::is('tasks') || Request::is('tasks/*') && !Request::is('tasks/bulk-upload')) ? ' active' : ''),
                        'show' => ($user->can('manage_tasks')) ? 1 : 0
                    ],
                    [
                        'id' => 'favorite_tasks',
                        'label' => get_label('favorite_tasks', 'Favorite Tasks'),
                        'url' => url(getUserPreferences('tasks', 'default_view') . '?favorite=1'),
                        'icon' => 'bx bx-star',
                        'class' => 'menu-item' . (request()->query('favorite') && (Request::is('tasks') || Request::is('tasks/calendar') || Request::is('tasks/draggable')) ? ' active' : ''),
                        'show' => ($user->can('manage_tasks')) ? 1 : 0
                    ],
                    [
                        'id' => 'tasks_bulk_upload',
                        'label' => get_label('bulk_upload', 'Bulk Upload'),
                        'url' => route('tasks.showBulkUploadForm'),
                        'icon' => 'bx bx-upload',
                        'class' => 'menu-item' . (Request::is('tasks/bulk-upload') ? ' active' : ''),
                        'show' => ($user->can('manage_tasks') && $user->can('create_tasks')) ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'statuses',
                'label' => get_label('statuses', 'Statuses'),
                'url' => url('status/manage'),
                'icon' => 'bx bx-grid-small',
                'class' => 'menu-item' . (Request::is('status/manage') ? ' active' : ''),
                'show' => $user->can('manage_statuses') ? 1 : 0,
                'category' => 'projects_and_task_management',
            ],
            [
                'id' => 'priorities',
                'label' => get_label('priorities', 'Priorities'),
                'url' => url('priority/manage'),
                'icon' => 'bx bx-up-arrow-alt',
                'class' => 'menu-item' . (Request::is('priority/manage') ? ' active' : ''),
                'show' => $user->can('manage_priorities') ? 1 : 0,
                'category' => 'projects_and_task_management',
            ],
            [
                'id' => 'workspaces',
                'label' => get_label('workspaces', 'Workspaces'),
                'url' => url('workspaces'),
                'icon' => 'bx bx-check-square',
                'class' => 'menu-item' . (Request::is('workspaces') || Request::is('workspaces/*') ? ' active' : ''),
                'show' => $user->can('manage_workspaces') ? 1 : 0,
                'category' => 'team',
            ],
            [
                'id' => 'chat',
                'label' => get_label('chat', 'Chat'),
                'url' => url('chat'),
                'icon' => 'bx bx-chat',
                'class' => 'menu-item' . (Request::is('chat') || Request::is('chat/*') ? ' active' : ''),
                'badge' => ($unread > 0) ? '<span class="flex-shrink-0 tk-badge-counter tk-badge-counter-danger">' . $unread . '</span>' : '',
                'show' => Auth::guard('web')->check() ? 1 : 0,
                'category' => 'team',
            ],
              [
                'id' => 'users',
                'label' => get_label('users', 'Users'),
                'url' => url('users'),
                'icon' => 'bx bx-group',
                'class' => 'menu-item' . (Request::is('users') || Request::is('users/*') ? ' active' : ''),
                'show' => $user->can('manage_users') ? 1 : 0,
                'category' => 'team',
            ],
            [
                'id' => 'clients',
                'label' => get_label('clients', 'Clients'),
                'url' => url('clients'),
                'icon' => 'bx bx-group',
                'class' => 'menu-item' . (Request::is('clients') || Request::is('clients/*') ? ' active' : ''),
                'show' => $user->can('manage_clients') ? 1 : 0,
                'category' => 'team',
            ],
            [
                'id' => 'leads_management',
                'label' => get_label('leads_management', 'Leads Management'),
                'url' => '',
                'icon' => 'bx bxs-phone-call',
                'class' => 'menu-item ' . (Request::is('lead-sources') || Request::is('lead-sources/*') || Request::is('lead-stages') || Request::is('lead-stages/*') || Request::is('leads') || Request::is('leads/*') || Request::is('lead-forms') || Request::is('lead-forms/*')  ? 'active open' : ''),
                'category' => 'utilities',
                'show' =>  $user->can('manage_leads') ? 1 : 0,
                'submenus' => [
                    [
                        'id' => 'lead_sources',
                        'label' => get_label('lead_sources', 'Lead Sources'),
                        'url' => route('lead-sources.index'),
                        'icon' => 'bx bx-bullseye',
                        'show' => $user->can('manage_leads') ? 1 : 0,
                        'class' => 'menu-item ' . (Request::is('lead-sources') || Request::is('lead-sources/*') ? 'active' : '')
                    ],
                    [
                        'id' => 'lead_stages',
                        'label' => get_label('lead_stages', 'Lead Stages'),
                        'url' => route('lead-stages.index'),
                        'icon' => 'bx bx-trending-up',
                        'show' => $user->can('manage_leads') ? 1 : 0,
                        'class' => 'menu-item ' . (Request::is('lead-stages') || Request::is('lead-stages/*') ? 'active' : '')
                    ],
                    [
                        'id' => 'leads',
                        'label' => get_label('leads', 'Leads'),
                        'url' => getDefaultRoute('leads'),
                        'icon' => 'bx bx-user-pin',
                        'show' => $user->can('manage_leads') ? 1 : 0,
                        'class' => 'menu-item ' . (Request::is('leads') || (Request::is('leads/*') && !Request::is('leads/bulk-upload')) ? 'active' : '')
                    ],
                    [
                        'id' => 'lead_bulk_upload',
                        'label' => get_label('bulk_upload', 'Bulk Upload'),
                        'url' => route('leads.upload'),
                        'icon' => 'bx bx-upload',
                        'class' => 'menu-item' . (Request::is('leads/bulk-upload') ? ' active' : ''),
                        'show' => ($user->can('manage_leads') && $user->can('create_leads')) ? 1 : 0
                    ],
                    [
                        'id' => 'lead_forms',
                        'label' => get_label('lead_forms', 'Lead Forms'),
                        'url' => route('lead-forms.index'),
                        'icon' => 'bx bx-detail',
                        'class' => 'menu-item' . (Request::is('lead-forms') || (Request::is('lead-forms/*')) ? ' active' : ''),
                        'show' => ($user->can('manage_leads') && $user->can('create_leads')) ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'email',
                'label' => get_label('email', 'Email'),
                'class' => 'menu-item' . (Request::is('emails') || Request::is('emails/create') || Request::is('email-templates') ? ' active open' : ''),
                'category' => 'email',
                'show' => ($user->can('send_email') || $user->can('manage_email_template')) ? 1 : 0,
                'icon' => 'bx bx-mail-send',
                'submenus' => [
                    [
                        'id' => 'email_history',
                        'label' => get_label('send_email', 'Send Email'),
                        'url' => route('emails.sent_list'),
                        'icon' => 'bx bx-history',
                        'class' => 'menu-item' . (Request::is('emails') || Request::is('emails/create') ? ' active' : ''),
                        'show' => $user->can('send_email') ? 1 : 0
                    ],
                    [
                        'id' => 'email_templates',
                        'label' => get_label('email_templates', 'Email Templates'),
                        'url' => route('email.templates'),
                        'icon' => 'bx bx-layer',
                        'class' => 'menu-item' . (Request::is('email-templates') ? ' active' : ''),
                        'show' => $user->can('manage_email_template') ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'todos',
                'label' => get_label('todos', 'Todos'),
                'url' => url('todos'),
                'icon' => 'bx bx-list-check',
                'class' => 'menu-item' . (Request::is('todos') || Request::is('todos/*') ? ' active' : ''),
                'badge' => ($pending_todos_count > 0) ? '<span class="flex-shrink-0 tk-badge-counter tk-badge-counter-danger">' . $pending_todos_count . '</span>' : '',
                'category' => 'todos',
            ],
            [
                'id' => 'meetings',
                'label' => get_label('meetings', 'Meetings'),
                'url' => getDefaultRoute('meetings'),
                'icon' => 'bx bx-shape-polygon',
                'class' => 'menu-item' . (Request::is('meetings') || Request::is('meetings/*') ? ' active' : ''),
                'badge' => ($ongoing_meetings_count > 0) ? '<span class="flex-shrink-0 tk-badge-counter tk-badge-counter-success">' . $ongoing_meetings_count . '</span>' : '',
                'show' => $user->can('manage_meetings') ? 1 : 0,
                'category' => 'utilities',
            ],
          
            [
                'id' => 'contracts',
                'label' => get_label('contracts', 'Contracts'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-news',
                'class' => 'menu-item' . (Request::is('contracts') || Request::is('contracts/*') ? ' active open' : ''),
                'show' => ($user->can('manage_contracts') || $user->can('manage_contract_types')) ? 1 : 0,
                'category' => 'finance',
                'submenus' => [
                    [
                        'id' => 'manage_contracts',
                        'label' => get_label('manage_contracts', 'Manage contracts'),
                        'url' => url('contracts'),
                        'icon' => 'bx bx-news',
                        'class' => 'menu-item' . (Request::is('contracts') ? ' active' : ''),
                        'show' => $user->can('manage_contracts') ? 1 : 0
                    ],
                    [
                        'id' => 'contract_types',
                        'label' => get_label('contract_types', 'Contract types'),
                        'url' => url('contracts/contract-types'),
                        'icon' => 'bx bx-collection',
                        'class' => 'menu-item' . (Request::is('contracts/contract-types') ? ' active' : ''),
                        'show' => $user->can('manage_contract_types') ? 1 : 0
                    ],
                ],
            ],

            [
                'id' => 'finance',
                'label' => get_label('finance', 'Finance'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-box',
                'class' => 'menu-item' . (Request::is('estimates-invoices') || Request::is('estimates-invoices/*') || Request::is('taxes') || Request::is('payment-methods') || Request::is('payments') || Request::is('units') || Request::is('items') || Request::is('expenses') || Request::is('expenses/*') ? ' active open' : ''),
                'show' => ($user->can('manage_estimates_invoices') || $user->can('manage_expenses') || $user->can('manage_payment_methods') ||
                    $user->can('manage_expense_types') || $user->can('manage_payments') || $user->can('manage_taxes') ||
                    $user->can('manage_units') || $user->can('manage_items')) ? 1 : 0,
                'category' => 'finance',
                'submenus' => [
                    [
                        'id' => 'expenses',
                        'label' => get_label('expenses', 'Expenses'),
                        'url' => url('expenses'),
                        'icon' => 'bx bx-credit-card',
                        'class' => 'menu-item' . (Request::is('expenses') ? ' active' : ''),
                        'show' => $user->can('manage_expenses') ? 1 : 0
                    ],
                    [
                        'id' => 'expense_types',
                        'label' => get_label('expense_types', 'Expense types'),
                        'url' => url('expenses/expense-types'),
                        'icon' => 'bx bx-category',
                        'class' => 'menu-item' . (Request::is('expenses/expense-types') ? ' active' : ''),
                        'show' => $user->can('manage_expense_types') ? 1 : 0
                    ],
                    [
                        'id' => 'estimates_invoices',
                        'label' => get_label('estimates_invoices', 'Estimates/Invoices'),
                        'url' => url('estimates-invoices'),
                        'icon' => 'bx bx-file-blank',
                        'class' => 'menu-item' . (Request::is('estimates-invoices') || Request::is('estimates-invoices/*') ? ' active' : ''),
                        'show' => $user->can('manage_estimates_invoices') ? 1 : 0
                    ],
                    [
                        'id' => 'payments',
                        'label' => get_label('payments', 'Payments'),
                        'url' => url('payments'),
                        'icon' => 'bx bx-money',
                        'class' => 'menu-item' . (Request::is('payments') ? ' active' : ''),
                        'show' => $user->can('manage_payments') ? 1 : 0
                    ],
                    [
                        'id' => 'payment_methods',
                        'label' => get_label('payment_methods', 'Payment methods'),
                        'url' => url('payment-methods'),
                        'icon' => 'bx bx-wallet',
                        'class' => 'menu-item' . (Request::is('payment-methods') ? ' active' : ''),
                        'show' => $user->can('manage_payment_methods') ? 1 : 0
                    ],
                    [
                        'id' => 'taxes',
                        'label' => get_label('taxes', 'Taxes'),
                        'url' => url('taxes'),
                        'icon' => 'bx bx-barcode',
                        'class' => 'menu-item' . (Request::is('taxes') ? ' active' : ''),
                        'show' => $user->can('manage_taxes') ? 1 : 0
                    ],
                    [
                        'id' => 'units',
                        'label' => get_label('units', 'Units'),
                        'url' => url('units'),
                        'icon' => 'bx bx-ruler',
                        'class' => 'menu-item' . (Request::is('units') ? ' active' : ''),
                        'show' => $user->can('manage_units') ? 1 : 0
                    ],
                    [
                        'id' => 'items',
                        'label' => get_label('items', 'Items'),
                        'url' => url('items'),
                        'icon' => 'bx bx-box',
                        'class' => 'menu-item' . (Request::is('items') ? ' active' : ''),
                        'show' => $user->can('manage_items') ? 1 : 0
                    ],
                ],
            ],
            [
                'id' => 'reports',
                'label' => get_label('reports', 'Reports'),
                'url' => 'javascript:void(0)',
                'icon' => 'bx bx-file',
                'class' => 'menu-item' . (Request::is('reports') || Request::is('reports/*') ? ' active open' : ''),
                'show' => $user->hasRole('admin') || Auth::guard('web')->check() || checkPermission('manage_projects') || checkPermission('manage_tasks') || checkPermission('manage_estimates_invoices') ? 1 : 0,
                'category' => 'reports',
                'submenus' => [
                    [
                        'id' => 'projects_report',
                        'label' => get_label('projects', 'Projects'),
                        'url' => route('reports.projects'),
                        'icon' => 'bx bx-briefcase-alt-2',
                        'class' => 'menu-item' . (Request::is('reports/projects') ? ' active' : ''),
                        'show' => checkPermission('manage_projects') ? 1 : 0,
                    ],
                    [
                        'id' => 'tasks_report',
                        'label' => get_label('tasks', 'Tasks'),
                        'url' => route('reports.tasks'),
                        'icon' => 'bx bx-task',
                        'class' => 'menu-item' . (Request::is('reports/tasks') ? ' active' : ''),
                        'show' => checkPermission('manage_tasks') ? 1 : 0,
                    ],
                    [
                        'id' => 'estimates_invoices_report',
                        'label' => get_label('estimates_invoices', 'Estimates/Invoices'),
                        'url' => route('reports.invoices-report'),
                        'icon' => 'bx bx-file',
                        'class' => 'menu-item' . (Request::is('reports/estimates-invoices') ? ' active' : ''),
                        'show' => checkPermission('manage_estimates_invoices') ? 1 : 0,
                    ],
                    [
                        'id' => 'income_vs_expense',
                        'label' => get_label('income_vs_expense', 'Income vs Expense'),
                        'url' => route('reports.income-vs-expense'),
                        'icon' => 'bx bx-pie-chart-alt-2',
                        'class' => 'menu-item' . (Request::is('reports/income-vs-expense') ? ' active' : ''),
                        'show' => $user->hasRole('admin') ? 1 : 0,
                    ],
                    [
                        'id' => 'leaves',
                        'label' => get_label('leaves', 'Leaves'),
                        'url' => route('reports.leaves'),
                        'icon' => 'bx bx-calendar-event',
                        'class' => 'menu-item' . (Request::is('reports/leaves') ? ' active' : ''),
                        'show' => Auth::guard('web')->check() ? 1 : 0,
                    ]
                ],
            ],
            [
                'id' => 'hrms',
                'label' => get_label('HRMS', 'HRMS'),
                'icon' => 'bx bx-group',
                'class' => 'menu-item' . (Request::is('candidate*') || Request::is('candidate_status*') || Request::is('interviews*') || Request::is('leave-requests*') || Request::is('payslips*') || Request::is('allowances*') || Request::is('deductions*') ? ' active open' : ''),
                'show' => ($user->can('manage_candidate') || $user->can('manage_candidate_status') || $user->can('manage_interview') || Auth::guard('web')->check() || $user->can('manage_payslips') || $user->can('manage_allowances') || $user->can('manage_deductions')) ? 1 : 0,
                'category' => 'hrms',
                'submenus' => [
                    [
                        'id' => 'candidates',
                        'label' => get_label('candidate', 'Candidates'),
                        'url' => route('candidate.index'),
                        'icon' => 'bx bx-user-plus',
                        'class' => 'menu-item' . (Request::is('candidate/index') ? ' active' : ''),
                        'show' => $user->can('manage_candidate') ? 1 : 0,
                    ],
                    [
                        'id' => 'candidates_status',
                        'label' => get_label('candidate_status', 'Candidates Status'),
                        'url' => route('candidate.status.index'),
                        'icon' => 'bx bx-user-check',
                        'class' => 'menu-item' . (Request::is('candidate_status*') ? ' active' : ''),
                        'show' => $user->can('manage_candidate_status') ? 1 : 0,
                    ],
                    [
                        'id' => 'interviews',
                        'label' => get_label('interviews', 'Interviews'),
                        'url' => route('interviews.index'),
                        'icon' => 'bx bx-microphone',
                        'class' => 'menu-item' . (Request::is('interviews*') ? ' active' : ''),
                        'show' => $user->can('manage_interview') ? 1 : 0,
                    ],
                    [
                        'id' => 'leave_requests',
                        'label' => get_label('leave_requests', 'Leave requests'),
                        'url' => getDefaultRoute('leave_requests'),
                        'icon' => 'bx bx-calendar-event',
                        'class' => 'menu-item' . (Request::is('leave-requests') || Request::is('leave-requests/*') ? ' active' : ''),
                        'badge' => ($pendingLeaveRequestsCount > 0) ? '<span class="flex-shrink-0 tk-badge-counter tk-badge-counter-danger">' . $pendingLeaveRequestsCount . '</span>' : '',
                        'show' => Auth::guard('web')->check() ? 1 : 0,
                    ],
                    [
                        'id' => 'manage_payslips',
                        'label' => get_label('manage_payslips', 'Manage payslips'),
                        'url' => url('payslips'),
                        'icon' => 'bx bx-receipt',
                        'class' => 'menu-item' . (Request::is('payslips') || Request::is('payslips/*') ? ' active' : ''),
                        'show' => $user->can('manage_payslips') ? 1 : 0
                    ],
                    [
                        'id' => 'allowances',
                        'label' => get_label('allowances', 'Allowances'),
                        'url' => url('allowances'),
                        'icon' => 'bx bx-plus-circle',
                        'class' => 'menu-item' . (Request::is('allowances') ? ' active' : ''),
                        'show' => $user->can('manage_allowances') ? 1 : 0
                    ],
                    [
                        'id' => 'deductions',
                        'label' => get_label('deductions', 'Deductions'),
                        'url' => url('deductions'),
                        'icon' => 'bx bx-minus-circle',
                        'class' => 'menu-item' . (Request::is('deductions') ? ' active' : ''),
                        'show' => $user->can('manage_deductions') ? 1 : 0
                    ],
                ]
            ],
            [
                'id' => 'notes',
                'label' => get_label('notes', 'Notes'),
                'url' => url('notes'),
                'icon' => 'bx bx-notepad',
                'class' => 'menu-item' . (Request::is('notes') || Request::is('notes/*') ? ' active' : ''),
                'category' => 'notes',
            ],

            [
                'id' => 'activity_log',
                'label' => get_label('activity_log', 'Activity log'),
                'url' => getDefaultRoute('activity_logs'),
                'icon' => 'bx bx-line-chart',
                'class' => 'menu-item' . (Request::is('activity-log') || Request::is('activity-log/*') ? ' active' : ''),
                'show' => $user->can('manage_activity_log') ? 1 : 0,
                'category' => 'utilities',
            ],
            [
                'id' => 'calendars',
                'label' => get_label('calendars', 'Calendars'),
                'icon' => 'bx bx-calendar',
                'class' => 'menu-item' . (Request::is('calendars') || Request::is('calendars/*') ? ' active open' : ''),
                'show' => 1,
                'category' => 'utilities',
                'submenus' => [
                    [
                        'id' => 'holiday_calendar',
                        'label' => get_settings('google_calendar_settings')['calendar_name'] ??  get_label('holiday_calendar', 'Holiday Calendar'),
                        'url' => route('calendars.holiday_calendar'),
                        'icon' => 'bx bx-calendar-star',
                        'show' => 1,
                        'class' => 'menu-item' . (Request::is('calendars/holiday-calendar') ? ' active' : ''),
                    ],
                ]
            ],
            [
                'id' => 'general_file_manager',
                'label' => get_label('general_file_manager', 'General File Manager'),
                'url' => route('file-manager.index'),
                'icon' => 'bx bx-folder-open',
                'class' => 'menu-item' . (Request::is('file-manager') || Request::is('file-manager/*') ? ' active' : ''),
                'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
                'category' => 'file_manager',
            ],
            [
                'id' => 'settings',
                'label' => get_label('settings', 'Settings'),
                'icon' => 'bx bx-box',
                'class' => 'menu-item' . (Request::is('settings') || Request::is('roles/*') || Request::is('settings/*') ? ' active open' : ''),
                'show' => $user->hasRole('admin') ? 1 : 0,
                'category' => 'settings',
                'submenus' => [
                    [
                        'id' => 'general',
                        'label' => get_label('general', 'General'),
                        'url' => url('settings/general'),
                        'icon' => 'bx bx-cog',
                        'class' => 'menu-item' . (Request::is('settings/general') ? ' active' : ''),
                    ],
                    [
                        'id' => 'company',
                        'label' => get_label('company_info', 'Company Information'),
                        'url' => url('settings/company-info'),
                        'icon' => 'bx bx-buildings',
                        'class' => 'menu-item' . (Request::is('settings/company-info') ? ' active' : ''),
                    ],
                    [
                        'id' => 'custom_fields',
                        'label' => get_label('custom_fields', 'Custom Fields'),
                        'url' => route('custom_fields.index'),
                        'icon' => 'bx bx-customize',
                        'class' => 'menu-item' . (Request::is('settings/custom-fields') ? ' active' : ''),
                    ],
                    [
                        'id' => 'security',
                        'label' => get_label('security', 'Security'),
                        'url' => url('settings/security'),
                        'icon' => 'bx bx-shield-quarter',
                        'class' => 'menu-item' . (Request::is('settings/security') ? ' active' : ''),
                    ],
                    [
                        'id' => 'permissions',
                        'label' => get_label('permissions', 'Permissions'),
                        'url' => url('settings/permission'),
                        'icon' => 'bx bx-lock-open-alt',
                        'class' => 'menu-item' . (Request::is('settings/permission') || Request::is('roles/*') ? ' active' : ''),
                    ],
                    [
                        'id' => 'languages',
                        'label' => get_label('languages', 'Languages'),
                        'url' => url('settings/languages'),
                        'icon' => 'bx bx-globe',
                        'class' => 'menu-item' . (Request::is('settings/languages') || Request::is('settings/languages/create') ? ' active' : ''),
                    ],
                    [
                        'id' => 'email',
                        'label' => get_label('email', 'Email'),
                        'url' => url('settings/email'),
                        'icon' => 'bx bx-mail-send',
                        'class' => 'menu-item' . (Request::is('settings/email') ? ' active' : ''),
                    ],
                    [
                        'id' => 'ai_model_settings',
                        'label' => get_label('ai_model_settings', 'AI Model Settings'),
                        'url' => url('settings/ai-model-settings'),
                        'icon' => 'bx bx-bot',
                        'class' => 'menu-item' . (Request::is('settings/ai-model-settings') ? ' active' : ''),
                    ],
                    [
                        'id' => 'sms_gateway',
                        'label' => get_label('messaging_and_integrations', 'Messaging & Integrations'),
                        'url' => url('settings/sms-gateway'),
                        'icon' => 'bx bx-message-rounded-dots',
                        'class' => 'menu-item' . (Request::is('settings/sms-gateway') ? ' active' : ''),
                    ],
                    [
                        'id' => 'google_calendar',
                        'label' => get_label('google_calendar', 'Google Calendar'),
                        'url' => route('google_calendar.index'),
                        'icon' => 'bx bx-calendar',
                        'class' => 'menu-item' . (Request::is('settings/google-calendar') ? ' active' : ''),
                    ],
                    [
                        'id' => 'pusher',
                        'label' => get_label('pusher', 'Pusher'),
                        'url' => url('settings/pusher'),
                        'icon' => 'bx bx-broadcast',
                        'class' => 'menu-item' . (Request::is('settings/pusher') ? ' active' : ''),
                    ],
                    [
                        'id' => 'media_storage',
                        'label' => get_label('media_storage', 'Media storage'),
                        'url' => url('settings/media-storage'),
                        'icon' => 'bx bx-cloud-upload',
                        'class' => 'menu-item' . (Request::is('settings/media-storage') ? ' active' : ''),
                    ],
                    [
                        'id' => 'notification_templates',
                        'label' => get_label('notification_templates', 'Notification Templates'),
                        'url' => url('settings/templates'),
                        'icon' => 'bx bx-bell',
                        'class' => 'menu-item' . (Request::is('settings/templates') ? ' active' : ''),
                    ],
                    [
                        'id' => 'privacy_policy',
                        'label' => get_label('terms_privacy_about', 'Terms, Privacy & About'),
                        'url' => url('settings/terms-privacy-about'),
                        'icon' => 'bx bx-shield-alt-2',
                        'class' => 'menu-item' . (Request::is('settings/terms-privacy-about') ? ' active' : ''),
                    ],
                    [
                        'id' => 'plugins',
                        'label' => get_label('plugins', 'Plugins'),
                        'url' => route('plugins.index'),
                        'icon' => 'bx bx-extension',
                        'class' => 'menu-item' . (Request::is('settings/plugins') ? ' active' : ''),
                    ],
                    [
                        'id' => 'system_updater',
                        'label' => get_label('system_updater', 'System updater'),
                        'url' => url('settings/system-updater'),
                        'icon' => 'bx bx-refresh',
                        'class' => 'menu-item' . (Request::is('settings/system-updater') ? ' active' : ''),
                    ],
                    [
                        'id' => 'pwa',
                        'label' => get_label('pwa_settings', 'PWA Settings'),
                        'url' => url('settings/pwa-settings'),
                        'icon' => 'bx bx-mobile-alt',
                        'class' => 'menu-item' . (Request::is('settings/pwa-settings') ? ' active' : ''),
                    ]
                ]
            ]
        ];
    }
}
