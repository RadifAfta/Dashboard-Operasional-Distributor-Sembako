<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(): Response
    {
        $roles = Role::with(['permissions:id,name'])
            ->withCount('users')
            ->get();

        $permissions = Permission::select('id', 'name')->get()->groupBy(function ($perm) {
            $parts = explode('-', $perm->name);

            return count($parts) > 1 ? end($parts) : 'other';
        });

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'permissionsGrouped' => $permissions,
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', [
            'title' => 'Role Baru Berhasil Dibuat',
            'message' => "Tingkatan hak akses untuk '{$role->name}' telah siap dikonfigurasikan ke pengguna.",
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // Protect Super Admin role name from renaming
        if ($role->name === 'Super Admin' && $validated['name'] !== 'Super Admin') {
            return redirect()->route('admin.roles.index')->with('error', [
                'title' => 'Perubahan Dibatasi',
                'message' => 'Nama tingkatan Super Admin merupakan standar identitas sistem dan tidak dapat diubah.',
            ]);
        }

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')->with('success', [
            'title' => 'Matriks Hak Akses Diperbarui',
            'message' => "Daftar izin dan wewenang untuk role '{$role->name}' telah berhasil disinkronkan.",
        ]);
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['Super Admin', 'Admin', 'User'])) {
            return redirect()->route('admin.roles.index')->with('error', [
                'title' => 'Role Sistem Dilindungi',
                'message' => "Role bawaan '{$role->name}' sangat krusial bagi operasional platform dan tidak dapat dihapus.",
            ]);
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return redirect()->route('admin.roles.index')->with('error', [
                'title' => 'Role Masih Digunakan',
                'message' => "Terdapat {$userCount} pengguna yang masih menggunakan role ini. Mohon alihkan role mereka terlebih dahulu.",
            ]);
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', [
            'title' => 'Role Berhasil Dihapus',
            'message' => "Role '{$roleName}' telah dihapus dari daftar hak akses sistem.",
        ]);
    }
}
