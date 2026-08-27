<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\RefundService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(
        protected RefundService $refundService
    ) {}

    /**
     * Display a listing of return requests
     */
    public function index(Request $request)
    {
        $query = ReturnRequest::with(['user', 'order'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type (return/refund)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by refund status
        if ($request->filled('refund_status')) {
            $query->where('refund_status', $request->refund_status);
        }

        // Search by return number, order number, or customer
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($q) use ($search) {
                        $q->where('order_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true) ? (int) $request->input('per_page') : 20;
        $returns = $query->paginate($perPage)->withQueryString();

        // Statistics
        $stats = [
            'pending' => ReturnRequest::pending()->count(),
            'approved' => ReturnRequest::where('status', 'approved')->count(),
            'processing' => ReturnRequest::where('refund_status', 'processing')->count(),
            'completed' => ReturnRequest::where('status', 'completed')->count(),
            'total_refunds_today' => ReturnRequest::where('status', 'completed')
                ->whereDate('refunded_at', today())
                ->sum('final_refund_amount'),
        ];

        return view('admin.returns.index', compact('returns', 'stats'));
    }

    /**
     * Display the specified return request
     */
    public function show(ReturnRequest $return)
    {
        $return->load([
            'user',
            'order.items.product',
            'order.payment',
            'items.product',
            'items.orderItem',
            'processedBy',
        ]);

        return view('admin.returns.show', compact('return'));
    }

    /**
     * Approve a return request
     */
    public function approve(Request $request, ReturnRequest $return)
    {
        if (! $return->isPending()) {
            return back()->with('error', 'This return request has already been processed.');
        }

        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'restocking_fee' => 'nullable|numeric|min:0',
            'refund_amount' => 'required|numeric|min:0|max:'.$return->total_amount,
        ]);

        $return->approve([
            'admin_notes' => $validated['admin_notes'] ?? null,
            'restocking_fee' => $validated['restocking_fee'] ?? 0,
            'final_refund_amount' => $validated['refund_amount'],
            'processed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Return request approved successfully.');
    }

    /**
     * Reject a return request
     */
    public function reject(Request $request, ReturnRequest $return)
    {
        if (! $return->isPending()) {
            return back()->with('error', 'This return request has already been processed.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $return->reject([
            'admin_notes' => $validated['rejection_reason'],
            'processed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Return request rejected.');
    }

    /**
     * Mark items as received
     */
    public function markReceived(Request $request, ReturnRequest $return)
    {
        if (! $return->isApproved() && ! $return->isProcessing()) {
            return back()->with('error', 'Return must be approved before marking as received.');
        }

        $validated = $request->validate([
            'return_tracking_number' => 'nullable|string|max:255',
            'condition_notes' => 'nullable|string|max:1000',
            'condition' => 'required|in:good,damaged,missing_parts,wrong_item',
        ]);

        // Update item conditions if needed
        if ($validated['condition'] !== 'good') {
            $return->update([
                'admin_notes' => ($return->admin_notes ? $return->admin_notes."\n\n" : '')
                    ."Received Condition: {$validated['condition']}\n"
                    .($validated['condition_notes'] ?? ''),
            ]);
        }

        if ($validated['return_tracking_number']) {
            $return->update([
                'return_tracking_number' => $validated['return_tracking_number'],
            ]);
        }

        $return->markAsReceived();

        return back()->with('success', 'Items marked as received. You can now process the refund.');
    }

    /**
     * Process refund for approved return
     */
    public function processRefund(Request $request, ReturnRequest $return)
    {
        if (! $return->canProcessRefund()) {
            return back()->with('error', 'This return is not eligible for refund processing.');
        }

        // Check if it's eligible for auto-refund
        if ($return->isEligibleForAutoRefund()) {
            $result = $this->refundService->processRefund($return);

            if ($result['success']) {
                $message = 'Refund processed successfully.';
                if (isset($result['requires_manual_action']) && $result['requires_manual_action']) {
                    $message .= ' Note: This requires manual bank transfer.';
                }

                return back()->with('success', $message);
            }

            return back()->with('error', 'Refund processing failed: '.$result['message']);
        }

        // Manual refund processing
        $validated = $request->validate([
            'refund_transaction_id' => 'required|string|max:255',
            'refund_notes' => 'nullable|string|max:1000',
        ]);

        $return->markRefundCompleted($validated['refund_transaction_id']);

        if ($validated['refund_notes']) {
            $return->update([
                'admin_notes' => ($return->admin_notes ? $return->admin_notes."\n\n" : '')
                    ."Refund Notes: {$validated['refund_notes']}",
            ]);
        }

        // Update order payment status
        $return->order->update(['payment_status' => 'refunded']);

        return back()->with('success', 'Refund marked as completed.');
    }

    /**
     * Update refund method
     */
    public function updateRefundMethod(Request $request, ReturnRequest $return)
    {
        $validated = $request->validate([
            'refund_method' => 'required|in:original,store_credit,bank_transfer',
        ]);

        $return->update([
            'refund_method' => $validated['refund_method'],
        ]);

        return back()->with('success', 'Refund method updated.');
    }

    /**
     * Add admin notes
     */
    public function addNotes(Request $request, ReturnRequest $return)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $existingNotes = $return->admin_notes;
        $newNote = '['.now()->format('Y-m-d H:i').' - '.auth()->user()->name."]\n".$validated['notes'];

        $return->update([
            'admin_notes' => $existingNotes ? $existingNotes."\n\n".$newNote : $newNote,
        ]);

        return back()->with('success', 'Notes added successfully.');
    }

    /**
     * Export returns to CSV
     */
    public function export(Request $request)
    {
        $query = ReturnRequest::with(['user', 'order']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $returns = $query->get();

        $filename = 'returns_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($returns) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Return #',
                'Order #',
                'Customer',
                'Email',
                'Type',
                'Status',
                'Reason',
                'Total Amount',
                'Refund Amount',
                'Refund Status',
                'Refund Method',
                'Created At',
                'Completed At',
            ]);

            // Data rows
            foreach ($returns as $return) {
                fputcsv($file, [
                    $return->return_number,
                    $return->order->order_number ?? 'N/A',
                    $return->user->name ?? 'Guest',
                    $return->user->email ?? 'N/A',
                    ucfirst($return->type),
                    ucfirst($return->status),
                    $return->reason_label,
                    $return->total_amount,
                    $return->final_refund_amount,
                    ucfirst($return->refund_status ?? 'N/A'),
                    ucfirst($return->refund_method),
                    $return->created_at->format('Y-m-d H:i:s'),
                    $return->refunded_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
