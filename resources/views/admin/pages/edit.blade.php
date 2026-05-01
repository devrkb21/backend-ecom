@extends('admin.layouts.app')

@section('title', 'Edit Page')
@section('page-title', 'Edit Page')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Edit Page: {{ $page->title }}</h6>
                <div>
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="{{ rtrim(config('app.frontend_url', env('FRONTEND_URL')), '/') }}/pages/{{ $page->slug }}" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="bi bi-eye"></i> View
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.pages.update', $page) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $page->slug) }}" placeholder="Leave empty to auto-generate">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Content</label>
                            <textarea name="content" id="editor" class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-semibold border-bottom pb-2">SEO Settings</h6>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control @error('meta_title') is-invalid @enderror" value="{{ old('meta_title', $page->meta_title) }}">
                            @error('meta_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control @error('meta_description') is-invalid @enderror" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
                            @error('meta_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Page is active and visible</label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Update Page
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        ClassicEditor
            .create(document.querySelector('#editor'))
            .catch(error => {
                console.error(error);
            });
    });
</script>
<style>
    .ck-editor__editable {
        min-height: 400px;
    }
</style>
@endpush
