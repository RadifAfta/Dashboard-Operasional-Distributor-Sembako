<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Filter admin menu items based on user permissions
        $adminMenu = [];
        if ($user) {
            $rawItems = config('admin_menu.items', []);
            foreach ($rawItems as $item) {
                // Check parent item permission
                if (! empty($item['permission']) && ! $user->can($item['permission'])) {
                    continue;
                }

                // Check and filter sub-items if present
                if (! empty($item['children'])) {
                    $filteredChildren = [];
                    foreach ($item['children'] as $child) {
                        if (empty($child['permission']) || $user->can($child['permission'])) {
                            $filteredChildren[] = $child;
                        }
                    }

                    if (empty($filteredChildren) && ! empty($item['permission'])) {
                        continue;
                    }
                    $item['children'] = $filteredChildren;
                }

                $adminMenu[] = $item;
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'adminMenu' => $adminMenu,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'appSettings' => [
                'name' => Setting::get('app_name', config('app.name', 'AdminHub')),
                'description' => Setting::get('app_description', ''),
                'currency_symbol' => Setting::get('currency_symbol', 'Rp'),
                'brand_color' => Setting::get('brand_color', 'indigo'),
            ],
        ];
    }
}
