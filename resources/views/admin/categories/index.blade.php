@extends('admin.layouts.app')

@section('title', 'Categories')
@section('page-title', 'Categories')

@section('content')
<div class="row g-3">
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-diagram-2 me-2"></i>Category Tree</h6>
            </div>
            <div class="card-body">
                @php
                    function renderCategoryTree($categories, $level = 0) {
                        foreach ($categories as $category) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                            $icon = $category->children->isNotEmpty() ? '<i class="bi bi-folder-fill text-warning"></i>' : '<i class="bi bi-folder text-secondary"></i>';
                            $statusBadge = $category->is_active ? '' : '<span class="badge bg-secondary ms-1">Hidden</span>';
                            $productCount = $category->products_count ?? 0;
                            echo "<div class=\"py-2 border-bottom\">{$indent}{$icon} <a href=\"" . route('admin.categories.edit', $category) . "\" class=\"text-decoration-none\">{$category->name}</a> <small class=\"text-muted\">({$productCount})</small>{$statusBadge}</div>";
                            if ($category->children->isNotEmpty()) {
                                renderCategoryTree($category->children, $level + 1);
                            }
                        }
                    }
                    
                    $rootCategories = $allCategories->filter(fn($c) => $c->parent_id === null);
                @endphp

                @if($rootCategories->isNotEmpty())
                    @php renderCategoryTree($rootCategories); @endphp
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No categories found.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-tags me-2"></i>All Categories ({{ $allCategories->count() }})</h6>
                <div class="d-flex gap-2">
                    <form action="{{ route('admin.categories.index') }}" method="GET" class="d-flex" data-realtime-filter="1">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Search categories..." value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                            @if(request('search'))
                                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-danger"><i class="bi bi-x"></i></a>
                            @endif
                        </div>
                    </form>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm text-nowrap">
                        <i class="bi bi-plus"></i> Add Category
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Parent</th>
                                <th class="text-center">Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>
                                        <strong>{{ $category->name }}</strong>
                                        <div class="small text-muted">{{ $category->slug }}</div>
                                    </td>
                                    <td>
                                        @if($category->parent)
                                            <a href="{{ route('admin.categories.edit', $category->parent) }}" class="text-decoration-none">{{ $category->parent->name }}</a>
                                        @else
                                            <span class="text-muted">Root</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $category->products_count ?? 0 }}</td>
                                    <td>
                                        @if($category->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
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
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No categories found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                @include('admin.partials.pagination', ['paginator' => $categories])
            </div>
        </div>
    </div>
</div>
@endsection
