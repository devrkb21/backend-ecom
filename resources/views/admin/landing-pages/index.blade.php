@extends('admin.layouts.app')

@section('title', 'Landing Pages')
@section('page-title', 'Landing Pages')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-file-earmark-text me-2"></i>Landing Pages</h6>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.landing-pages.index') }}" method="GET" class="d-flex">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    @if(request('search'))
                        <a href="{{ route('admin.landing-pages.index') }}" class="btn btn-outline-danger"><i class="bi bi-x"></i></a>
                    @endif
                </div>
            </form>
            <a href="{{ route('admin.landing-pages.create') }}" class="btn btn-primary btn-sm text-nowrap">
                <i class="bi bi-plus"></i> Create Landing Page
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Products</th>
                        <th>Frontend Custom URL</th>
                        <th>Template Type</th>
                        <th>Clicks (Views)</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($landingPages as $lp)
                        <tr>
                            <td>{{ $lp->id }}</td>
                            <td>
                                <strong>{{ $lp->title }}</strong>
                                @if($lp->theme_color)
                                    <span class="badge border ms-1" style="background-color: {{ $lp->theme_color }}; width: 12px; height: 12px; display: inline-block; padding: 0;" title="Theme Accent Color: {{ $lp->theme_color }}">&nbsp;</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $lpProductIds = $lp->product_ids ?? ($lp->product_id ? [$lp->product_id] : []);
                                    $lpCount = count($lpProductIds);
                                @endphp
                                @if($lp->product)
                                    <a href="{{ route('admin.products.edit', $lp->product) }}" target="_blank" class="d-block text-truncate" style="max-width:160px;" title="{{ $lp->product->name }}">
                                        {{ $lp->product->name }}
                                    </a>
                                    @if($lpCount > 1)
                                        <span class="badge bg-primary bg-opacity-10 text-primary">+{{ $lpCount - 1 }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <code>/l/{{ $lp->slug }}</code>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border capitalize">
                                    {{ str_replace('_', ' ', $lp->template_type) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info px-2 py-1">
                                    <i class="bi bi-eye me-1"></i> {{ $lp->views_count }}
                                </span>
                            </td>
                            <td>
                                @if($lp->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Hidden</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @php
                                    $frontendBaseUrl = rtrim(config('app.frontend_url', 'https://innercollection.com.bd'), '/');
                                    $previewUrl = $frontendBaseUrl . '/l/' . $lp->slug;
                                @endphp
                                <a href="{{ $previewUrl }}" target="_blank" class="btn btn-sm btn-outline-info me-1" title="View Frontend Landing Page">
                                    <i class="bi bi-box-arrow-up-right"></i> View Page
                                </a>
                                <a href="{{ route('admin.landing-pages.edit', $lp) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.landing-pages.destroy', $lp) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this landing page configuration?');">
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
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No landing pages found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($landingPages->hasPages())
    <div class="card-footer">
        @include('admin.partials.pagination', ['paginator' => $landingPages])
    </div>
    @endif
</div>
@endsection
