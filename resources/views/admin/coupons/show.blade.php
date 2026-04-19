@extends('admin.layouts.app')

@section('title', 'Coupon Details')
@section('page-title', 'Coupon Details')

@section('content')
<div class="row">
    <div class="col-lg-8">
        {{-- Coupon Info --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <code class="fs-5 bg-light px-2 py-1 rounded me-2">{{ $coupon->code }}</code>
                    <span class="badge bg-{{ ['active' => 'success', 'inactive' => 'secondary', 'expired' => 'danger', 'scheduled' => 'info', 'exhausted' => 'warning'][$coupon->status] ?? 'secondary' }}">
                        {{ ucfirst($coupon->status) }}
                    </span>
                </h5>
                <div>
                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Coupon Name</h6>
                        <p class="fw-semibold">{{ $coupon->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Discount</h6>
                        <p class="fw-semibold">
                            <span class="badge bg-{{ $coupon->type == 'percentage' ? 'warning' : 'success' }} text-dark fs-6">
                                {{ $coupon->formatted_value }}
                            </span>
                            @if($coupon->type == 'percentage' && $coupon->maximum_discount)
                                <span class="text-muted">(Max: ৳{{ number_format($coupon->maximum_discount) }})</span>
                            @endif
                        </p>
                    </div>
                    @if($coupon->description)
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p>{{ $coupon->description }}</p>
                        </div>
                    @endif
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Minimum Order</h6>
                        <p>{{ $coupon->minimum_order_amount ? '৳' . number_format($coupon->minimum_order_amount) : 'No minimum' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Usage Limit</h6>
                        <p>{{ $coupon->usage_limit ?? 'Unlimited' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Per Customer Limit</h6>
                        <p>{{ $coupon->usage_limit_per_user ?? 'Unlimited' }}</p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Start Date</h6>
                        <p>{{ $coupon->starts_at ? $coupon->starts_at->format('M d, Y H:i') : 'Immediate' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Expiry Date</h6>
                        <p>{{ $coupon->expires_at ? $coupon->expires_at->format('M d, Y H:i') : 'Never' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Free Shipping</h6>
                        <p>
                            @if($coupon->free_shipping)
                                <span class="badge bg-info"><i class="bi bi-truck me-1"></i>Yes</span>
                            @else
                                <span class="text-muted">No</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted mb-2">Guest Checkout</h6>
                        <p>
                            @if($coupon->allow_guest_checkout)
                                <span class="badge bg-success"><i class="bi bi-person me-1"></i>Allowed</span>
                            @else
                                <span class="text-muted">Login required</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($coupon->applicable_categories || $coupon->applicable_products || $coupon->excluded_products)
                    <hr>
                    <h6 class="text-muted mb-3">Product Restrictions</h6>
                    <div class="row">
                        @if($coupon->applicable_categories)
                            <div class="col-md-4">
                                <h6 class="small text-muted">Applicable Categories</h6>
                                @foreach(\App\Models\Category::whereIn('id', $coupon->applicable_categories)->get() as $cat)
                                    <span class="badge bg-light text-dark me-1 mb-1">{{ $cat->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($coupon->applicable_products)
                            <div class="col-md-4">
                                <h6 class="small text-muted">Applicable Products</h6>
                                @foreach(\App\Models\Product::whereIn('id', $coupon->applicable_products)->get() as $prod)
                                    <span class="badge bg-light text-dark me-1 mb-1">{{ $prod->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($coupon->excluded_products)
                            <div class="col-md-4">
                                <h6 class="small text-muted">Excluded Products</h6>
                                @foreach(\App\Models\Product::whereIn('id', $coupon->excluded_products)->get() as $prod)
                                    <span class="badge bg-danger text-white me-1 mb-1">{{ $prod->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Usage History --}}
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Usage History</h6>
            </div>
            <div class="card-body">
                @if($coupon->usages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Order</th>
                                    <th>Discount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($coupon->usages()->latest()->take(20)->get() as $usage)
                                    <tr>
                                        <td>
                                            @if($usage->user)
                                                {{ $usage->user->name }}
                                                <br><small class="text-muted">{{ $usage->user->email }}</small>
                                            @else
                                                <span class="text-muted">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($usage->order)
                                                <a href="{{ route('admin.orders.show', $usage->order) }}" class="text-decoration-none">
                                                    #{{ $usage->order->order_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>৳{{ number_format($usage->discount_amount, 2) }}</td>
                                        <td>{{ $usage->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($coupon->usages->count() > 20)
                        <div class="text-center text-muted small mt-2">
                            Showing 20 of {{ $coupon->usages->count() }} records
                        </div>
                    @endif
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mb-0 mt-2">No usage records yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Statistics --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Statistics</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="fs-1 fw-bold text-primary">{{ $coupon->used_count }}</div>
                    <div class="text-muted">Times Used</div>
                    @if($coupon->usage_limit)
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: {{ min(100, ($coupon->used_count / $coupon->usage_limit) * 100) }}%"></div>
                        </div>
                        <small class="text-muted">{{ $coupon->usage_limit - $coupon->used_count }} remaining</small>
                    @endif
                </div>

                <div class="row text-center">
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <div class="fs-5 fw-bold text-success">৳{{ number_format($coupon->usages->sum('discount_amount'), 2) }}</div>
                            <div class="text-muted small">Total Discounts</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <div class="fs-5 fw-bold text-info">{{ $coupon->usages->unique('user_id')->count() }}</div>
                            <div class="text-muted small">Unique Users</div>
                        </div>
                    </div>
                </div>

                @if($coupon->usages->count() > 0)
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            Avg. discount: ৳{{ number_format($coupon->usages->avg('discount_amount'), 2) }}
                        </small>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form action="{{ route('admin.coupons.toggle-status', $coupon) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-{{ $coupon->is_active ? 'warning' : 'success' }} w-100">
                            <i class="bi bi-{{ $coupon->is_active ? 'pause' : 'play' }} me-1"></i>
                            {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <form action="{{ route('admin.coupons.duplicate', $coupon) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-copy me-1"></i> Duplicate Coupon
                        </button>
                    </form>
                    <hr>
                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" 
                          onsubmit="return confirm('Are you sure you want to delete this coupon?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-1"></i> Delete Coupon
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
