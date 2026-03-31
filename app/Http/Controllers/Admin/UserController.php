<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['orders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.users.show', compact('user'));
    }
}
