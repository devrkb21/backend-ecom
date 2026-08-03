@extends('admin.layouts.app')

@section('title', 'Fraud Blocker')
@section('page-title', 'Fraud Blocker')

@section('content')
<style>
    /* Accordion chevron rotation transitions */
    .card-header.cursor-pointer[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }
    .card-header.cursor-pointer .bi-chevron-down {
        transition: transform 0.2s ease-in-out;
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 mb-2" style="width: 48px; height: 48px;">
                    <i class="bi bi-telephone-x text-danger fs-5"></i>
                </div>
                <h3 class="mb-0 fw-bold">{{ $summary['phone'] }}</h3>
                <small class="text-muted">Blocked Phones</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 mb-2" style="width: 48px; height: 48px;">
                    <i class="bi bi-envelope-x text-warning fs-5"></i>
                </div>
                <h3 class="mb-0 fw-bold">{{ $summary['email'] }}</h3>
                <small class="text-muted">Blocked Emails</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 mb-2" style="width: 48px; height: 48px;">
                    <i class="bi bi-globe2 text-info fs-5"></i>
                </div>
                <h3 class="mb-0 fw-bold">{{ $summary['ip'] }}</h3>
                <small class="text-muted">Blocked IPs</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 mb-2" style="width: 48px; height: 48px;">
                    <i class="bi bi-laptop text-secondary fs-5"></i>
                </div>
                <h3 class="mb-0 fw-bold">{{ $summary['device'] }}</h3>
                <small class="text-muted">Blocked Devices</small>
            </div>
        </div>
    </div>
</div>

@if($needsReviewCount > 0)
<div class="alert alert-warning d-flex align-items-center justify-content-between shadow-sm mb-4" role="alert">
    <div>
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>{{ $needsReviewCount }}</strong> {{ Str::plural('customer', $needsReviewCount) }} auto-flagged as repeat offenders and awaiting your review.
    </div>
    <a href="{{ route('admin.fraud-blocks.index', ['status' => 'needs_review']) }}" class="btn btn-sm btn-warning">
        Review Now <i class="bi bi-arrow-right ms-1"></i>
    </a>
</div>
@endif

<div class="alert alert-light border d-flex align-items-center justify-content-between mb-4">
    <div>
        <i class="bi bi-signpost-split me-2"></i>
        Cross-courier delivery-history checks (search, credentials, and thresholds) now live under
        <strong>Orders → Courier Checker</strong>.
    </div>
    <a href="{{ route('admin.orders.courier-checker') }}" class="btn btn-sm btn-outline-secondary">
        Open Courier Checker <i class="bi bi-arrow-right ms-1"></i>
    </a>
</div>

{{-- Automated Fraud Detection --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-light border-0 cursor-pointer collapsed" data-bs-toggle="collapse" data-bs-target="#automationCollapse" aria-expanded="false">
        <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-robot me-2"></i>Automated Fraud Detection</span>
            <i class="bi bi-chevron-down text-muted"></i>
        </h5>
    </div>
    <div class="collapse" id="automationCollapse">
        <div class="card-body">
            <form action="{{ route('admin.fraud-blocks.automation-settings.save') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="velocity_enabled" name="velocity_enabled" value="1" {{ $automation['velocity_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="velocity_enabled">Order Velocity Limit</label>
                        </div>
                        <p class="text-muted small">Block checkout outright once the same phone, IP, or device places too many orders in a short window (order-bombing / spam protection).</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Max orders</label>
                                <input type="number" min="1" max="1000" class="form-control" name="velocity_limit_count" value="{{ $automation['velocity_limit_count'] }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Per (minutes)</label>
                                <input type="number" min="1" max="10080" class="form-control" name="velocity_limit_window_minutes" value="{{ $automation['velocity_limit_window_minutes'] }}" required>
                            </div>
                        </div>
                        <div class="form-text">Default: 5 orders per 60 minutes, checked independently per phone, per IP, and per device.</div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="repeat_offender_enabled" name="repeat_offender_enabled" value="1" {{ $automation['repeat_offender_enabled'] ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="repeat_offender_enabled">Repeat-Offender Detection</label>
                        </div>
                        <p class="text-muted small">When a phone number's cancelled + returned order count crosses this threshold, automatically act on it.</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Cancelled/returned threshold</label>
                                <input type="number" min="1" max="100" class="form-control" name="repeat_offender_threshold" value="{{ $automation['repeat_offender_threshold'] }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Action</label>
                                <select class="form-select" name="repeat_offender_action">
                                    <option value="flag" {{ $automation['repeat_offender_action'] === 'flag' ? 'selected' : '' }}>Flag for review</option>
                                    <option value="auto_block" {{ $automation['repeat_offender_action'] === 'auto_block' ? 'selected' : '' }}>Auto-block immediately</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-text">Default: flag for review after 3 cancelled/returned orders.</div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Automation Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add New Block --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-danger bg-opacity-10 border-0">
        <h5 class="card-title mb-0 text-danger">
            <i class="bi bi-shield-plus me-2"></i>Add New Block
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.fraud-blocks.store') }}" method="POST">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="block_type" class="form-label fw-semibold">Block Type</label>
                    <select class="form-select" id="block_type" name="type" required>
                        <option value="phone">📞 Phone Number</option>
                        <option value="email">📧 Email Address</option>
                        <option value="ip">🌐 IP Address</option>
                        <option value="device">💻 Device / User Agent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="block_value" class="form-label fw-semibold">Value</label>
                    <input type="text" class="form-control @error('value') is-invalid @enderror" id="block_value" name="value" placeholder="Enter phone, email, IP or device string..." required>
                    @error('value')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <label for="block_reason" class="form-label fw-semibold">Reason <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" class="form-control" id="block_reason" name="reason" placeholder="e.g. Fake orders...">
                </div>
                <div class="col-md-3">
                    <label for="block_custom_message" class="form-label fw-semibold">Custom Message <span class="text-muted fw-normal">(shown to user)</span></label>
                    <input type="text" class="form-control @error('custom_message') is-invalid @enderror" id="block_custom_message" name="custom_message" placeholder="e.g. Your account has been suspended...">
                    @error('custom_message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-shield-x me-1"></i> Block
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Default Custom Messages --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-light border-0 cursor-pointer collapsed" data-bs-toggle="collapse" data-bs-target="#defaultMessagesCollapse" aria-expanded="false">
        <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-chat-left-text me-2"></i>Default Custom Messages</span>
            <i class="bi bi-chevron-down text-muted"></i>
        </h5>
    </div>
    <div class="collapse" id="defaultMessagesCollapse">
        <div class="card-body">
            <form action="{{ route('admin.fraud-blocks.settings.save') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="bi bi-telephone text-danger me-1"></i> Default Phone Block Message</label>
                        <textarea class="form-control" name="default_phone_msg" rows="2" placeholder="Message shown when phone is blocked...">{{ $defaults['default_phone_msg'] ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="bi bi-envelope text-warning me-1"></i> Default Email Block Message</label>
                        <textarea class="form-control" name="default_email_msg" rows="2" placeholder="Message shown when email is blocked...">{{ $defaults['default_email_msg'] ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="bi bi-globe2 text-info me-1"></i> Default IP Block Message</label>
                        <textarea class="form-control" name="default_ip_msg" rows="2" placeholder="Message shown when IP is blocked...">{{ $defaults['default_ip_msg'] ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="bi bi-laptop text-secondary me-1"></i> Default Device Block Message</label>
                        <textarea class="form-control" name="default_device_msg" rows="2" placeholder="Message shown when device is blocked...">{{ $defaults['default_device_msg'] ?? '' }}</textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Defaults
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Filters & Search --}}
<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center border-0">
        <h5 class="card-title mb-0">
            <i class="bi bi-shield-lock me-2"></i>Blocked List
            <span class="badge bg-danger ms-2">{{ $blocks->total() }}</span>
        </h5>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <select name="type" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="phone" {{ request('type') === 'phone' ? 'selected' : '' }}>Phone</option>
                    <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="ip" {{ request('type') === 'ip' ? 'selected' : '' }}>IP</option>
                    <option value="device" {{ request('type') === 'device' ? 'selected' : '' }}>Device</option>
                </select>
                <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="needs_review" {{ request('status') === 'needs_review' ? 'selected' : '' }}>Needs Review ({{ $needsReviewCount }})</option>
                </select>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search...">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        @if($blocks->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-check fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-0">No blocked entries found.</p>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 120px;">Type</th>
                        <th>Value</th>
                        <th>Reason</th>
                        <th>Custom Message</th>
                        <th>Blocked By</th>
                        <th>Order</th>
                        <th style="width: 100px;">Status</th>
                        <th>Date</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blocks as $block)
                    <tr class="{{ $block->needs_review ? 'table-warning' : '' }}">
                        <td class="text-muted">{{ $block->id }}</td>
                        <td>
                            <span class="badge bg-{{ $block->type_color }} bg-opacity-10 text-{{ $block->type_color }}">
                                <i class="bi {{ $block->type_icon }} me-1"></i>{{ $block->type_label }}
                            </span>
                            @if($block->source === 'auto')
                                <span class="badge bg-dark bg-opacity-10 text-dark" title="Auto-detected by repeat-offender rule">
                                    <i class="bi bi-robot me-1"></i>Auto
                                </span>
                            @endif
                        </td>
                        <td>
                            <code class="text-dark" style="word-break: break-all;">{{ Str::limit($block->value, 60) }}</code>
                        </td>
                        <td>
                            @if($block->reason)
                                <span class="text-muted small">{{ Str::limit($block->reason, 40) }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($block->custom_message)
                                <span class="small text-info" title="{{ $block->custom_message }}">
                                    <i class="bi bi-chat-quote me-1"></i>{{ Str::limit($block->custom_message, 35) }}
                                </span>
                            @else
                                <span class="text-muted small">Default</span>
                            @endif
                        </td>
                        <td>
                            @if($block->blockedByUser)
                                <small>{{ $block->blockedByUser->name }}</small>
                            @else
                                <small class="text-muted">System</small>
                            @endif
                        </td>
                        <td>
                            @if($block->order)
                                <a href="{{ route('admin.orders.show', $block->order_id) }}" class="text-decoration-none small">
                                    #{{ $block->order->order_number }}
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($block->needs_review)
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Needs Review</span>
                            @else
                                <form action="{{ route('admin.fraud-blocks.toggle', $block) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $block->is_active ? 'btn-success' : 'btn-outline-secondary' }}" title="{{ $block->is_active ? 'Active - Click to deactivate' : 'Inactive - Click to activate' }}">
                                        <i class="bi {{ $block->is_active ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                                        {{ $block->is_active ? 'Active' : 'Off' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $block->created_at->format('d M, Y') }}</small>
                            <br><small class="text-muted">{{ $block->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            @if($block->needs_review)
                                <form action="{{ route('admin.fraud-blocks.confirm-review', $block) }}" method="POST" class="d-inline" title="Confirm block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-shield-x"></i> Block
                                    </button>
                                </form>
                                <form action="{{ route('admin.fraud-blocks.dismiss-review', $block) }}" method="POST" class="d-inline" title="Dismiss as false positive">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.fraud-blocks.destroy', $block) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this block permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    <div class="card-footer" id="blocksPaginationWrap">
        @include('admin.partials.pagination', ['paginator' => $blocks, 'perPageOptions' => [25, 50, 100]])
    </div>
</div>
@endsection
