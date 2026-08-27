@extends('admin.layouts.app')

@section('title', 'License')
@section('page-title', 'License')

@section('content')
@php
    /** @var \App\Services\LicenseService $licenseService */
    $licenseService = app(\App\Services\LicenseService::class);

    $status = $licenseService->status();
    $isValid = $licenseService->isValid();
    $lastVerifiedAt = $licenseService->lastVerifiedAt();
    $expiredSince = $licenseService->expiredSince();
    $lastError = $licenseService->lastError();
    $coreConfig = $licenseService->coreConfig();
    $lockedOrdersCount = $licenseService->lockedOrdersCount();

    $statusBadge = match ($status) {
        'active' => ['bg-success', 'bi-patch-check-fill', 'Active'],
        'expired' => ['bg-danger', 'bi-exclamation-octagon-fill', 'Expired'],
        default => ['bg-secondary', 'bi-dash-circle', 'Not Activated'],
    };
@endphp

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-xs mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-key me-2 text-primary"></i>License Key</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.license.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="license_key">License Key <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control form-control-sm @error('license_key') is-invalid @enderror"
                            id="license_key"
                            name="license_key"
                            placeholder="{{ $licenseService->maskedLicenseKey() ?? 'CZBD-XXXX-XXXX-XXXX' }}"
                            autocomplete="off"
                        >
                        @error('license_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted d-block mt-1">
                            Leave the current key showing above and paste a new one only if you're changing it.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-check-lg me-1"></i> Save &amp; Activate
                    </button>
                </form>

                <form action="{{ route('admin.license.verify') }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i> Verify Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-xs mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-patch-check me-2 text-primary"></i>Status</h6>
                <span class="badge {{ $statusBadge[0] }}"><i class="bi {{ $statusBadge[1] }} me-1"></i>{{ $statusBadge[2] }}</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small" style="width: 200px;">License Key</td>
                            <td class="small"><code>{{ $licenseService->maskedLicenseKey() ?? 'Not set' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Product</td>
                            <td class="small">{{ config('license.product_slug') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Licensing Server</td>
                            <td class="small">
                                @if(config('license.server_url') && config('license.public_key'))
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>Configured</span>
                                @else
                                    <span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Not configured (set LICENSE_SERVER_URL / LICENSE_PUBLIC_KEY)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Last Verified</td>
                            <td class="small">{{ $lastVerifiedAt?->diffForHumans() ?? 'Never' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Offline Grace Period</td>
                            <td class="small">{{ config('license.grace_period_hours') }} hours</td>
                        </tr>
                        @if($lastError)
                            <tr>
                                <td class="text-muted small">Last Error</td>
                                <td class="small text-danger">{{ $lastError }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if(!$isValid && $expiredSince)
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle me-1"></i>
                License expired <strong>{{ $expiredSince->diffForHumans() }}</strong>. The storefront and checkout
                keep working normally, but new products/categories/users can't be created, and
                <strong>{{ $lockedOrdersCount }} order{{ $lockedOrdersCount === 1 ? '' : 's' }}</strong> placed since then
                {{ $lockedOrdersCount === 1 ? 'is' : 'are' }} hidden from the admin order list until you renew.
            </div>
        @elseif($status === 'unactivated')
            <div class="alert alert-secondary mb-4">
                <i class="bi bi-info-circle me-1"></i>
                This installation hasn't been activated yet. Enter a license key and click "Save &amp; Activate".
            </div>
        @endif

        @if(!empty($coreConfig))
            <div class="card shadow-xs">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-sliders me-2 text-primary"></i>Plan Configuration</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($coreConfig as $key => $value)
                                <tr>
                                    <td class="small text-muted">{{ $key }}</td>
                                    <td class="small">
                                        @if(is_bool($value))
                                            <span class="badge {{ $value ? 'bg-success' : 'bg-secondary' }}">{{ $value ? 'Enabled' : 'Disabled' }}</span>
                                        @elseif(is_array($value))
                                            <code>{{ json_encode($value) }}</code>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
