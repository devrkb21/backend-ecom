@extends('admin.layouts.app')

@section('title', 'Payment Gateways')
@section('page-title', 'Payment Gateways')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card me-2"></i>Payment Gateways</h6>
        <span class="badge bg-secondary">{{ $gateways->where('is_active', true)->count() }} Active</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="gateways-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Gateway</th>
                        <th>Description</th>
                        <th class="text-center">Limits</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-gateways">
                    @foreach($gateways as $gateway)
                        <tr data-id="{{ $gateway->id }}">
                            <td class="text-center text-muted cursor-move">
                                <i class="bi bi-grip-vertical"></i>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="gateway-icon me-3 fs-4">
                                        {!! $gateway->getIconHtml() !!}
                                    </div>
                                    <div>
                                        <strong>{{ $gateway->name }}</strong>
                                        <div class="small text-muted">{{ $gateway->code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ Str::limit($gateway->description, 50) }}</span>
                            </td>
                            <td class="text-center">
                                @if($gateway->min_amount || $gateway->max_amount)
                                    <small class="text-muted">
                                        @if($gateway->min_amount)
                                            Min: ৳{{ number_format($gateway->min_amount, 0) }}
                                        @endif
                                        @if($gateway->min_amount && $gateway->max_amount)
                                            |
                                        @endif
                                        @if($gateway->max_amount)
                                            Max: ৳{{ number_format($gateway->max_amount, 0) }}
                                        @endif
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.settings.payment-gateways.toggle', $gateway) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $gateway->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                            title="{{ $gateway->is_active ? 'Click to disable' : 'Click to enable' }}">
                                        @if($gateway->is_active)
                                            <i class="bi bi-check-circle me-1"></i> Active
                                        @else
                                            <i class="bi bi-circle me-1"></i> Inactive
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.settings.payment-gateways.edit', $gateway) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-gear"></i> Configure
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Gateway Info Cards -->
<div class="row g-3 mt-3">
    @foreach($gateways as $gateway)
        <div class="col-md-4">
            <div class="card h-100 {{ $gateway->is_active ? 'border-success' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="fs-2 me-3">{!! $gateway->getIconHtml() !!}</div>
                        <div>
                            <h6 class="mb-0 fw-semibold">{{ $gateway->name }}</h6>
                            @if($gateway->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-muted small mb-3">{{ $gateway->description }}</p>

                    <div class="small mb-1">
                        <i class="bi bi-calculator text-info me-1"></i>
                        @if($gateway->getSetting('extra_charge', 0) > 0)
                            Gateway charge:
                            @if($gateway->getSetting('extra_charge_type', 'fixed') === 'percentage')
                                {{ $gateway->getSetting('extra_charge') }}%
                            @else
                                ৳{{ number_format((float) $gateway->getSetting('extra_charge'), 2) }}
                            @endif
                        @else
                            No gateway charge
                        @endif
                    </div>

                    @if($gateway->code === 'stripe')
                        <div class="small">
                            <i class="bi bi-{{ $gateway->getSetting('mode') === 'live' ? 'check-circle text-success' : 'exclamation-circle text-warning' }} me-1"></i>
                            Mode: <strong>{{ ucfirst($gateway->getSetting('mode', 'test')) }}</strong>
                        </div>
                        @if(empty($gateway->getSetting('public_key')))
                            <div class="small text-danger mt-1">
                                <i class="bi bi-exclamation-triangle me-1"></i> Not configured
                            </div>
                        @endif
                    @elseif($gateway->code === 'bkash')
                        <div class="small">
                            <i class="bi bi-{{ $gateway->getSetting('mode') === 'live' ? 'check-circle text-success' : 'exclamation-circle text-warning' }} me-1"></i>
                            Mode: <strong>{{ ucfirst($gateway->getSetting('mode', 'sandbox')) }}</strong>
                        </div>
                        @if(empty($gateway->getSetting('app_key')))
                            <div class="small text-danger mt-1">
                                <i class="bi bi-exclamation-triangle me-1"></i> Not configured
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('styles')
<style>
    .cursor-move { cursor: move; }
    .gateway-icon { width: 40px; text-align: center; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sortable = new Sortable(document.getElementById('sortable-gateways'), {
            animation: 150,
            handle: '.cursor-move',
            onEnd: function() {
                const rows = document.querySelectorAll('#sortable-gateways tr');
                const gateways = [];
                
                rows.forEach((row, index) => {
                    gateways.push({
                        id: row.dataset.id,
                        sort_order: index + 1
                    });
                });

                fetch('{{ route("admin.settings.payment-gateways.order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ gateways })
                });
            }
        });
    });
</script>
@endpush
