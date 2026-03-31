@extends('admin.layouts.app')

@section('title', 'Return #' . $return->return_number)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Return #{{ $return->return_number }}</h1>
            <small class="text-muted">Created {{ $return->created_at->format('M d, Y \a\t h:i A') }}</small>
        </div>
        <div>
            <a href="{{ route('admin.returns.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Return Details Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Return Details</h6>
                    @php
                        $statusColors = [
                            'pending' => 'warning',
                            'approved' => 'info',
                            'rejected' => 'danger',
                            'processing' => 'primary',
                            'received' => 'secondary',
                            'completed' => 'success',
                            'cancelled' => 'dark',
                        ];
                    @endphp
                    <span class="badge badge-{{ $statusColors[$return->status] ?? 'secondary' }} badge-lg" style="font-size: 1rem;">
                        {{ $return->status_label }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Return Number:</th>
                                    <td>{{ $return->return_number }}</td>
                                </tr>
                                <tr>
                                    <th>Order Number:</th>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $return->order_id) }}">
                                            {{ $return->order->order_number }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        <span class="badge badge-{{ $return->type == 'return' ? 'primary' : 'info' }}">
                                            {{ ucfirst($return->type) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Reason:</th>
                                    <td>{{ $return->reason_label }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Payment Method:</th>
                                    <td>{{ ucfirst($return->getPaymentMethod()) }}</td>
                                </tr>
                                <tr>
                                    <th>Refund Method:</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $return->refund_method)) }}</td>
                                </tr>
                                <tr>
                                    <th>Refund Status:</th>
                                    <td>
                                        @if($return->refund_status)
                                            @php
                                                $refundColors = [
                                                    'pending' => 'warning',
                                                    'processing' => 'primary',
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                ];
                                            @endphp
                                            <span class="badge badge-{{ $refundColors[$return->refund_status] ?? 'secondary' }}">
                                                {{ ucfirst($return->refund_status) }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not started</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($return->refund_transaction_id)
                                <tr>
                                    <th>Refund Transaction:</th>
                                    <td><code>{{ $return->refund_transaction_id }}</code></td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <hr>

                    <h6 class="font-weight-bold">Customer Description</h6>
                    <p class="mb-0 text-gray-700">{{ $return->description }}</p>
                </div>
            </div>

            <!-- Return Items -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Return Items</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                                <th>Condition</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($return->items as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($item->product && $item->product->thumbnail)
                                        <img src="{{ Storage::url($item->product->thumbnail) }}" alt="" class="rounded" style="width: 50px; height: 50px; object-fit: cover;" class="mr-3">
                                        @endif
                                        <div class="ml-2">
                                            <strong>{{ $item->product->name ?? 'Product Deleted' }}</strong>
                                            @if($item->reason)
                                            <br><small class="text-muted">Reason: {{ $item->reason }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>৳{{ number_format($item->unit_price, 2) }}</td>
                                <td>৳{{ number_format($item->subtotal, 2) }}</td>
                                <td>
                                    @if($item->condition)
                                    <span class="badge badge-{{ $item->condition == 'good' ? 'success' : 'warning' }}">
                                        {{ ucfirst(str_replace('_', ' ', $item->condition)) }}
                                    </span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="3" class="text-right font-weight-bold">Total Amount:</td>
                                <td colspan="2" class="font-weight-bold">৳{{ number_format($return->total_amount, 2) }}</td>
                            </tr>
                            @if($return->restocking_fee > 0)
                            <tr>
                                <td colspan="3" class="text-right">Restocking Fee:</td>
                                <td colspan="2" class="text-danger">- ৳{{ number_format($return->restocking_fee, 2) }}</td>
                            </tr>
                            @endif
                            @if($return->final_refund_amount)
                            <tr class="table-success">
                                <td colspan="3" class="text-right font-weight-bold">Final Refund Amount:</td>
                                <td colspan="2" class="font-weight-bold text-success">৳{{ number_format($return->final_refund_amount, 2) }}</td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Images -->
            @if($return->images && count($return->images) > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Uploaded Images</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($return->images as $image)
                        <div class="col-md-3 mb-3">
                            <a href="{{ Storage::url($image) }}" target="_blank">
                                <img src="{{ Storage::url($image) }}" alt="Return Image" class="img-fluid rounded shadow-sm" style="max-height: 150px; object-fit: cover; width: 100%;">
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Admin Notes -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Admin Notes</h6>
                </div>
                <div class="card-body">
                    @if($return->admin_notes)
                    <div class="mb-3 p-3 bg-light rounded">
                        {!! nl2br(e($return->admin_notes)) !!}
                    </div>
                    @else
                    <p class="text-muted mb-3">No notes yet.</p>
                    @endif

                    <form action="{{ route('admin.returns.add-notes', $return) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add a note..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-plus"></i> Add Note
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Customer Info -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $return->user->name ?? 'Guest' }}</strong></p>
                    <p class="mb-1">{{ $return->user->email ?? 'N/A' }}</p>
                    <p class="mb-0">{{ $return->user->phone ?? '' }}</p>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Original Order</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Order #:</th>
                            <td>
                                <a href="{{ route('admin.orders.show', $return->order_id) }}">
                                    {{ $return->order->order_number }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Order Total:</th>
                            <td>৳{{ number_format($return->order->total, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Payment Method:</th>
                            <td>{{ ucfirst($return->order->payment_method) }}</td>
                        </tr>
                        <tr>
                            <th>Transaction ID:</th>
                            <td><small><code>{{ $return->getOriginalTransactionId() ?: 'N/A' }}</code></small></td>
                        </tr>
                        <tr>
                            <th>Order Date:</th>
                            <td>{{ $return->order->created_at->format('M d, Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                </div>
                <div class="card-body">
                    @if($return->isPending())
                    <button type="button" class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#approveModal">
                        <i class="fas fa-check"></i> Approve Return
                    </button>
                    <button type="button" class="btn btn-danger btn-block mb-2" data-toggle="modal" data-target="#rejectModal">
                        <i class="fas fa-times"></i> Reject Return
                    </button>
                    @elseif($return->isApproved() || $return->isProcessing())
                        @if($return->type == 'return' && !$return->isReceived())
                        <button type="button" class="btn btn-info btn-block mb-2" data-toggle="modal" data-target="#receivedModal">
                            <i class="fas fa-box"></i> Mark Items Received
                        </button>
                        @endif

                        @if($return->canProcessRefund())
                        <button type="button" class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#refundModal">
                            <i class="fas fa-money-bill-wave"></i> Process Refund
                        </button>
                        @endif
                    @endif

                    <!-- Update Refund Method -->
                    @if(!$return->isCompleted() && !$return->isRejected())
                    <hr>
                    <form action="{{ route('admin.returns.update-refund-method', $return) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="small">Refund Method</label>
                            <select name="refund_method" class="form-control form-control-sm">
                                <option value="original" {{ $return->refund_method == 'original' ? 'selected' : '' }}>Original Payment Method</option>
                                <option value="store_credit" {{ $return->refund_method == 'store_credit' ? 'selected' : '' }}>Store Credit</option>
                                <option value="bank_transfer" {{ $return->refund_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm btn-block">
                            Update Refund Method
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Timeline -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Timeline</h6>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li class="timeline-item {{ $return->status ? 'completed' : '' }}">
                            <span class="timeline-point bg-primary"></span>
                            <div class="timeline-content">
                                <strong>Request Submitted</strong>
                                <small class="text-muted d-block">{{ $return->created_at->format('M d, Y h:i A') }}</small>
                            </div>
                        </li>
                        @if($return->processed_at)
                        <li class="timeline-item completed">
                            <span class="timeline-point bg-{{ $return->isRejected() ? 'danger' : 'info' }}"></span>
                            <div class="timeline-content">
                                <strong>{{ $return->isRejected() ? 'Rejected' : 'Approved' }}</strong>
                                @if($return->processedBy)
                                <small class="text-muted d-block">By {{ $return->processedBy->name }}</small>
                                @endif
                                <small class="text-muted d-block">{{ $return->processed_at->format('M d, Y h:i A') }}</small>
                            </div>
                        </li>
                        @endif
                        @if($return->received_at)
                        <li class="timeline-item completed">
                            <span class="timeline-point bg-secondary"></span>
                            <div class="timeline-content">
                                <strong>Items Received</strong>
                                <small class="text-muted d-block">{{ $return->received_at->format('M d, Y h:i A') }}</small>
                            </div>
                        </li>
                        @endif
                        @if($return->refunded_at)
                        <li class="timeline-item completed">
                            <span class="timeline-point bg-success"></span>
                            <div class="timeline-content">
                                <strong>Refund Completed</strong>
                                <small class="text-muted d-block">৳{{ number_format($return->final_refund_amount, 2) }}</small>
                                <small class="text-muted d-block">{{ $return->refunded_at->format('M d, Y h:i A') }}</small>
                            </div>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.returns.approve', $return) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Return Request</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Total Return Amount</label>
                        <input type="text" class="form-control" value="৳{{ number_format($return->total_amount, 2) }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Restocking Fee (optional)</label>
                        <input type="number" name="restocking_fee" class="form-control" min="0" step="0.01" value="0" id="restocking_fee">
                        <small class="text-muted">Deducted from refund amount</small>
                    </div>
                    <div class="form-group">
                        <label>Final Refund Amount</label>
                        <input type="number" name="refund_amount" class="form-control" required min="0" max="{{ $return->total_amount }}" step="0.01" value="{{ $return->total_amount }}" id="refund_amount">
                    </div>
                    <div class="form-group">
                        <label>Admin Notes (optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Internal notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.returns.reject', $return) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Return Request</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please explain why this return is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mark Received Modal -->
<div class="modal fade" id="receivedModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.returns.mark-received', $return) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Mark Items as Received</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Return Tracking Number (optional)</label>
                        <input type="text" name="return_tracking_number" class="form-control" value="{{ $return->return_tracking_number }}" placeholder="Customer's return shipping tracking number">
                    </div>
                    <div class="form-group">
                        <label>Received Condition <span class="text-danger">*</span></label>
                        <select name="condition" class="form-control" required>
                            <option value="good">Good - As Described</option>
                            <option value="damaged">Damaged</option>
                            <option value="missing_parts">Missing Parts</option>
                            <option value="wrong_item">Wrong Item Returned</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Condition Notes (optional)</label>
                        <textarea name="condition_notes" class="form-control" rows="3" placeholder="Notes about the condition of returned items..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Mark as Received</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Process Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.returns.process-refund', $return) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Process Refund</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Refund Amount:</strong> ৳{{ number_format($return->final_refund_amount, 2) }}<br>
                        <strong>Method:</strong> {{ ucfirst(str_replace('_', ' ', $return->refund_method)) }}<br>
                        <strong>Payment Gateway:</strong> {{ ucfirst($return->getPaymentMethod()) }}
                    </div>

                    @if($return->isEligibleForAutoRefund())
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> This refund is eligible for <strong>automatic processing</strong> via {{ ucfirst($return->getPaymentMethod()) }}.
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> This refund requires <strong>manual processing</strong>.
                    </div>
                    <div class="form-group">
                        <label>Refund Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" name="refund_transaction_id" class="form-control" required placeholder="Enter the manual refund transaction ID">
                    </div>
                    <div class="form-group">
                        <label>Refund Notes (optional)</label>
                        <textarea name="refund_notes" class="form-control" rows="3" placeholder="Notes about the manual refund..."></textarea>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-money-bill-wave"></i> Process Refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
}
.timeline-item {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    border-left: 2px solid #e3e6f0;
}
.timeline-item:last-child {
    padding-bottom: 0;
    border-left-color: transparent;
}
.timeline-point {
    position: absolute;
    left: -8px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}
.timeline-content {
    padding-top: 0;
}
.timeline-content strong {
    font-size: 0.9rem;
}
</style>
@endsection

@section('scripts')
<script>
document.getElementById('restocking_fee')?.addEventListener('input', function() {
    const total = {{ $return->total_amount }};
    const fee = parseFloat(this.value) || 0;
    document.getElementById('refund_amount').value = Math.max(0, total - fee).toFixed(2);
});
</script>
@endsection
