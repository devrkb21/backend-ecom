@extends('admin.layouts.app')

@section('title', 'Create Attribute')
@section('page-title', 'Create Attribute')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('admin.attributes.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                New Attribute
            </div>
            <div class="card-body">
                <form action="{{ route('admin.attributes.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Attribute Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g., Size, Color, Material" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}">
                        <div class="form-text">Leave empty to auto-generate from name.</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> After creating the attribute, you can add values to it from the edit page.
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Create Attribute
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
