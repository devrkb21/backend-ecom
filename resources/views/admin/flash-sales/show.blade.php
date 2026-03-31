@extends('admin.layouts.app')

@section('title', 'Flash Sale Details')
@section('page-title', $flashSale->name)

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card {{ $flashSale->status === 'active' ? 'bg-success' : ($flashSale->status === 'scheduled' ? 'bg-warning' : 'bg-secondary') }} text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Status</h6>
                <h4 class="mb-0 text-uppercase">{{ $flashSale->status }}</h4>
                @if($flashSale->status === 'active' && $flashSale->time_remaining)
                    <small class="text-white-50">{{ $flashSale->time_remaining['days'] }}d {{ $flashSale->time_remaining['hours'] }}h {{ $flashSale->time_remaining['minutes'] }}m</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Products</h6>
                <h4 class="mb-0">{{ $flashSale->flashSaleProducts->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Total Sold</h6>
                <h4 class="mb-0">{{ number_format($flashSale->flashSaleProducts->sum('sold_count')) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h6 class="text-white-50 mb-1">Revenue</h6>
                <h4 class="mb-0">৳{{ number_format($flashSale->flashSaleProducts->sum(function($p) { return $p->sold_count * $p->flash_price; }), 0) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2"></i>Products in Sale ({{ $flashSale->flashSaleProducts->count() }})</h6>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus"></i> Add Product
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Original Price</th>
                                <th>Flash Price</th>
                                <th>Discount</th>
                                <th>Stock</th>
                                <th>Sold</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($flashSale->flashSaleProducts as $flashProduct)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $image = $flashProduct->product->images->first();
                                            @endphp
                                            @if($image)
                                                <img src="{{ $image->url }}" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            @endif
                                            <div>
                                                <a href="{{ route('admin.products.show', $flashProduct->product) }}">{{ $flashProduct->product->name }}</a>
                                                <div class="small text-muted">{{ $flashProduct->product->sku }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>৳{{ number_format($flashProduct->original_price, 2) }}</td>
                                    <td class="text-danger fw-bold">৳{{ number_format($flashProduct->flash_price, 2) }}</td>
                                    <td>
                                        <span class="badge bg-danger">
                                            {{ round((($flashProduct->original_price - $flashProduct->flash_price) / $flashProduct->original_price) * 100) }}% OFF
                                        </span>
                                    </td>
                                    <td>
                                        @if($flashProduct->quantity_limit)
                                            <span class="{{ $flashProduct->is_sold_out ? 'text-danger' : '' }}">
                                                {{ $flashProduct->stock_remaining }} / {{ $flashProduct->quantity_limit }}
                                            </span>
                                        @else
                                            <span class="text-muted">Unlimited</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">{{ $flashProduct->sold_count }}</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.flash-sales.toggle-product', [$flashSale, $flashProduct]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $flashProduct->is_active ? 'btn-success' : 'btn-secondary' }}">
                                                <i class="bi bi-{{ $flashProduct->is_active ? 'check' : 'x' }}"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $flashProduct->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.flash-sales.remove-product', [$flashSale, $flashProduct->product_id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                
                                <!-- Edit Product Modal -->
                                <div class="modal fade" id="editProductModal{{ $flashProduct->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.flash-sales.add-product', $flashSale) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $flashProduct->product_id }}">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Product: {{ $flashProduct->product->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Flash Price <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="flash_price" value="{{ $flashProduct->flash_price }}" step="0.01" min="0" required>
                                                        <small class="text-muted">Original: ৳{{ number_format($flashProduct->original_price, 2) }}</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Quantity Limit</label>
                                                        <input type="number" class="form-control" name="quantity_limit" value="{{ $flashProduct->quantity_limit }}" min="1">
                                                        <small class="text-muted">Leave empty for unlimited</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Per User Limit</label>
                                                        <input type="number" class="form-control" name="per_user_limit" value="{{ $flashProduct->per_user_limit }}" min="1">
                                                        <small class="text-muted">Leave empty for unlimited</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                        No products in this flash sale yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Sale Information</h6>
            </div>
            <div class="card-body">
                @if($flashSale->banner_image)
                    <img src="{{ $flashSale->banner_image }}" alt="Banner" class="img-fluid rounded mb-3">
                @endif
                
                <table class="table table-sm">
                    <tr>
                        <td class="text-muted">ID</td>
                        <td class="text-end">{{ $flashSale->id }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Slug</td>
                        <td class="text-end"><code>{{ $flashSale->slug }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Starts</td>
                        <td class="text-end">{{ $flashSale->starts_at->format('M d, Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Ends</td>
                        <td class="text-end">{{ $flashSale->ends_at->format('M d, Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Duration</td>
                        <td class="text-end">{{ $flashSale->starts_at->diffForHumans($flashSale->ends_at, true) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Featured</td>
                        <td class="text-end">
                            @if($flashSale->is_featured)
                                <span class="badge bg-warning"><i class="bi bi-star-fill"></i> Yes</span>
                            @else
                                <span class="text-muted">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Priority</td>
                        <td class="text-end">{{ $flashSale->priority }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Max Per User</td>
                        <td class="text-end">{{ $flashSale->max_items_per_user ?? 'Unlimited' }}</td>
                    </tr>
                </table>
                
                @if($flashSale->description)
                    <hr>
                    <p class="text-muted small mb-0">{{ $flashSale->description }}</p>
                @endif
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.flash-sales.edit', $flashSale) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Sale
                    </a>
                    
                    @if($flashSale->status === 'active')
                        <form action="{{ route('admin.flash-sales.end', $flashSale) }}" method="POST" onsubmit="return confirm('End this flash sale early?');">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-stop-circle me-2"></i>End Sale Early
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#extendModal">
                            <i class="bi bi-clock-history me-2"></i>Extend Sale
                        </button>
                    @endif
                    
                    @if($flashSale->status === 'ended')
                        <form action="{{ route('admin.flash-sales.duplicate', $flashSale) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="bi bi-files me-2"></i>Duplicate Sale
                            </button>
                        </form>
                    @endif
                    
                    <form action="{{ route('admin.flash-sales.destroy', $flashSale) }}" method="POST" onsubmit="return confirm('Delete this flash sale? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-2"></i>Delete Sale
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.flash-sales.add-product', $flashSale) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Product to Flash Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select class="form-select" name="product_id" required id="productSelect">
                            <option value="">Select Product</option>
                            @foreach($availableProducts as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->regular_price }}">
                                    {{ $product->name }} - ৳{{ number_format($product->regular_price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Flash Price <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="flash_price" step="0.01" min="0" required>
                        <small class="text-muted" id="priceHint">Select a product to see original price</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity Limit</label>
                        <input type="number" class="form-control" name="quantity_limit" min="1">
                        <small class="text-muted">Leave empty for unlimited</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Per User Limit</label>
                        <input type="number" class="form-control" name="per_user_limit" min="1">
                        <small class="text-muted">Leave empty for unlimited</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Extend Sale Modal -->
@if($flashSale->status === 'active')
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.flash-sales.extend', $flashSale) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Extend Flash Sale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Current end time: <strong>{{ $flashSale->ends_at->format('M d, Y H:i') }}</strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Extend By</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="hours" min="1" value="24" required>
                            <span class="input-group-text">hours</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Extend Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('productSelect')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.dataset.price;
    const priceHint = document.getElementById('priceHint');
    
    if (price) {
        priceHint.textContent = 'Original price: ৳' + parseFloat(price).toLocaleString('en-US', {minimumFractionDigits: 2});
    } else {
        priceHint.textContent = 'Select a product to see original price';
    }
});
</script>
@endpush
