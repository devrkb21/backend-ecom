@extends('admin.layouts.app')

@section('title', 'Reviews')
@section('page-title', 'Reviews Management')

@section('content')
{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 bg-primary bg-opacity-10">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-primary">{{ $stats['total'] }}</div>
                <div class="text-muted small">Total Reviews</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 bg-warning bg-opacity-10">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-warning">{{ $stats['pending'] }}</div>
                <div class="text-muted small">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 bg-success bg-opacity-10">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-success">{{ $stats['approved'] }}</div>
                <div class="text-muted small">Approved</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 bg-info bg-opacity-10">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-info">{{ $stats['featured'] }}</div>
                <div class="text-muted small">Featured</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="featured" {{ request('status') == 'featured' ? 'selected' : '' }}>Featured</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="rating" class="form-select">
                    <option value="">All Ratings</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

{{-- Reviews List --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-star me-2"></i>Reviews ({{ $reviews->total() }})</h6>
        @if($reviews->where('is_approved', false)->count() > 0)
            <form action="{{ route('admin.reviews.bulk-approve') }}" method="POST" class="d-inline">
                @csrf
                @foreach($reviews->where('is_approved', false) as $review)
                    <input type="hidden" name="review_ids[]" value="{{ $review->id }}">
                @endforeach
                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve all pending reviews on this page?')">
                    <i class="bi bi-check-all"></i> Approve All Pending
                </button>
            </form>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td><input type="checkbox" class="review-checkbox" value="{{ $review->id }}"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($review->product)
                                        <a href="{{ route('admin.products.show', $review->product) }}" class="text-decoration-none">
                                            {{ Str::limit($review->product->name, 30) }}
                                        </a>
                                    @else
                                        <span class="text-muted">Deleted Product</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $review->user->name }}</div>
                                <small class="text-muted">{{ $review->user->email }}</small>
                            </td>
                            <td>
                                <div class="text-warning">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                    @endfor
                                </div>
                            </td>
                            <td>
                                @if($review->title)
                                    <strong>{{ Str::limit($review->title, 30) }}</strong><br>
                                @endif
                                <small class="text-muted">{{ Str::limit($review->comment, 50) }}</small>
                                @if($review->is_verified_purchase)
                                    <br><span class="badge bg-success badge-sm">Verified Purchase</span>
                                @endif
                            </td>
                            <td>
                                @if($review->is_approved)
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                                @if($review->is_featured)
                                    <span class="badge bg-info">Featured</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $review->created_at->format('M d, Y') }}</small>
                                <br><small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(!$review->is_approved)
                                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success" title="Approve">
                                                <i class="bi bi-check"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning" title="Reject">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this review?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No reviews found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reviews->hasPages())
        <div class="card-footer">
            {{ $reviews->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.review-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
