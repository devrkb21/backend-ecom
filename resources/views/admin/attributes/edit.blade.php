@extends('admin.layouts.app')

@section('title', 'Edit Attribute')
@section('page-title', 'Edit Attribute')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <a href="{{ route('admin.attributes.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                Edit Attribute: {{ $attribute->name }}
            </div>
            <div class="card-body">
                <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Attribute Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $attribute->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $attribute->slug) }}">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Frontend Display Style <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="display_style" id="style_rounded" value="rounded" {{ old('display_style', $attribute->display_style ?? 'rounded') == 'rounded' ? 'checked' : '' }}>
                            <label class="form-check-label" for="style_rounded">Rounded Rectangle (with name)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="display_style" id="style_circle" value="circle" {{ old('display_style', $attribute->display_style ?? 'rounded') == 'circle' ? 'checked' : '' }}>
                            <label class="form-check-label" for="style_circle">Color Circle (only color)</label>
                        </div>
                        @error('display_style')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Controls how the variant options are shown on the product details page.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Update Attribute
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list"></i> Attribute Values</span>
            </div>
            <div class="card-body">
                @if($attribute->values->isNotEmpty())
                    <div class="list-group mb-4">
                        @foreach($attribute->values as $value)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    @if($value->image_url)
                                        <img src="{{ $value->image_url }}" alt="{{ $value->value }}" class="rounded border me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    @endif
                                    @if($value->color_code)
                                        <span class="d-inline-block me-2 rounded" style="width: 24px; height: 24px; background-color: {{ $value->color_code }}; border: 1px solid #ccc;"></span>
                                    @endif
                                    <span>{{ $value->value }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-id="{{ $value->id }}"
                                        data-value="{{ $value->value }}"
                                        data-color-code="{{ $value->color_code ?? '' }}"
                                        data-image="{{ $value->image ?? '' }}"
                                        data-image-url="{{ $value->image_url ?? '' }}"
                                        data-sort-order="{{ $value->sort_order ?? 0 }}"
                                        onclick="openEditAttributeValueModal(this)"
                                        title="Edit value"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.attributes.values.destroy', $value) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this value?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-4">No values added yet.</p>
                @endif

                <h6>Add New Value</h6>
                <form action="{{ route('admin.attributes.values.store', $attribute) }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">Value Name</label>
                            <input type="text" class="form-control" name="value" placeholder="e.g., Small, Red, Cotton" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Color Code (optional)</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="color_picker" value="#ffffff" title="Pick a color">
                                <input type="text" class="form-control" name="color_code" id="color_code" placeholder="#FFFFFF or leave empty">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Option Image (optional)</label>
                            <input type="hidden" name="image" id="attribute-value-image-input">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary w-100" onclick="openMediaPicker('attribute-value-image-input', false, handleAttributeValueImageSelect)">
                                    <i class="bi bi-image"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="clearAttributeValueImage()" title="Remove">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus"></i> Add
                            </button>
                        </div>
                    </div>

                    <div class="mt-2" id="attribute-value-image-preview-wrap" style="display: none;">
                        <div class="small text-muted mb-1">Selected image</div>
                        <img src="" id="attribute-value-image-preview" class="rounded border" style="width: 64px; height: 64px; object-fit: cover;" alt="Attribute value image preview">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editAttributeValueModal" tabindex="-1" aria-labelledby="editAttributeValueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="edit-attribute-value-form" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editAttributeValueModalLabel">Edit Attribute Value</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Value Name</label>
                        <input type="text" class="form-control" name="value" id="edit_attribute_value_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Color Code (optional)</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="edit_color_picker" value="#ffffff" title="Pick a color">
                            <input type="text" class="form-control" name="color_code" id="edit_color_code" placeholder="#FFFFFF or leave empty">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Option Image (optional)</label>
                        <input type="hidden" name="image" id="edit-attribute-value-image-input">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="openMediaPicker('edit-attribute-value-image-input', false, handleEditAttributeValueImageSelect)">
                                <i class="bi bi-image me-1"></i> Select Image
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="clearEditAttributeValueImage()">
                                <i class="bi bi-x me-1"></i> Remove
                            </button>
                        </div>
                        <div class="mt-2" id="edit-attribute-value-image-preview-wrap" style="display: none;">
                            <img src="" id="edit-attribute-value-image-preview" class="rounded border" style="width: 72px; height: 72px; object-fit: cover;" alt="Edit attribute value image preview">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Sort Order</label>
                        <input type="number" min="0" class="form-control" name="sort_order" id="edit_attribute_sort_order" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.media.picker')

@push('scripts')
<script>
    const addColorPicker = document.getElementById('color_picker');
    const addColorCode = document.getElementById('color_code');
    const editColorPicker = document.getElementById('edit_color_picker');
    const editColorCode = document.getElementById('edit_color_code');
    const editAttributeValueForm = document.getElementById('edit-attribute-value-form');
    const editAttributeValueModal = document.getElementById('editAttributeValueModal');
    const editValueUpdateUrlTemplate = @json(route('admin.attributes.values.update', '__VALUE_ID__'));

    if (addColorPicker && addColorCode) {
        addColorPicker.addEventListener('input', function(e) {
            addColorCode.value = e.target.value.toUpperCase();
        });

        addColorCode.addEventListener('input', function(e) {
            if (e.target.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                addColorPicker.value = e.target.value;
            }
        });
    }

    if (editColorPicker && editColorCode) {
        editColorPicker.addEventListener('input', function(e) {
            editColorCode.value = e.target.value.toUpperCase();
        });

        editColorCode.addEventListener('input', function(e) {
            if (e.target.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                editColorPicker.value = e.target.value;
            }
        });
    }

    function handleAttributeValueImageSelect(media) {
        const input = document.getElementById('attribute-value-image-input');
        const wrap = document.getElementById('attribute-value-image-preview-wrap');
        const preview = document.getElementById('attribute-value-image-preview');

        input.value = media.path;
        preview.src = media.url;
        wrap.style.display = 'block';
    }

    function clearAttributeValueImage() {
        const input = document.getElementById('attribute-value-image-input');
        const wrap = document.getElementById('attribute-value-image-preview-wrap');
        const preview = document.getElementById('attribute-value-image-preview');

        input.value = '';
        preview.src = '';
        wrap.style.display = 'none';
    }

    function openEditAttributeValueModal(button) {
        const valueId = button.getAttribute('data-id');
        const valueName = button.getAttribute('data-value') || '';
        const colorCode = button.getAttribute('data-color-code') || '';
        const imagePath = button.getAttribute('data-image') || '';
        const imageUrl = button.getAttribute('data-image-url') || '';
        const sortOrder = button.getAttribute('data-sort-order') || '0';

        editAttributeValueForm.action = editValueUpdateUrlTemplate.replace('__VALUE_ID__', valueId);
        document.getElementById('edit_attribute_value_name').value = valueName;
        document.getElementById('edit_color_code').value = colorCode;
        document.getElementById('edit_attribute_sort_order').value = sortOrder;
        document.getElementById('edit-attribute-value-image-input').value = imagePath;

        if (colorCode.match(/^#[0-9A-Fa-f]{6}$/)) {
            document.getElementById('edit_color_picker').value = colorCode;
        } else {
            document.getElementById('edit_color_picker').value = '#ffffff';
        }

        const wrap = document.getElementById('edit-attribute-value-image-preview-wrap');
        const preview = document.getElementById('edit-attribute-value-image-preview');

        if (imageUrl) {
            preview.src = imageUrl;
            wrap.style.display = 'block';
        } else {
            preview.src = '';
            wrap.style.display = 'none';
        }

        bootstrap.Modal.getOrCreateInstance(editAttributeValueModal).show();
    }

    function handleEditAttributeValueImageSelect(media) {
        const input = document.getElementById('edit-attribute-value-image-input');
        const wrap = document.getElementById('edit-attribute-value-image-preview-wrap');
        const preview = document.getElementById('edit-attribute-value-image-preview');

        input.value = media.path;
        preview.src = media.url;
        wrap.style.display = 'block';
    }

    function clearEditAttributeValueImage() {
        const input = document.getElementById('edit-attribute-value-image-input');
        const wrap = document.getElementById('edit-attribute-value-image-preview-wrap');
        const preview = document.getElementById('edit-attribute-value-image-preview');

        input.value = '';
        preview.src = '';
        wrap.style.display = 'none';
    }
</script>
@endpush
@endsection
