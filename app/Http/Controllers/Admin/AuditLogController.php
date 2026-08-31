<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $eventFilter = $request->input('event');
        $userIdFilter = $request->input('user_id');
        $perPage = (int) $request->input('per_page', 15);
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Allowed sort fields
        $allowedSorts = ['id', 'event', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

        $logs = AuditLog::with('user:id,name,email')
            ->search($search)
            ->filterEvent($eventFilter)
            ->filterUser($userIdFilter)
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        // Get available event types for filtering
        $availableEvents = AuditLog::select('event')
            ->distinct()
            ->pluck('event')
            ->values();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'availableEvents' => $availableEvents,
            'filters' => [
                'search' => $search,
                'event' => $eventFilter,
                'user_id' => $userIdFilter,
                'per_page' => $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }
}
