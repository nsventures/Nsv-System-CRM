<?php

namespace App\Services\BulkImport;

class BulkImportModuleConfig
{
    public static function get(string $type): array
    {
        return match ($type) {
            'users' => [
                'type' => 'users',
                'label' => get_label('users', 'Users'),
                'index_route' => route('users.index'),
                'upload_info' => get_label('users_bulk_upload_info', 'Supported: .xlsx, .xls, .csv. Max 10MB.'),
                'sample_file' => 'storage/files/Users bulk upload sample.xlsx',
                'instructions_file' => 'storage/files/Users bulk upload help and instructions.pdf',
                'breadcrumbs' => [
                    ['label' => get_label('users', 'Users'), 'url' => route('users.index')],
                ],
                'header_buttons' => [
                    ['url' => route('users.index'), 'label' => get_label('list_view', 'List View'), 'icon' => 'bx-list-ul'],
                ],
                'db_fields' => [
                    ['name' => 'first_name', 'required' => true],
                    ['name' => 'last_name', 'required' => true],
                    ['name' => 'email', 'required' => true],
                    ['name' => 'password', 'required' => true],
                    ['name' => 'password_confirmation', 'required' => true],
                    ['name' => 'role_id', 'required' => true],
                    ['name' => 'phone', 'required' => false],
                    ['name' => 'country_code', 'required' => false],
                    ['name' => 'country_iso_code', 'required' => false],
                    ['name' => 'dob', 'required' => false],
                    ['name' => 'doj', 'required' => false],
                    ['name' => 'status', 'required' => false],
                    ['name' => 'require_email_verification', 'required' => false],
                ],
                'display_columns' => ['id', 'first_name', 'last_name', 'email', 'role_id'],
            ],

            'clients' => [
                'type'             => 'clients',
                'label'            => get_label('clients', 'Clients'),
                'index_route'      => route('clients.index'),
                'sample_file'      => 'storage/files/Clients bulk upload sample.xlsx',
                'instructions_file' => 'storage/files/Clients bulk upload help and instructions.pdf',
                'breadcrumbs'      => [
                    ['label' => get_label('clients', 'Clients'), 'url' => route('clients.index')],
                ],
                'header_buttons'   => [
                    ['url' => route('clients.index'), 'label' => get_label('list_view', 'List View'), 'icon' => 'bx-list-ul'],
                ],
                'db_fields'        => [
                    ['name' => 'first_name',                'required' => true],
                    ['name' => 'last_name',                 'required' => true],
                    ['name' => 'email',                     'required' => true],
                    ['name' => 'is_for_internal_purpose',   'required' => true],
                    ['name' => 'password',                  'required' => false],
                    ['name' => 'password_confirmation',     'required' => false],
                    ['name' => 'status',                    'required' => false],
                    ['name' => 'require_email_verification', 'required' => false],
                    ['name' => 'phone',                     'required' => false],
                    ['name' => 'country_code',              'required' => false],
                    ['name' => 'country_iso_code',          'required' => false],
                    ['name' => 'company',                   'required' => false],
                    ['name' => 'address',                   'required' => false],
                    ['name' => 'city',                      'required' => false],
                    ['name' => 'state',                     'required' => false],
                    ['name' => 'zip',                       'required' => false],
                    ['name' => 'country',                   'required' => false],
                    ['name' => 'dob',                       'required' => false],
                    ['name' => 'doj',                       'required' => false],
                ],
                'display_columns'  => ['id', 'first_name', 'last_name', 'email', 'company'],
            ],

            'projects' => [
                'type'              => 'projects',
                'label'             => get_label('projects', 'Projects'),
                'index_route'       => route('projects.index'),
                'sample_file'       => 'storage/files/Projects bulk upload sample.xlsx',
                'instructions_file' => 'storage/files/Projects bulk upload instructions.pdf',
                'breadcrumbs'       => [
                    ['label' => get_label('projects', 'Projects'), 'url' => route('projects.index')],
                ],
                'header_buttons'    => [
                    ['url' => route('projects.index'), 'label' => get_label('list_view', 'List View'), 'icon' => 'bx-list-ul'],
                ],
                'db_fields'         => [
                    ['name' => 'title',               'required' => true],
                    ['name' => 'status_id',           'required' => true],
                    ['name' => 'task_accessibility',  'required' => true],
                    ['name' => 'client_can_discuss',  'required' => true],
                    ['name' => 'priority_id',         'required' => false],
                    ['name' => 'start_date',          'required' => false],
                    ['name' => 'end_date',            'required' => false],
                    ['name' => 'budget',              'required' => false],
                    ['name' => 'description',         'required' => false],
                    ['name' => 'note',                'required' => false],
                    ['name' => 'user_ids',            'required' => false],
                    ['name' => 'client_ids',          'required' => false],
                    ['name' => 'tag_ids',             'required' => false],
                    ['name' => 'is_favorite',         'required' => false],
                ],
                'display_columns'   => ['id', 'title', 'status_id', 'start_date', 'end_date'],
            ],

            'tasks' => [
                'type'              => 'tasks',
                'label'             => get_label('tasks', 'Tasks'),
                'index_route'       => route('tasks.index'),
                'sample_file'       => 'storage/files/Tasks bulk upload sample.xlsx',
                'instructions_file' => 'storage/files/Tasks bulk upload instructions.pdf',
                'breadcrumbs'       => [
                    ['label' => get_label('tasks', 'Tasks'), 'url' => route('tasks.index')],
                ],
                'header_buttons'    => [
                    ['url' => route('tasks.index'), 'label' => get_label('list_view', 'List View'), 'icon' => 'bx-list-ul'],
                ],
                'db_fields'         => [
                    ['name' => 'title',              'required' => true],
                    ['name' => 'status_id',          'required' => true],
                    ['name' => 'project_id',         'required' => true],
                    ['name' => 'client_can_discuss', 'required' => true],
                    ['name' => 'priority_id',        'required' => false],
                    ['name' => 'start_date',         'required' => false],
                    ['name' => 'end_date',           'required' => false],
                    ['name' => 'description',        'required' => false],
                    ['name' => 'note',               'required' => false],
                    ['name' => 'user_ids',           'required' => false],
                    ['name' => 'is_favorite',        'required' => false],
                ],
                'display_columns'   => ['id', 'title', 'project_id', 'status_id', 'start_date', 'due_date'],
            ],

            'leads' => [
                'type'              => 'leads',
                'label'             => get_label('leads', 'Leads'),
                'index_route'       => route('leads.index'),
                'sample_file'       => 'storage/files/Leads Bulk Upload Sample File.xlsx',
                'instructions_file' => 'storage/files/Leads_Bulk_Upload_Instructions.pdf',
                'breadcrumbs'       => [
                    ['label' => get_label('leads_management', 'Leads Management')],
                    ['label' => get_label('leads', 'Leads'), 'url' => route('leads.index')],
                ],
                'header_buttons'    => [
                    ['url' => route('leads.create'),      'label' => get_label('create_lead', 'Create Lead'),  'icon' => 'bx-plus'],
                    ['url' => route('leads.index'),       'label' => get_label('list_view', 'List View'),       'icon' => 'bx-list-ul'],
                    ['url' => route('leads.kanban_view'), 'label' => get_label('kanban_view', 'Kanban View'),   'icon' => 'bx-layout'],
                ],
                'db_fields'         => [
                    ['name' => 'first_name',       'required' => true],
                    ['name' => 'last_name',        'required' => true],
                    ['name' => 'email',            'required' => true],
                    ['name' => 'phone',            'required' => true],
                    ['name' => 'country_code',     'required' => true],
                    ['name' => 'country_iso_code', 'required' => true],
                    ['name' => 'source',           'required' => true],
                    ['name' => 'stage',            'required' => true],
                    ['name' => 'company',          'required' => true],
                    ['name' => 'job_title',        'required' => false],
                    ['name' => 'industry',         'required' => false],
                    ['name' => 'website',          'required' => false],
                    ['name' => 'linkedin',         'required' => false],
                    ['name' => 'instagram',        'required' => false],
                    ['name' => 'facebook',         'required' => false],
                    ['name' => 'pinterest',        'required' => false],
                    ['name' => 'city',             'required' => false],
                    ['name' => 'state',            'required' => false],
                    ['name' => 'zip',              'required' => false],
                    ['name' => 'country',          'required' => false],
                ],
                'display_columns'   => ['id', 'first_name', 'last_name', 'email', 'phone', 'company'],
            ],

            default => throw new \Exception("No config found for module: {$type}"),
        };
    }
}
