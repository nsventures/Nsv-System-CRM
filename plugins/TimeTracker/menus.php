<?php

/**
 * Time Tracker Plugin - Menu Configuration
 *
 * This file returns an array defining the menu structure for the Time Tracker plugin.
 * Each menu and submenu includes properties such as id, label, url, icon, class, category, and visibility.
 * Visibility and active states are determined based on user permissions and current route.
 */

return [
    [
        'id' => 'team_monitoring_and_productivity_tracker',
        'label' => get_label('team_insights', 'Team Insights'),
        'url' => route('timetracker.index'),
        'icon' => 'bx bx-alarm',
        'class' => 'menu-item' . (request()->is('timetracker*') ? ' active open' : ''),
        'category' => 'team_monitoring_and_productivity_tracker',
        'badge' => '<span class="badge rounded-pill bg-label-info text-uppercase ms-2">' . get_label('plugin', 'Plugin') . '</span>',
        'show' =>  1,
        'submenus' => [

            [
                'id' => 'productivity_dashboard',
                'label' => get_label('productivity_dashboard', 'Productivity Dashboard'),
                'url' => route('timetracker.index'),
                'class' => 'menu-item' . (request()->is('timetracker') ? ' active' : ''),
                'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
            ],
            [
                'id' => 'screenshots',
                'label' => get_label('screenshot_gallery', 'Screenshot Gallery'),
                'url' => route('timetracker.screenshots'),
                'class' => 'menu-item' . (request()->is('timetracker/screen-shots*') ? ' active ' : ''),
                'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
            ],
            [
                'id' => 'time_and_attendance',
                'label' => get_label('time_and_attendance', 'Time And Attendance'),
                'url' => route('time_and_attendance.index'),
                'class' => 'menu-item' . (request()->is('timetracker/time-and-attendance*') ? ' active' : ''),
                'show' => isUser() ?  1 : 0,
            ],
            [
                'id' => 'manual_time',
                'label' => get_label('manual_time', 'Manual Time'),
                'url' => route('timetracker.manual_time.index'),
                'class' => 'menu-item' . (request()->is('timetracker/manual-time*') ? ' active' : ''),
                'show' => isUser() ? 1 : 0,
            ],
            [
                'id' => 'configuration',
                'label' => get_label('configuration', 'Configuration'),
                'url' => route('timetracker.configuration'),
                'class' => 'menu-item' . (request()->is('timetracker/configuration') ? ' active' : ''),
                'show' => isAdminOrHasAllDataAccess() ? 1 : 0,
            ],
            [
                'id' => 'downloads',
                'label' => get_label('downloads', 'Downloads'),
                'url' => route('timetracker.downloads.index'),
                'class' => 'menu-item' . (request()->is('timetracker/downloads*') ? ' active' : ''),
                'show' => 1
            ]
        ],
    ],
];
