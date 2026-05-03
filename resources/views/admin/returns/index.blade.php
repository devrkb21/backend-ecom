@extends('admin.layouts.app')

@section('title', 'Returns & Refunds')

@section('content')
<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Review</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Approved</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['approved'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Processing Refunds</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['processing'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sync-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Refunds Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">৳{{ number_format($stats['total_refunds_today'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Returns</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.returns.index') }}" method="GET" class="row g-3" data-realtime-filter="1">
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Return #, Order #, Customer" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="return" {{ request('type') == 'return' ? 'selected' : '' }}>Return</option>
                        <option value="refund" {{ request('type') == 'refund' ? 'selected' : '' }}>Refund Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.returns.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Returns & Refunds</h6>
            <a href="{{ route('admin.returns.export', request()->query()) }}" class="btn btn-sm btn-success" data-no-admin-ajax="1">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Return #</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Refund Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                        <tr>
                            <td>
                                <a href="{{ route('admin.returns.show', $return) }}" class="font-weight-bold">
                                    {{ $return->return_number }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $return->order_id) }}">
                                    {{ $return->order->order_number ?? 'N/A' }}
                                </a>
                            </td>
                            <td>
                                {{ $return->user->name ?? 'Guest' }}
                                <br>
                                <small class="text-muted">{{ $return->user->email ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $return->type == 'return' ? 'primary' : 'info' }}">
                                    {{ ucfirst($return->type) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($return->reason_label, 30) }}</td>
                            <td>৳{{ number_format($return->total_amount, 2) }}</td>
                            <td>
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
                                <span class="badge badge-{{ $statusColors[$return->status] ?? 'secondary' }}">
                                    {{ ucfirst($return->status) }}
                                </span>
                            </td>
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
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $return->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.returns.show', $return) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($return->isPending())
                                    <button type="button" class="btn btn-sm btn-success" onclick="showApproveModal({{ $return->id }}, {{ $return->total_amount }})" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal({{ $return->id }})" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No return requests found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                @include('admin.partials.pagination', ['paginator' => $returns])
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="approveForm" method="POST">
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
                        <input type="text" class="form-control" id="approve_total_amount" readonly>
                    </div>
                    <div class="form-group">
                        <label>Restocking Fee (optional)</label>
                        <input type="number" name="restocking_fee" class="form-control" min="0" step="0.01" value="0">
                        <small class="text-muted">Deducted from refund amount</small>
                    </div>
                    <div class="form-group">
                        <label>Final Refund Amount</label>
                        <input type="number" name="refund_amount" class="form-control" id="approve_refund_amount" required min="0" step="0.01">
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
            <form id="rejectForm" method="POST">
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
@endsection

@push('scripts')
<script>
function showApproveModal(returnId, totalAmount) {
    document.getElementById('approveForm').action = '/admin/returns/' + returnId + '/approve';
    document.getElementById('approve_total_amount').value = '৳' + totalAmount.toFixed(2);
    document.getElementById('approve_refund_amount').value = totalAmount;
    document.getElementById('approve_refund_amount').max = totalAmount;
    $('#approveModal').modal('show');
}

function showRejectModal(returnId) {
    document.getElementById('rejectForm').action = '/admin/returns/' + returnId + '/reject';
    $('#rejectModal').modal('show');
}

// Update refund amount when restocking fee changes
document.querySelector('input[name="restocking_fee"]')?.addEventListener('input', function() {
    const total = parseFloat(document.getElementById('approve_total_amount').value.replace('৳', '').replace(',', '')) || 0;
    const fee = parseFloat(this.value) || 0;
    document.getElementById('approve_refund_amount').value = Math.max(0, total - fee).toFixed(2);
});
</script>
@endpush
