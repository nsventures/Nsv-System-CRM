<?php

return [

    'modules' => [

        'leads' => [
            'label' => 'Leads',
            'route_prefix' => 'leads',
            'sample_file' => 'Leads Bulk Upload Sample File.xlsx',
            'instructions_file' => 'Leads_Bulk_Upload_Instructions.pdf',
            'fields' => [
                ['name' => 'first_name', 'required' => true],
                ['name' => 'last_name', 'required' => true],
                ['name' => 'email', 'required' => true],
                ['name' => 'country_code', 'required' => true],
                ['name' => 'country_iso_code', 'required' => true],
                ['name' => 'phone', 'required' => true],
                ['name' => 'source', 'required' => true],
                ['name' => 'stage', 'required' => true],
                ['name' => 'company', 'required' => true],
                ['name' => 'job_title', 'required' => false],
                ['name' => 'industry', 'required' => false],
                ['name' => 'website', 'required' => false],
            ],
            'display_columns' => ['id', 'first_name', 'last_name', 'email', 'phone']
        ],

        'users' => [
            'label' => 'Users',
            'route_prefix' => 'users',
            'sample_file' => 'Users Bulk Upload Sample File.xlsx',
            'instructions_file' => 'Users_Bulk_Upload_Instructions.pdf',
            'fields' => [
                ['name' => 'first_name', 'required' => true],
                ['name' => 'last_name', 'required' => true],
                ['name' => 'email', 'required' => true],
                ['name' => 'password', 'required' => true],
                ['name' => 'role_id', 'required' => true],
                ['name' => 'status', 'required' => true],
            ],
            'display_columns' => ['id', 'first_name', 'last_name', 'email']
        ],

    ]

];