<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request): Response
    {
        $totalUsers = User::count();
        $totalRoles = Role::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $recentUsers = User::with('roles:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'created_at']);

        // Monthly stats for chart demonstration
        $chartData = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'series' => [
                [
                    'name' => 'New Users',
                    'data' => [12, 19, 25, 32, 45, 58, 67, 80, 95, 110, 130, 150],
                ],
                [
                    'name' => 'Active Sessions',
                    'data' => [8, 14, 20, 26, 38, 49, 55, 68, 82, 98, 115, 138],
                ],
            ],
        ];

        $systemStats = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
            'db_driver' => config('database.default'),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_roles' => $totalRoles,
                'verified_users' => $verifiedUsers,
                'active_percentage' => $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100) : 0,
            ],
            'chartData' => $chartData,
            'recentUsers' => $recentUsers,
            'systemStats' => $systemStats,
        ]);
    }
}
