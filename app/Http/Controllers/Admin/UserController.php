<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $perPage = (int) $request->input('per_page', 10);
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Whitelist allowed sort fields
        $allowedSorts = ['id', 'name', 'email', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

        $users = User::with('roles:id,name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        $roles = Role::select('id', 'name')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'per_page' => $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', [
            'title' => 'Pengguna Berhasil Ditambahkan',
            'message' => "Akun {$user->name} telah berhasil didaftarkan dan siap digunakan.",
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', [
            'title' => 'Perubahan Berhasil Disimpan',
            'message' => "Data profil dan hak akses untuk {$user->name} telah diperbarui.",
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Prevent user from deleting own account
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')->with('error', [
                'title' => 'Tindakan Dibatasi',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk ke sistem.',
            ]);
        }

        // Prevent deleting the primary superadmin
        if ($user->hasRole('Super Admin') && User::role('Super Admin')->count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', [
                'title' => 'Proteksi Akun Kunci',
                'message' => 'Sistem wajib memiliki minimal satu akun Super Admin aktif. Akun ini dilindungi dari penghapusan.',
            ]);
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', [
            'title' => 'Pengguna Dihapus',
            'message' => "Akun {$userName} telah berhasil dihapus dari sistem.",
        ]);
    }

    /**
     * Remove multiple users from storage.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $currentUserId = $request->user()->id;
        $idsToDelete = array_filter($validated['ids'], fn ($id) => (int) $id !== $currentUserId);

        $count = count($idsToDelete);
        User::whereIn('id', $idsToDelete)->delete();

        return redirect()->route('admin.users.index')->with('success', [
            'title' => 'Penghapusan Massal Selesai',
            'message' => "Sebanyak {$count} pengguna terpilih berhasil dihapus dari database.",
        ]);
    }
}
