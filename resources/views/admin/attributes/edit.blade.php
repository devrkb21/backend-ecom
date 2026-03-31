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
                                    @if($value->color_code)
                                        <span class="d-inline-block me-2 rounded" style="width: 24px; height: 24px; background-color: {{ $value->color_code }}; border: 1px solid #ccc;"></span>
                                    @endif
                                    <span>{{ $value->value }}</span>
                                </div>
                                <form action="{{ route('admin.attributes.values.destroy', $value) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this value?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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
                        <div class="col-md-5">
                            <label class="form-label small">Color Code (optional)</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="color_picker" value="#ffffff" title="Pick a color">
                                <input type="text" class="form-control" name="color_code" id="color_code" placeholder="#FFFFFF or leave empty">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('color_picker').addEventListener('input', function(e) {
        document.getElementById('color_code').value = e.target.value.toUpperCase();
    });
    document.getElementById('color_code').addEventListener('input', function(e) {
        if (e.target.value.match(/^#[0-9A-Fa-f]{6}$/)) {
            document.getElementById('color_picker').value = e.target.value;
        }
    });
</script>
@endpush
@endsection
