<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $payments = Payment::with('order.user')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load('order.user', 'order.items.product');

        return view('admin.payments.show', compact('payment'));
    }
}
