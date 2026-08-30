<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Navigation Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Each item can contain:
    | - title: Display name
    | - icon: Lucide icon name (e.g. 'LayoutDashboard', 'Users', 'Shield', 'Settings')
    | - route: Laravel named route
    | - active: Route pattern for active state (e.g. 'admin.dashboard*')
    | - permission: Spatie permission string required to view (or null for all authenticated)
    | - badge: Optional text badge or count
    | - children: Array of sub-menu items
    |
    */

    'items' => [
        [
            'title' => 'Dashboard',
            'icon' => 'LayoutDashboard',
            'route' => 'admin.dashboard',
            'active' => 'admin.dashboard*',
            'permission' => 'view-dashboard',
        ],
        [
            'title' => 'User Management',
            'icon' => 'Users',
            'active' => 'admin.users.*',
            'permission' => 'view-users',
            'children' => [
                [
                    'title' => 'All Users',
                    'route' => 'admin.users.index',
                    'active' => 'admin.users.index',
                    'permission' => 'view-users',
                ],
                [
                    'title' => 'Roles & Permissions',
                    'route' => 'admin.roles.index',
                    'active' => 'admin.roles.*',
                    'permission' => 'view-roles',
                ],
            ],
        ],
        [
            'title' => 'Settings',
            'icon' => 'Settings',
            'route' => 'admin.settings.index',
            'active' => 'admin.settings.*',
            'permission' => 'view-settings',
        ],
    ],
];
