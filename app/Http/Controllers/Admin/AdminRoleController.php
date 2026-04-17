<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = AdminRole::query()
            ->withCount(['users as users_count' => fn ($query) => $query->withTrashed()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $permissionCatalog = AdminRole::permissionCatalog();

        return view('admin.roles.index', compact('roles', 'permissionCatalog'));
    }

    public function store(Request $request): RedirectResponse
    {
        $permissionKeys = AdminRole::availablePermissionKeys();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'can_access_admin_panel' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionKeys)],
        ]);

        $roleKey = $this->makeRoleKey($validated['key'] ?? $validated['name']);

        if (AdminRole::query()->where('key', $roleKey)->exists()) {
            return back()->withErrors([
                'key' => 'Role key already exists. Choose a different key.',
            ])->withInput();
        }

        $canAccessAdminPanel = $request->boolean('can_access_admin_panel');
        $permissions = AdminRole::normalizePermissions((array) ($validated['permissions'] ?? []));

        if ($canAccessAdminPanel && $permissions !== ['*']) {
            $permissions = $this->ensureDashboardPermission($permissions);
        }

        if (!$canAccessAdminPanel && $permissions !== ['*']) {
            $permissions = [];
        }

        AdminRole::create([
            'name' => trim($validated['name']),
            'key' => $roleKey,
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'can_access_admin_panel' => $canAccessAdminPanel,
            'is_active' => $request->boolean('is_active', true),
            'is_system' => false,
            'permissions' => $permissions,
            'sort_order' => ((int) AdminRole::max('sort_order')) + 1,
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(AdminRole $role): View
    {
        $permissionCatalog = AdminRole::permissionCatalog();

        return view('admin.roles.edit', compact('role', 'permissionCatalog'));
    }

    public function update(Request $request, AdminRole $role): RedirectResponse
    {
        $permissionKeys = AdminRole::availablePermissionKeys();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/', Rule::unique('admin_roles', 'key')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'can_access_admin_panel' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionKeys)],
        ]);

        $newKey = $role->is_system
            ? $role->key
            : $this->makeRoleKey($validated['key'] ?? $validated['name']);

        if (!$role->is_system && AdminRole::query()->where('key', $newKey)->where('id', '!=', $role->id)->exists()) {
            return back()->withErrors([
                'key' => 'Role key already exists. Choose a different key.',
            ])->withInput();
        }

        $permissions = AdminRole::normalizePermissions((array) ($validated['permissions'] ?? []));

        $canAccessAdminPanel = $request->boolean('can_access_admin_panel');
        $isActive = $request->boolean('is_active', true);

        if ($role->key === User::ROLE_ADMIN) {
            // Keep admin role always active with full permission.
            $canAccessAdminPanel = true;
            $isActive = true;
            $permissions = ['*'];
        } elseif ($canAccessAdminPanel && $permissions !== ['*']) {
            $permissions = $this->ensureDashboardPermission($permissions);
        }

        if (!$canAccessAdminPanel && $permissions !== ['*']) {
            $permissions = [];
        }

        $role->update([
            'name' => trim($validated['name']),
            'key' => $newKey,
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'can_access_admin_panel' => $canAccessAdminPanel,
            'is_active' => $isActive,
            'permissions' => $permissions,
        ]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->withTrashed()->exists()) {
            return back()->with('error', 'This role is assigned to users. Reassign users before deleting it.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    private function makeRoleKey(string $source): string
    {
        $normalized = Str::of($source)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($normalized === '') {
            $normalized = 'role_' . Str::lower(Str::random(6));
        }

        return $normalized;
    }

    private function ensureDashboardPermission(array $permissions): array
    {
        if (!in_array('dashboard.view', $permissions, true)) {
            $permissions[] = 'dashboard.view';
        }

        return array_values(array_unique($permissions));
    }
}
