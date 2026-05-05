@extends('admin.layouts.app')

@section('title', 'Create Order')
@section('page-title', 'Create New Order')

@section('content')
<form action="{{ route('admin.orders.store') }}" method="POST" id="createOrderForm" data-no-admin-ajax="1">
    @csrf

    <div class="row g-4">
        {{-- Left Column: Products --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2"></i>Products</h6>
                </div>
                <div class="card-body">
                    <div class="position-relative mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="productSearchInput" placeholder="Search product by name or SKU..." autocomplete="off">
                        </div>
                        <div id="productSearchResults" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm" style="display:none; z-index:1050; max-height:350px; overflow-y:auto;"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="orderItemsTable">
                            <thead>
                                <tr>
                                    <th style="width:50px"></th>
                                    <th>Product</th>
                                    <th style="width:100px">Price (৳)</th>
                                    <th style="width:90px">Qty</th>
                                    <th style="width:100px" class="text-end">Total</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="orderItemsBody">
                                <tr id="noItemsRow">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-cart3 fs-3 d-block mb-2"></i>
                                        Search and add products above
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Customer Information --}}
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2"></i>Customer Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('shipping_name') is-invalid @enderror" name="shipping_name" value="{{ old('shipping_name') }}" required>
                            @error('shipping_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('shipping_phone') is-invalid @enderror" name="shipping_phone" value="{{ old('shipping_phone') }}" placeholder="01XXXXXXXXX" required>
                            @error('shipping_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="shipping_email" value="{{ old('shipping_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="shipping_city" value="{{ old('shipping_city') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full Address <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('shipping_address') is-invalid @enderror" name="shipping_address" rows="2" required>{{ old('shipping_address') }}</textarea>
                            @error('shipping_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zip/Postal Code</label>
                            <input type="text" class="form-control" name="shipping_zip" value="{{ old('shipping_zip') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Order Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Order Settings & Summary --}}
        <div class="col-lg-4">
            {{-- Order Settings --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Order Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Order Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status->key }}" {{ old('status', 'pending') === $status->key ? 'selected' : '' }}>
                                    {{ $status->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                            <option value="bkash" {{ old('payment_method') === 'bkash' ? 'selected' : '' }}>bKash</option>
                            <option value="stripe" {{ old('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                            <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_status" required>
                            <option value="pending" {{ old('payment_status', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="awaiting" {{ old('payment_status') === 'awaiting' ? 'selected' : '' }}>Awaiting</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Order Source</label>
                        <select class="form-select" name="order_source">
                            <option value="admin" {{ old('order_source', 'admin') === 'admin' ? 'selected' : '' }}>Admin Panel</option>
                            <option value="phone" {{ old('order_source') === 'phone' ? 'selected' : '' }}>Phone Call</option>
                            <option value="facebook" {{ old('order_source') === 'facebook' ? 'selected' : '' }}>Facebook</option>
                            <option value="whatsapp" {{ old('order_source') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="walk_in" {{ old('order_source') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                            <option value="other" {{ old('order_source') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Shipping Method</label>
                        <select class="form-select" name="shipping_method_id" id="shippingMethodSelect">
                            <option value="">-- No Shipping --</option>
                            @foreach($shippingMethods as $method)
                                <option value="{{ $method->id }}" data-cost="{{ $method->base_cost }}" {{ old('shipping_method_id') == $method->id ? 'selected' : '' }}>
                                    {{ $method->name }} (৳{{ number_format($method->base_cost, 0) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Custom Shipping Charge (৳)</label>
                        <input type="number" class="form-control" name="shipping_charge" id="shippingChargeInput" value="{{ old('shipping_charge', '0') }}" min="0" step="0.01">
                        <small class="text-muted">Leave 0 to use shipping method's default cost</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Discount (৳)</label>
                        <input type="number" class="form-control" name="discount_amount" id="discountInput" value="{{ old('discount_amount', '0') }}" min="0" step="0.01">
                    </div>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2"></i>Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold" id="summarySubtotal">৳0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Shipping</span>
                        <span id="summaryShipping">৳0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Discount</span>
                        <span class="text-danger" id="summaryDiscount">-৳0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-6">Total</span>
                        <span class="fw-bold fs-5 text-primary" id="summaryTotal">৳0.00</span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-100" id="submitOrderBtn" disabled>
                        <i class="bi bi-check-lg me-1"></i> Create Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('productSearchInput');
    var searchResults = document.getElementById('productSearchResults');
    var itemsBody = document.getElementById('orderItemsBody');
    var noItemsRow = document.getElementById('noItemsRow');
    var shippingMethodSelect = document.getElementById('shippingMethodSelect');
    var shippingChargeInput = document.getElementById('shippingChargeInput');
    var discountInput = document.getElementById('discountInput');
    var submitBtn = document.getElementById('submitOrderBtn');
    var searchUrl = '{{ route("admin.orders.search-products") }}';
    var searchTimeout = null;
    var itemIndex = 0;

    // Product search
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        var q = this.value.trim();
        if (q.length < 1) {
            searchResults.style.display = 'none';
            return;
        }
        searchTimeout = setTimeout(function() {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.products || data.products.length === 0) {
                    searchResults.innerHTML = '<div class="p-3 text-center text-muted small">No products found</div>';
                    searchResults.style.display = 'block';
                    return;
                }
                searchResults.innerHTML = data.products.map(function(p) {
                    var img = p.image ? '<img src="' + p.image + '" style="width:36px;height:36px;object-fit:cover;" class="rounded me-2">' : '<div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width:36px;height:36px;"><i class="bi bi-image text-muted small"></i></div>';
                    var variantBtns = '';
                    if (p.variants && p.variants.length > 0) {
                        variantBtns = '<div class="mt-1">' + p.variants.map(function(v) {
                            return '<button type="button" class="btn btn-outline-secondary btn-sm me-1 mb-1 add-variant-btn" data-product=\'' + JSON.stringify(p) + '\' data-variant=\'' + JSON.stringify(v) + '\' style="font-size:11px;padding:1px 6px;">' + v.label + ' (৳' + v.price.toFixed(0) + ')</button>';
                        }).join('') + '</div>';
                    }
                    return '<div class="d-flex align-items-start p-2 border-bottom product-search-item" style="cursor:pointer;" data-product=\'' + JSON.stringify(p) + '\'>' +
                        img +
                        '<div class="flex-grow-1">' +
                            '<div class="fw-semibold small">' + p.name + '</div>' +
                            '<div class="text-muted" style="font-size:11px;">SKU: ' + (p.sku || 'N/A') + ' | Stock: ' + p.stock + ' | ৳' + p.price.toFixed(0) + '</div>' +
                            variantBtns +
                        '</div>' +
                    '</div>';
                }).join('');
                searchResults.style.display = 'block';
            })
            .catch(function() {
                searchResults.style.display = 'none';
            });
        }, 300);
    });

    // Close search on outside click
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Click on product (no variants) to add
    searchResults.addEventListener('click', function(e) {
        // Handle variant button click
        var variantBtn = e.target.closest('.add-variant-btn');
        if (variantBtn) {
            e.stopPropagation();
            var product = JSON.parse(variantBtn.dataset.product);
            var variant = JSON.parse(variantBtn.dataset.variant);
            addItem(product, variant);
            searchResults.style.display = 'none';
            searchInput.value = '';
            return;
        }

        // Handle product click (no variant)
        var item = e.target.closest('.product-search-item');
        if (item) {
            var product = JSON.parse(item.dataset.product);
            if (product.variants && product.variants.length > 0) {
                // Has variants, don't auto-add — let them click variant buttons
                return;
            }
            addItem(product, null);
            searchResults.style.display = 'none';
            searchInput.value = '';
        }
    });

    function addItem(product, variant) {
        if (noItemsRow) noItemsRow.style.display = 'none';

        var idx = itemIndex++;
        var price = variant ? variant.price : product.price;
        var name = product.name + (variant ? ' — ' + variant.label : '');
        var sku = variant ? (variant.sku || product.sku) : product.sku;
        var img = product.image ? '<img src="' + product.image + '" style="width:40px;height:40px;object-fit:cover;" class="rounded">' : '<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>';

        var tr = document.createElement('tr');
        tr.className = 'order-item-row';
        tr.dataset.idx = idx;
        tr.innerHTML =
            '<td>' + img + '</td>' +
            '<td>' +
                '<div class="fw-semibold small">' + name + '</div>' +
                '<div class="text-muted" style="font-size:11px;">SKU: ' + (sku || 'N/A') + '</div>' +
                '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '">' +
                (variant ? '<input type="hidden" name="items[' + idx + '][variant_id]" value="' + variant.id + '">' : '') +
            '</td>' +
            '<td>' +
                '<input type="number" class="form-control form-control-sm item-price" name="items[' + idx + '][price]" value="' + price.toFixed(2) + '" min="0" step="0.01" style="width:90px;">' +
            '</td>' +
            '<td>' +
                '<input type="number" class="form-control form-control-sm item-qty" name="items[' + idx + '][quantity]" value="1" min="1" step="1" style="width:70px;">' +
            '</td>' +
            '<td class="text-end fw-semibold item-total">৳' + price.toFixed(2) + '</td>' +
            '<td>' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-item-btn"><i class="bi bi-x-lg"></i></button>' +
            '</td>';
        itemsBody.appendChild(tr);
        recalculate();
    }

    // Remove item
    itemsBody.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-item-btn');
        if (btn) {
            btn.closest('tr').remove();
            var rows = itemsBody.querySelectorAll('.order-item-row');
            if (rows.length === 0 && noItemsRow) noItemsRow.style.display = '';
            recalculate();
        }
    });

    // Recalculate on price/qty change
    itemsBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-price') || e.target.classList.contains('item-qty')) {
            var row = e.target.closest('tr');
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var qty = parseInt(row.querySelector('.item-qty').value) || 1;
            row.querySelector('.item-total').textContent = '৳' + (price * qty).toFixed(2);
            recalculate();
        }
    });

    // Shipping method change
    shippingMethodSelect.addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        var cost = selected.dataset.cost || 0;
        if (parseFloat(shippingChargeInput.value) === 0 || shippingChargeInput.value === '') {
            shippingChargeInput.value = parseFloat(cost).toFixed(2);
        }
        recalculate();
    });

    shippingChargeInput.addEventListener('input', recalculate);
    discountInput.addEventListener('input', recalculate);

    function recalculate() {
        var rows = itemsBody.querySelectorAll('.order-item-row');
        var subtotal = 0;
        rows.forEach(function(row) {
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var qty = parseInt(row.querySelector('.item-qty').value) || 1;
            subtotal += price * qty;
        });

        var shipping = parseFloat(shippingChargeInput.value) || 0;
        var discount = parseFloat(discountInput.value) || 0;
        var total = Math.max(0, subtotal + shipping - discount);

        document.getElementById('summarySubtotal').textContent = '৳' + subtotal.toFixed(2);
        document.getElementById('summaryShipping').textContent = '৳' + shipping.toFixed(2);
        document.getElementById('summaryDiscount').textContent = '-৳' + discount.toFixed(2);
        document.getElementById('summaryTotal').textContent = '৳' + total.toFixed(2);

        submitBtn.disabled = rows.length === 0;
    }

    // Enter key in search
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') e.preventDefault();
    });
});
</script>
@endpush
