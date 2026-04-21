@php
    $variantAttributes = $variantAttributes
        ?? $product->variants
            ->flatMap(fn($variant) => $variant->attributeValues->map(fn($value) => $value->attribute))
            ->filter()
            ->unique('id')
            ->sortBy('id')
            ->values();

    $variantBasePurchasePrice = $variantBasePurchasePrice ?? (float) ($product->buy_price ?? 0);
    $variantBaseRegularPrice = $variantBaseRegularPrice ?? (float) $product->regular_price;
    $variantBaseDiscountPrice = $variantBaseDiscountPrice ?? (float) ($product->sale_price ?? $product->regular_price);
    $stockEnabled = $stockEnabled ?? true;
    $submittedVariantRows = collect(old('variants', []))
        ->filter(fn($row) => is_array($row) && array_key_exists('id', $row))
        ->keyBy(fn($row) => (string) $row['id']);
    $selectedDefaultVariantId = old('default_variant_id', $product->getDefaultVariantId());
@endphp

@if($product->variants->isNotEmpty())
    <form action="{{ route('admin.products.variants.matrix-update', $product) }}" method="POST" id="variantMatrixForm" data-no-admin-ajax="1">
        @csrf
        @method('PUT')
        <div class="table-responsive variant-matrix-table">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 44px;">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="selectAllVariants"
                                onchange="toggleAllVariants(this)"
                            >
                        </th>
                        <th style="width: 60px;">SL</th>
                        <th style="width: 60px;">Image</th>
                        <th style="min-width: 180px;">Name</th>
                        <th>SKU</th>
                        @foreach($variantAttributes as $variantAttribute)
                            <th>{{ $variantAttribute->name }}</th>
                        @endforeach
                        <th style="min-width: 120px;">Purchase Price</th>
                        <th class="variant-copy-column"></th>
                        <th style="min-width: 120px;">Regular Price</th>
                        <th class="variant-copy-column"></th>
                        <th style="min-width: 140px;">Discounted Price</th>
                        <th class="variant-copy-column"></th>
                        <th>Stock @if($stockEnabled)<span class="text-danger">*</span>@endif</th>
                        <th class="variant-copy-column"></th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->variants as $index => $variant)
                        @php
                            $attributeValueMap = $variant->attributeValues->keyBy(fn($value) => (int) $value->attribute_id);
                            $submittedVariant = (array) ($submittedVariantRows->get((string) $variant->id) ?? []);
                            $variantPurchasePrice = (float) $variant->purchase_price;
                            $variantRegularPrice = (float) $variant->regular_price;
                            $variantDiscountedRaw = $variant->getRawOriginal('discounted_price');
                            $variantDiscountedPrice = $variantDiscountedRaw !== null
                                ? (float) $variantDiscountedRaw
                                : null;

                            $variantSkuInput = array_key_exists('sku', $submittedVariant)
                                ? (string) $submittedVariant['sku']
                                : (string) $variant->sku;
                            $variantPurchasePriceInput = array_key_exists('purchase_price', $submittedVariant)
                                ? (string) $submittedVariant['purchase_price']
                                : number_format($variantPurchasePrice, 2, '.', '');
                            $variantRegularPriceInput = array_key_exists('regular_price', $submittedVariant)
                                ? (string) $submittedVariant['regular_price']
                                : number_format($variantRegularPrice, 2, '.', '');
                            $variantDiscountedPriceInput = array_key_exists('discounted_price', $submittedVariant)
                                ? (string) $submittedVariant['discounted_price']
                                : ($variantDiscountedPrice !== null ? number_format($variantDiscountedPrice, 2, '.', '') : '');
                            $variantStockInput = array_key_exists('stock_quantity', $submittedVariant)
                                ? (string) $submittedVariant['stock_quantity']
                                : (string) $variant->stock_quantity;
                            $variantIsActiveInput = array_key_exists('is_active', $submittedVariant)
                                ? filter_var($submittedVariant['is_active'], FILTER_VALIDATE_BOOLEAN)
                                : (bool) $variant->is_active;

                            $variantImageInput = array_key_exists('image', $submittedVariant)
                                ? trim((string) $submittedVariant['image'])
                                : trim((string) ($variant->image ?? ''));
                            $variantImagePath = ltrim($variantImageInput, '/');
                            $variantImageUrl = '';

                            if ($variantImagePath !== '') {
                                $variantImageUrl = (str_starts_with($variantImagePath, 'http://') || str_starts_with($variantImagePath, 'https://'))
                                    ? $variantImageInput
                                    : ((str_starts_with($variantImagePath, 'media/') || str_starts_with($variantImagePath, 'storage/'))
                                        ? asset($variantImagePath)
                                        : asset('storage/' . $variantImagePath));
                            }
                        @endphp
                        <tr id="variant-row-{{ $variant->id }}">
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-check-input variant-checkbox"
                                    value="{{ $variant->id }}"
                                    onchange="updateBulkEditCount()"
                                >
                            </td>
                            <td>
                                <strong>{{ $index + 1 }}</strong>
                                <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                            </td>
                            <td>
                                <input
                                    type="hidden"
                                    name="variants[{{ $index }}][image]"
                                    id="variant-image-input-{{ $variant->id }}"
                                    value="{{ $variantImageInput }}"
                                >
                                <button
                                    type="button"
                                    class="btn p-0 border-0 bg-transparent"
                                    onclick="openVariantMatrixImagePicker({{ $variant->id }})"
                                    title="Select variant image from media library"
                                >
                                    <div id="variant-row-image-box-{{ $variant->id }}" class="rounded d-flex align-items-center justify-content-center {{ $variantImageUrl ? '' : 'bg-light' }}" style="width: 45px; height: 45px; overflow: hidden;">
                                        @if($variantImageUrl)
                                            <img src="{{ $variantImageUrl }}" alt="Variant" class="w-100 h-100" style="object-fit: cover;">
                                        @else
                                            <i class="bi bi-image text-muted"></i>
                                        @endif
                                    </div>
                                </button>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @forelse($variant->attributeValues as $attrValue)
                                        <span class="badge bg-light text-dark border">{{ $attrValue->value }}</span>
                                    @empty
                                        <span class="text-muted small">No attributes</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="form-control form-control-sm"
                                    name="variants[{{ $index }}][sku]"
                                    value="{{ $variantSkuInput }}"
                                    required
                                >
                            </td>

                            @foreach($variantAttributes as $variantAttribute)
                                @php
                                    $value = $attributeValueMap->get((int) $variantAttribute->id);
                                @endphp
                                <td>
                                    @if($value)
                                        @if($value->color_code)
                                            <span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background-color: {{ $value->color_code }}; border: 1px solid #ccc; vertical-align: middle;"></span>
                                        @endif
                                        {{ $value->value }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endforeach

                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">৳</span>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        name="variants[{{ $index }}][purchase_price]"
                                        value="{{ $variantPurchasePriceInput }}"
                                        required
                                    >
                                </div>
                            </td>
                            <td class="variant-copy-cell align-middle">
                                @if($index === 0)
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm variant-copy-field-btn"
                                        onclick="copyVariantFieldFromRow(this, 'purchase_price')"
                                        title="Copy Purchase Price to all rows"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">৳</span>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        name="variants[{{ $index }}][regular_price]"
                                        value="{{ $variantRegularPriceInput }}"
                                        required
                                    >
                                </div>
                            </td>
                            <td class="variant-copy-cell align-middle">
                                @if($index === 0)
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm variant-copy-field-btn"
                                        onclick="copyVariantFieldFromRow(this, 'regular_price')"
                                        title="Copy Regular Price to all rows"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">৳</span>
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control"
                                        name="variants[{{ $index }}][discounted_price]"
                                        value="{{ $variantDiscountedPriceInput }}"
                                        placeholder="Optional"
                                    >
                                </div>
                            </td>
                            <td class="variant-copy-cell align-middle">
                                @if($index === 0)
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm variant-copy-field-btn"
                                        onclick="copyVariantFieldFromRow(this, 'discounted_price')"
                                        title="Copy Discounted Price to all rows"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    class="form-control form-control-sm"
                                    name="variants[{{ $index }}][stock_quantity]"
                                    value="{{ $variantStockInput }}"
                                    @if($stockEnabled) required @endif
                                >
                            </td>
                            <td class="variant-copy-cell align-middle">
                                @if($index === 0)
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm variant-copy-field-btn"
                                        onclick="copyVariantFieldFromRow(this, 'stock_quantity')"
                                        title="Copy Stock to all rows"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                @endif
                            </td>
                            <td>
                                <input type="hidden" name="variants[{{ $index }}][is_active]" value="0">
                                <div class="form-check form-switch m-0">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        name="variants[{{ $index }}][is_active]"
                                        value="1"
                                        {{ $variantIsActiveInput ? 'checked' : '' }}
                                    >
                                </div>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editVariant({{ $variant->id }}, {{ json_encode(array_merge($variant->toArray(), ['image_url' => $variant->image_url])) }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteVariant({{ $variant->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="{{ 11 + $variantAttributes->count() }}" class="text-end"><strong>Total Stock:</strong></td>
                        <td><strong>{{ $product->variants->sum('stock_quantity') }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row g-3 align-items-end mt-1">
            <div class="col-lg-6">
                <label for="default_variant_id" class="form-label mb-1">Base Variant (Global Price)</label>
                <select class="form-select" id="default_variant_id" name="default_variant_id">
                    <option value="">Auto (lowest active variant price)</option>
                    @foreach($product->variants as $variant)
                        @php
                            $variantLabel = $variant->attributeValues->pluck('value')->implode(' / ');
                        @endphp
                        <option value="{{ $variant->id }}" {{ (string) $selectedDefaultVariantId === (string) $variant->id ? 'selected' : '' }}>
                            {{ $variant->sku ?: ('Variant #' . $variant->id) }}
                            @if($variantLabel)
                                - {{ $variantLabel }}
                            @endif
                            @if(!$variant->is_active)
                                (inactive)
                            @endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">This variant drives base/global product price across listing and storefront displays.</small>
            </div>
            <div class="col-lg-6 d-flex flex-wrap justify-content-lg-end gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkEditModal" onclick="updateBulkEditCount()">
                    <i class="bi bi-pencil-square me-1"></i> Bulk Edit/Delete
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="bulkDeleteVariantsBtn" onclick="submitBulkDeleteSelectedVariants()" disabled>
                    <i class="bi bi-trash me-1"></i> Delete Selected
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i> Update Variants
                </button>
            </div>
        </div>

        @if(!$stockEnabled)
            <p class="text-muted small mt-2 mb-0">Global stock tracking is disabled. Variant stock values are optional and ignored.</p>
        @endif
    </form>
@else
    <div class="text-center py-5">
        <i class="bi bi-box text-muted" style="font-size: 3rem;"></i>
        <p class="text-muted mt-3 mb-0">No variants yet.</p>
        <p class="text-muted small">Select variation name and values above to generate variants automatically.</p>
    </div>
@endif

<form id="deleteVariantForm" method="POST" style="display: none;" data-no-admin-ajax="1">
    @csrf
    @method('DELETE')
</form>

<form id="bulkDeleteVariantsForm" action="{{ route('admin.products.variants.bulk-update', $product) }}" method="POST" class="d-none" data-no-admin-ajax="1">
    @csrf
    @method('PUT')
    <input type="hidden" name="bulk_delete" value="1">
</form>
