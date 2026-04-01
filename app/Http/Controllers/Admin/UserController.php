<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withTrashed()->withCount('orders');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && array_key_exists($request->input('role'), User::roleOptions())) {
            $query->where('role', $request->input('role'));
        }

        if ($request->input('status') === 'active') {
            $query->whereNull('deleted_at');
        } elseif ($request->input('status') === 'inactive') {
            $query->whereNotNull('deleted_at');
        }

        $users = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::withTrashed()->count(),
            'active' => User::count(),
            'inactive' => User::onlyTrashed()->count(),
        ];

        $roles = User::roleOptions();
        $filters = $request->only(['search', 'status', 'role']);

        return view('admin.users.index', compact('users', 'stats', 'roles', 'filters'));
    }

    public function create()
    {
        $roles = User::roleOptions();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $roleOptions = array_keys(User::roleOptions());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'role' => ['required', 'string', 'in:' . implode(',', $roleOptions)],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => $validated['role'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $roleOptions = array_keys(User::roleOptions());

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', $roleOptions)],
        ]);

        $user = User::withTrashed()->findOrFail($id);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'User role updated successfully.');
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->trashed()) {
            $user->restore();
            $message = 'User activated successfully.';
        } else {
            $user->delete();
            $message = 'User deactivated successfully.';
        }

        return back()->with('success', $message);
    }

    public function show(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $user->load(['orders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $roleLabels = User::roleOptions(false);

        return view('admin.users.show', compact('user', 'roleLabels'));
    }
}
