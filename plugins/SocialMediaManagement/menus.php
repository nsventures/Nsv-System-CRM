<?php

use function PHPUnit\Framework\isArray;

$user = getAuthenticatedUser();

return [
    [
        'id'       => 'Social Media Management',
        'label'    => get_label('social_media', 'Social Media'),
        'class'    => 'menu-item' . (request()->is('social-media-scheduler/*') || request()->is('social-media-scheduler') ? ' active open' : ''),
        'category' => 'utilities',
        'show' => (getAuthenticatedUser()->hasRole('admin') || ($user->can('manage_posts'))) ? 1 : 0,
        'badge' => '<span class="badge rounded-pill bg-label-info text-uppercase ms-2">' . get_label('plugin', 'Plugin') . '</span>',
        'icon'     => 'bx bx-share-alt',
        'submenus' => [
            [
                'id'    => 'social_posts',
                'label' => get_label('posts', 'Posts'),
                'url'   => route('social.index'),
                'class' => 'menu-item' . (request()->is('social-media-scheduler') ? ' active' : ''),
                'show'  => (isAdminOrHasAllDataAccess() || ($user->can('manage_posts'))) ? 1 : 0,
            ],
            [
                'id'    => 'create_post',
                'label' => get_label('create_post', 'Create Post'),
                'url'   => route('social.create'),
                'class' => 'menu-item' . (request()->is('social-media-scheduler/create') ? ' active' : ''),
                'show'  => (isAdminOrHasAllDataAccess() || ($user->can('create_posts'))) ? 1 : 0,
            ],
            [
                'id'    => 'social_calendar',
                'label' => get_label('calendar', 'Calendar'),
                'url'   => route('social.calendar'),
                'class' => 'menu-item' . (request()->is('social-media-scheduler/calendar') ? ' active' : ''),
                'show'  => (isAdminOrHasAllDataAccess() || $user->can('manage_posts')) ? 1 : 0,
            ],
            [
                'id'    => 'social_analytics',
                'label' => get_label('analytics', 'Analytics'),
                'url'   => route('social.analytics'),
                'class' => 'menu-item' . (request()->is('social-media-scheduler/analytics') ? ' active' : ''),
                'show'  => (isAdminOrHasAllDataAccess() || $user->can('manage_posts')) ? 1 : 0,
            ],
            //[
               // 'id'    => 'social_settings',
                //'label' => get_label('settings', 'Settings'),
                //'url'   => route('social.settings.index'),
                //'class' => 'menu-item' . (request()->is('social-media-scheduler/social-settings') ? ' active' : ''),
                //'show'  => (isAdminOrHasAllDataAccess() || $user->can('manage_posts')) ? 1 : 0,
            //]
        ],
    ],
];