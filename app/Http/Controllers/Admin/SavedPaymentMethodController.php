<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedPaymentMethod;
use Illuminate\Http\Request;

class SavedPaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = SavedPaymentMethod::query()
            ->with('user')
            ->where('gateway', 'stripe')
            ->orderByDesc('is_default')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $methods = $query->paginate($perPage)->withQueryString();

        return view('admin.payments.saved-methods', compact('methods', 'search'));
    }
}
