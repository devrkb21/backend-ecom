@extends('admin.layouts.app')

@section('title', 'Review Details')
@section('page-title', 'Review Details')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        {{-- Review Content --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <span class="fw-semibold">Review #{{ $review->id }}</span>
                </div>
                <div class="d-flex gap-2">
                    @if(!$review->is_approved)
                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check"></i> Approve
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="bi bi-x"></i> Reject
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('admin.reviews.toggle-featured', $review) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $review->is_featured ? 'btn-info' : 'btn-outline-info' }}">
                            <i class="bi bi-star{{ $review->is_featured ? '-fill' : '' }}"></i> 
                            {{ $review->is_featured ? 'Featured' : 'Feature' }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                {{-- Rating --}}
                <div class="mb-4 text-center py-3 bg-light rounded">
                    <div class="fs-1 text-warning mb-2">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                        @endfor
                    </div>
                    <div class="fs-4 fw-bold">{{ $review->rating }}/5</div>
                </div>

                {{-- Title & Comment --}}
                @if($review->title)
                    <h5 class="mb-3">{{ $review->title }}</h5>
                @endif

                @if($review->comment)
                    <p class="mb-4" style="white-space: pre-line;">{{ $review->comment }}</p>
                @else
                    <p class="text-muted mb-4"><em>No comment provided</em></p>
                @endif

                {{-- Pros & Cons --}}
                <div class="row mb-4">
                    @if($review->pros && count($review->pros) > 0)
                        <div class="col-md-6">
                            <h6 class="text-success"><i class="bi bi-plus-circle"></i> Pros</h6>
                            <ul class="list-unstyled">
                                @foreach($review->pros as $pro)
                                    <li><i class="bi bi-check text-success"></i> {{ $pro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($review->cons && count($review->cons) > 0)
                        <div class="col-md-6">
                            <h6 class="text-danger"><i class="bi bi-dash-circle"></i> Cons</h6>
                            <ul class="list-unstyled">
                                @foreach($review->cons as $con)
                                    <li><i class="bi bi-x text-danger"></i> {{ $con }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Review Images --}}
                @if($review->images && count($review->images) > 0)
                    <div class="mb-4">
                        <h6><i class="bi bi-images"></i> Review Images</h6>
                        <div class="row g-2">
                            @foreach($review->images as $image)
                                @php
                                    $reviewImagePath = ltrim((string) $image, '/');
                                    $reviewImageUrl = (str_starts_with($reviewImagePath, 'http://') || str_starts_with($reviewImagePath, 'https://'))
                                        ? $image
                                        : ((str_starts_with($reviewImagePath, 'media/') || str_starts_with($reviewImagePath, 'storage/'))
                                            ? asset($reviewImagePath)
                                            : asset('storage/' . $reviewImagePath));
                                @endphp
                                <div class="col-auto">
                                    <a href="{{ $reviewImageUrl }}" target="_blank">
                                        <img src="{{ $reviewImageUrl }}" 
                                             alt="Review image" 
                                             class="rounded" 
                                             style="width: 100px; height: 100px; object-fit: cover;">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Helpfulness --}}
                <div class="d-flex gap-4 text-muted small">
                    <span><i class="bi bi-hand-thumbs-up"></i> {{ $review->helpful_count }} found helpful</span>
                    <span><i class="bi bi-hand-thumbs-down"></i> {{ $review->unhelpful_count }} found unhelpful</span>
                    @if($review->helpfulness_percentage !== null)
                        <span><i class="bi bi-percent"></i> {{ $review->helpfulness_percentage }}% helpful</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Admin Reply Section --}}
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-reply"></i> Admin Reply</h6>
            </div>
            <div class="card-body">
                @if($review->admin_reply)
                    <div class="bg-light rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary">Store Response</span>
                            <small class="text-muted">{{ $review->admin_replied_at?->format('M d, Y H:i') }}</small>
                        </div>
                        <p class="mb-0" style="white-space: pre-line;">{{ $review->admin_reply }}</p>
                    </div>
                    <form action="{{ route('admin.reviews.remove-reply', $review) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this reply?')">
                            <i class="bi bi-trash"></i> Remove Reply
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.reviews.reply', $review) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="admin_reply" class="form-control" rows="4" 
                                      placeholder="Write a public reply to this review..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Post Reply
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Status --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Status</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between">
                        <span>Approval</span>
                        @if($review->is_approved)
                            <span class="badge bg-success">Approved</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Featured</span>
                        @if($review->is_featured)
                            <span class="badge bg-info">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Verified Purchase</span>
                        @if($review->is_verified_purchase)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer Info --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person"></i> Customer</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                         style="width: 50px; height: 50px; font-size: 1.2rem;">
                        {{ strtoupper(substr($review->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <strong>{{ $review->user->name }}</strong>
                        <div class="text-muted small">{{ $review->user->email }}</div>
                    </div>
                </div>
                <a href="{{ route('admin.users.show', $review->user) }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-eye"></i> View Customer
                </a>
            </div>
        </div>

        {{-- Product Info --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-box"></i> Product</h6>
            </div>
            <div class="card-body">
                @if($review->product)
                    <div class="d-flex gap-3 mb-3">
                        @if($review->product->primary_image_url)
                            <img src="{{ $review->product->primary_image_url }}" 
                                 alt="{{ $review->product->name }}" 
                                 class="rounded" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="width: 60px; height: 60px;">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                        @endif
                        <div>
                            <strong>{{ $review->product->name }}</strong>
                            <div class="text-muted small">SKU: {{ $review->product->sku }}</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.products.show', $review->product) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-eye"></i> View Product
                    </a>
                @else
                    <p class="text-muted mb-0"><em>Product has been deleted</em></p>
                @endif
            </div>
        </div>

        {{-- Order Info --}}
        @if($review->order)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-receipt"></i> Related Order</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Order #{{ $review->order->order_number }}</strong>
                    </p>
                    <p class="text-muted small mb-3">
                        {{ $review->order->created_at->format('M d, Y') }}
                    </p>
                    <a href="{{ route('admin.orders.show', $review->order) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-eye"></i> View Order
                    </a>
                </div>
            </div>
        @endif

        {{-- Timestamps --}}
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock"></i> Timeline</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Submitted</span>
                        <span>{{ $review->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($review->updated_at->ne($review->created_at))
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Last Updated</span>
                            <span>{{ $review->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                    @if($review->admin_replied_at)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Admin Replied</span>
                            <span>{{ $review->admin_replied_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="card border-danger mt-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Danger Zone</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100" 
                            onclick="return confirm('Are you sure you want to delete this review? This cannot be undone.')">
                        <i class="bi bi-trash"></i> Delete Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
