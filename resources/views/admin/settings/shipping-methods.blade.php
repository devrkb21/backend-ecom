@extends('admin.layouts.app')

@section('title', 'Shipping Methods')
@section('page-title', 'Shipping Methods')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-truck me-2"></i>Shipping Methods</h6>
        <a href="{{ route('admin.settings.shipping-methods.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Method
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Method</th>
                        <th>Base Cost</th>
                        <th>Free Shipping</th>
                        <th>Delivery</th>
                        <th class="text-center">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-methods">
                    @forelse($methods as $method)
                        <tr data-id="{{ $method->id }}">
                            <td class="text-center">
                                <i class="bi bi-grip-vertical text-muted drag-handle" style="cursor: grab;"></i>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $method->name }}</div>
                                <small class="text-muted">{{ $method->code }}</small>
                            </td>
                            <td>
                                @if($method->base_cost > 0)
                                    ৳{{ number_format($method->base_cost, 2) }}
                                    @if($method->cost_per_item > 0)
                                        <br><small class="text-muted">+ ৳{{ number_format($method->cost_per_item, 2) }}/item</small>
                                    @endif
                                    @if($method->cost_per_kg > 0)
                                        <br><small class="text-muted">+ ৳{{ number_format($method->cost_per_kg, 2) }}/kg</small>
                                    @endif
                                @else
                                    <span class="text-success fw-semibold">FREE</span>
                                @endif
                            </td>
                            <td>
                                @if($method->free_shipping_threshold)
                                    <span class="text-success">Over ৳{{ number_format($method->free_shipping_threshold, 2) }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($method->getDeliveryEstimate())
                                    {{ $method->getDeliveryEstimate() }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.settings.shipping-methods.toggle', $method) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $method->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                        <i class="bi bi-{{ $method->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                        {{ $method->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="{{ route('admin.settings.shipping-methods.edit', $method) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.settings.shipping-methods.destroy', $method) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shipping method?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2"></i>
                                No shipping methods found.
                                <a href="{{ route('admin.settings.shipping-methods.create') }}">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>About Shipping Methods</h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">Configure shipping options for your store:</p>
                <ul class="text-muted small mb-0">
                    <li><strong>Base Cost</strong> - Fixed shipping fee</li>
                    <li><strong>Per Item Cost</strong> - Additional cost per item in cart</li>
                    <li><strong>Per KG Cost</strong> - Additional cost based on weight</li>
                    <li><strong>Free Shipping Threshold</strong> - Orders above this amount ship free</li>
                    <li><strong>Country Restrictions</strong> - Limit availability by country</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-lightbulb me-2"></i>Tips</h6>
            </div>
            <div class="card-body">
                <ul class="text-muted small mb-0">
                    <li>Drag and drop to reorder shipping methods</li>
                    <li>The order affects how options are displayed to customers</li>
                    <li>Inactive methods won't be shown during checkout</li>
                    <li>Use country restrictions for region-specific shipping</li>
                    <li>Consider offering a free shipping threshold to increase average order value</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sortable = new Sortable(document.getElementById('sortable-methods'), {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {
                const order = Array.from(document.querySelectorAll('#sortable-methods tr[data-id]'))
                    .map(row => row.dataset.id);
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.settings.shipping-methods.order") }}';
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    ${order.map((id, i) => `<input type="hidden" name="order[${i}]" value="${id}">`).join('')}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
</script>
@endpush
