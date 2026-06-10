@extends('admin.layouts.app')

@section('title', 'Couriers')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0">Couriers</h4>
            <p class="text-muted">Manage your courier integrations and API credentials.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- SteadFast Courier Card -->
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.settings.couriers.steadfast') }}" class="text-decoration-none">
                <div class="card h-100 border hover-shadow transition-all">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-truck" style="font-size: 2.5rem; color: #00B795 !important;"></i>
                        </div>
                        <h5 class="card-title text-dark mb-1">SteadFast Courier</h5>
                        <div class="mt-2">
                            @if($steadfastEnabled)
                                <span class="badge rounded-pill px-3 py-2 text-white" style="background-color: #00B795 !important;"><i class="bi bi-check-circle me-1"></i> Active</span>
                            @else
                                <span class="badge bg-secondary rounded-pill px-3 py-2">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pathao Courier Card -->
        <div class="col-md-4 col-lg-3">
            <a href="{{ route('admin.settings.couriers.pathao') }}" class="text-decoration-none">
                <div class="card h-100 border hover-shadow transition-all">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-truck" style="font-size: 2.5rem; color: #E83434 !important;"></i>
                        </div>
                        <h5 class="card-title text-dark mb-1">Pathao Courier</h5>
                        <div class="mt-2">
                            @if($pathaoEnabled)
                                <span class="badge rounded-pill px-3 py-2 text-white" style="background-color: #E83434 !important;"><i class="bi bi-check-circle me-1"></i> Active</span>
                            @else
                                <span class="badge bg-secondary rounded-pill px-3 py-2">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Add more couriers here in the future (e.g. eCourier, RedX) -->
    </div>
</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
    }
    .transition-all {
        transition: all 0.3s ease;
    }
</style>
@endsection
