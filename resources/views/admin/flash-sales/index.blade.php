@extends('admin.layouts.app')

@section('title', 'Flash Sales')
@section('page-title', 'Flash Sales')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Active Sales</h6>
                        <h3 class="mb-0">{{ $stats['active'] }}</h3>
                    </div>
                    <i class="bi bi-lightning-charge fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-dark mb-1">Scheduled</h6>
                        <h3 class="mb-0 text-dark">{{ $stats['scheduled'] }}</h3>
                    </div>
                    <i class="bi bi-calendar-event fs-1 text-dark opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Products Sold</h6>
                        <h3 class="mb-0">{{ number_format($stats['total_sold']) }}</h3>
                    </div>
                    <i class="bi bi-box-seam fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Revenue</h6>
                        <h3 class="mb-0">৳{{ number_format($stats['total_revenue'], 0) }}</h3>
                    </div>
                    <i class="bi bi-cash-stack fs-1 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.flash-sales.index') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Ended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Featured</label>
                    <select class="form-select form-select-sm" name="is_featured">
                        <option value="">All</option>
                        <option value="1" {{ request('is_featured') === '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ request('is_featured') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Date Range</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-lightning-charge me-2"></i>All Flash Sales ({{ $flashSales->total() }})</h6>
        <a href="{{ route('admin.flash-sales.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> Create Flash Sale
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Products</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flashSales as $sale)
                        <tr>
                            <td>{{ $sale->id }}</td>
                            <td>
                                <a href="{{ route('admin.flash-sales.show', $sale) }}">{{ $sale->name }}</a>
                                @if($sale->description)
                                    <div class="small text-muted">{{ Str::limit($sale->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                @if($sale->status === 'active')
                                    <span class="badge bg-success">
                                        <i class="bi bi-lightning-charge"></i> Active
                                    </span>
                                @elseif($sale->status === 'scheduled')
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-calendar-event"></i> Scheduled
                                    </span>
                                @elseif($sale->status === 'ended')
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-clock-history"></i> Ended
                                    </span>
                                @else
                                    <span class="badge bg-dark">{{ $sale->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    <i class="bi bi-calendar text-primary"></i> {{ $sale->starts_at->format('M d, Y H:i') }}
                                </div>
                                <div class="small">
                                    <i class="bi bi-calendar-x text-danger"></i> {{ $sale->ends_at->format('M d, Y H:i') }}
                                </div>
                                @if($sale->status === 'active' && $sale->time_remaining)
                                    <div class="small text-success fw-bold">
                                        <i class="bi bi-hourglass-split"></i> 
                                        {{ $sale->time_remaining['days'] }}d {{ $sale->time_remaining['hours'] }}h {{ $sale->time_remaining['minutes'] }}m
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $sale->flashSaleProducts->count() }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-success">{{ number_format($sale->flashSaleProducts->sum('sold_count')) }}</span>
                            </td>
                            <td>
                                ৳{{ number_format($sale->flashSaleProducts->sum(function($p) { return $p->sold_count * $p->flash_price; }), 0) }}
                            </td>
                            <td>
                                @if($sale->is_featured)
                                    <span class="badge bg-warning"><i class="bi bi-star-fill"></i></span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.flash-sales.show', $sale) }}" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.flash-sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($sale->status === 'active')
                                        <form action="{{ route('admin.flash-sales.end', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('End this flash sale early?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="End Early">
                                                <i class="bi bi-stop-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($sale->status !== 'active')
                                        <form action="{{ route('admin.flash-sales.duplicate', $sale) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Duplicate">
                                                <i class="bi bi-files"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.flash-sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this flash sale?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-lightning-charge fs-1 d-block mb-2"></i>
                                No flash sales found. Create your first flash sale!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $flashSales->links() }}
    </div>
</div>
@endsection
