@extends('admin.layouts.app')

@section('title', 'Loyalty Members')
@section('page-title', 'Loyalty Members')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.loyalty.members.index') }}" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Name, email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Tier</label>
                    <select class="form-select form-select-sm" name="tier">
                        <option value="">All Tiers</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->slug }}" {{ request('tier') === $tier->slug ? 'selected' : '' }}>
                                {{ $tier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Min Points</label>
                    <input type="number" class="form-control form-control-sm" name="min_points" value="{{ request('min_points') }}" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Max Points</label>
                    <input type="number" class="form-control form-control-sm" name="max_points" value="{{ request('max_points') }}" min="0">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="{{ route('admin.loyalty.members.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-x"></i>
                    </a>
                    <a href="{{ route('admin.loyalty.members.export') }}?{{ http_build_query(request()->all()) }}" class="btn btn-sm btn-outline-success" data-no-admin-ajax="1">
                        <i class="bi bi-download"></i> Export
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>Members ({{ $members->total() }})</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Tier</th>
                        <th>Current Points</th>
                        <th>Lifetime Points</th>
                        <th>Rewards Redeemed</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>{{ $member->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.loyalty.members.show', $member) }}">{{ $member->name }}</a>
                                        <div class="small text-muted">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $tierColors = [
                                        'platinum' => '#E5E4E2',
                                        'gold' => '#FFD700',
                                        'silver' => '#C0C0C0',
                                        'bronze' => '#CD7F32',
                                    ];
                                    $tierColor = $tierColors[$member->loyalty_tier ?? 'bronze'] ?? '#CD7F32';
                                @endphp
                                <span class="badge" style="background-color: {{ $tierColor }}; color: #333;">
                                    {{ ucfirst($member->loyalty_tier ?? 'Bronze') }}
                                </span>
                            </td>
                            <td class="fw-semibold text-primary">{{ number_format($member->loyalty_points) }}</td>
                            <td>{{ number_format($member->lifetime_points) }}</td>
                            <td>{{ $member->loyalty_redemptions_count ?? 0 }}</td>
                            <td class="small">{{ $member->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.loyalty.members.show', $member) }}" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#adjustPointsModal{{ $member->id }}" title="Adjust Points">
                                    <i class="bi bi-plus-slash-minus"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Adjust Points Modal -->
                        <div class="modal fade" id="adjustPointsModal{{ $member->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.loyalty.members.adjust', $member) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Adjust Points: {{ $member->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info">
                                                Current Points: <strong>{{ number_format($member->loyalty_points) }}</strong>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Points <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="points" required>
                                                <small class="text-muted">Use positive number to add, negative to deduct</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea class="form-control" name="reason" rows="2" required placeholder="Reason for adjustment..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Adjust Points</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                No members found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', ['paginator' => $members])
    </div>
</div>
@endsection
