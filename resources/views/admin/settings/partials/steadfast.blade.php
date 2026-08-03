@php
    $settings = $courierSettings ?? collect();
    $sentToCourierCount = $sentToCourierCountSteadfast ?? 0;
    $pendingSendCount = $pendingSendCountSteadfast ?? 0;
@endphp

<style>
    /* Steadfast Brand styling overrides */
    .text-steadfast-brand {
        color: #00B795 !important;
    }
    .btn-steadfast {
        background-color: #00B795 !important;
        border-color: #00B795 !important;
        color: #fff !important;
    }
    .btn-steadfast:hover {
        background-color: #00967a !important;
        border-color: #00967a !important;
        color: #fff !important;
    }
    .border-steadfast-tab {
        border-bottom-color: #00B795 !important;
    }
    .steadfast-light-box {
        background-color: #F0FDF4 !important;
        border: 1px solid #C6F6D5 !important;
        color: #22543D !important;
    }
    .steadfast-badge-active {
        background-color: rgba(0, 183, 149, 0.1) !important;
        color: #00B795 !important;
        border: 1px solid rgba(0, 183, 149, 0.3) !important;
    }
</style>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-3">
            <img src="https://steadfast.com.bd/landing-page/asset/images/logo/logo.svg" alt="SteadFast Courier Logo" style="height: 38px;">
        </div>
        <p class="text-muted mt-1">Manage courier integration and API settings</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        @if(old('steadfast_enabled', $settings->get('steadfast_enabled')?->value) === '1')
            <span class="badge steadfast-badge-active rounded-pill px-3 py-2">
                <i class="bi bi-circle-fill small me-1"></i> Active
            </span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-3 py-2">
                <i class="bi bi-circle-fill small me-1"></i> Inactive
            </span>
        @endif
    </div>
</div>

<!-- Tabs Nav -->
<ul class="nav nav-tabs mb-4 border-bottom-0" id="steadfastTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 py-2 text-dark bg-transparent rounded-top border-bottom border-3 border-steadfast-tab fw-semibold" id="steadfast-dashboard-tab" data-bs-toggle="tab" data-bs-target="#steadfast-dashboard" type="button" role="tab" aria-controls="steadfast-dashboard" aria-selected="true">
            <i class="bi bi-speedometer2 me-2 text-steadfast-brand"></i> Dashboard
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-2 text-muted bg-transparent border-0" id="steadfast-settings-tab" data-bs-toggle="tab" data-bs-target="#steadfast-settings" type="button" role="tab" aria-controls="steadfast-settings" aria-selected="false">
            <i class="bi bi-gear me-2"></i> API Settings
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="steadfastTabsContent">
    
    <!-- DASHBOARD TAB -->
    <div class="tab-pane fade show active" id="steadfast-dashboard" role="tabpanel" aria-labelledby="steadfast-dashboard-tab">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="p-3 rounded me-3 text-steadfast-brand" style="background-color: rgba(0, 183, 149, 0.1);">
                            <i class="bi bi-send-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 text-uppercase small fw-bold">Sent to Courier</h6>
                            <h3 class="mb-0 fw-bold">{{ $sentToCourierCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="p-3 rounded me-3 text-steadfast-brand" style="background-color: rgba(0, 183, 149, 0.1);">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 text-uppercase small fw-bold">Pending Send</h6>
                            <h3 class="mb-0 fw-bold text-steadfast-brand">{{ $pendingSendCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="p-3 rounded me-3 text-steadfast-brand" style="background-color: rgba(0, 183, 149, 0.1);">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 text-uppercase small fw-bold">Account Balance</h6>
                            <h3 class="mb-0 fw-bold text-steadfast-brand" id="steadfastBalanceDisplay">{{ $settings->get('steadfast_last_balance')?->value !== null && $settings->get('steadfast_last_balance')?->value !== '' ? '৳' . number_format((float)$settings->get('steadfast_last_balance')->value, 2) : '--' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 border shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 text-steadfast-brand me-2"></i>SteadFast Account Balance</h6>
                <button type="button" class="btn btn-steadfast fw-semibold px-4" id="checkSteadfastBalanceBtn">
                    <i class="bi bi-arrow-repeat me-1"></i> Check Balance
                </button>
                <div id="steadfastBalanceResult" class="mt-2 text-muted small d-none"></div>
            </div>
        </div>

        <div class="card border shadow-sm steadfast-light-box">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text text-steadfast-brand me-2"></i>How to use SteadFast Courier</h6>
                <ol class="text-dark mb-0 small lh-lg">
                    <li>Go to <strong>API Settings</strong> tab and enter your SteadFast API Key & Secret Key.</li>
                    <li>Enable the courier integration using the toggle.</li>
                    <li>Go to <strong>Orders</strong> page - click "Send Courier" button on any pending/processing order.</li>
                    <li>Or select multiple orders and use <strong>Bulk Send to Courier</strong>.</li>
                    <li>Track delivery status directly from the Orders table.</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- SETTINGS TAB -->
    <div class="tab-pane fade" id="steadfast-settings" role="tabpanel" aria-labelledby="steadfast-settings-tab">
        <div class="row">
            <div class="col-md-8 col-lg-6">
                <form action="{{ route('admin.settings.couriers.steadfast.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    @php
                        $valueOf = function($key) use ($settings) {
                            return old($key, $settings->get($key)?->value);
                        };
                        $isChecked = function($key) use ($settings) {
                            return old($key, $settings->get($key)?->value) === '1';
                        };
                    @endphp

                    <div class="card border shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-truck text-steadfast-brand me-2"></i>SteadFast Courier Integration</h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="steadfast_enabled" name="steadfast_enabled" value="1" data-integration-toggle="steadfast_enabled" {{ $isChecked('steadfast_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="steadfast_enabled">Enable</label>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div data-integration-form="steadfast_enabled" class="{{ $isChecked('steadfast_enabled') ? '' : 'd-none' }}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="steadfast_api_key">API Key</label>
                                        <input type="text" class="form-control @error('steadfast_api_key') is-invalid @enderror" id="steadfast_api_key" name="steadfast_api_key" value="{{ $valueOf('steadfast_api_key') }}" placeholder="Enter SteadFast API Key">
                                        @error('steadfast_api_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="steadfast_secret_key">Secret Key</label>
                                        <input type="text" class="form-control @error('steadfast_secret_key') is-invalid @enderror" id="steadfast_secret_key" name="steadfast_secret_key" value="{{ $valueOf('steadfast_secret_key') }}" placeholder="Enter SteadFast Secret Key">
                                        @error('steadfast_secret_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row g-3 mt-2">
                                    <div class="col-md-12">
                                        <label class="form-label fw-semibold" for="steadfast_webhook_token">Webhook Auth Token (Bearer)</label>
                                        <input type="text" class="form-control @error('steadfast_webhook_token') is-invalid @enderror" id="steadfast_webhook_token" name="steadfast_webhook_token" value="{{ $valueOf('steadfast_webhook_token') }}" placeholder="Enter a secure string">
                                        @error('steadfast_webhook_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Generate a secure token here and provide it to SteadFast to authenticate webhook requests.</div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 p-3 rounded steadfast-light-box">
                                    <label class="form-label fw-bold mb-1"><i class="bi bi-link-45deg text-steadfast-brand me-1"></i>Callback / Webhook URL</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm bg-white text-dark" value="{{ route('api.webhooks.steadfast') }}" readonly id="steadfastWebhookUrl">
                                        <button class="btn btn-sm btn-steadfast" type="button" onclick="navigator.clipboard.writeText(document.getElementById('steadfastWebhookUrl').value); showAdminToast('Copied to clipboard!', 'success');">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2">Copy this URL and the Auth Token above, then paste them in your SteadFast / Packzy merchant dashboard to receive automatic status updates.</small>
                                </div>
                            </div>

                            <div class="text-muted {{ $isChecked('steadfast_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="steadfast_enabled">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="bi bi-info-circle me-3 fs-4"></i>
                                    <p class="mb-0 small">SteadFast integration is currently disabled. Toggle the switch above to enable and configure API credentials.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start">
                        <button type="submit" class="btn btn-steadfast px-4">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const steadfastTabs = document.querySelectorAll('#steadfastTabs .nav-link');
        steadfastTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                steadfastTabs.forEach(t => {
                    t.classList.remove('text-dark', 'border-bottom', 'border-3', 'border-steadfast-tab', 'fw-semibold');
                    t.classList.add('text-muted', 'border-0');
                    const icon = t.querySelector('i');
                    if(icon) icon.classList.remove('text-steadfast-brand');
                });
                event.target.classList.remove('text-muted', 'border-0');
                event.target.classList.add('text-dark', 'border-bottom', 'border-3', 'border-steadfast-tab', 'fw-semibold');
                const activeIcon = event.target.querySelector('i');
                if(activeIcon) activeIcon.classList.add('text-steadfast-brand');
            });
        });

        // Check Balance AJAX
        const checkSteadfastBalanceBtn = document.getElementById('checkSteadfastBalanceBtn');
        const steadfastBalanceDisplay = document.getElementById('steadfastBalanceDisplay');
        const steadfastBalanceResult = document.getElementById('steadfastBalanceResult');

        if (checkSteadfastBalanceBtn) {
            checkSteadfastBalanceBtn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Checking...';
                this.disabled = true;
                steadfastBalanceResult.classList.add('d-none');

                fetch('{{ route("admin.settings.couriers.steadfast.balance") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    
                    if (data.success) {
                        steadfastBalanceDisplay.innerText = '৳' + parseFloat(data.balance).toFixed(2);
                        showAdminToast('Balance retrieved successfully', 'success');
                    } else {
                        steadfastBalanceResult.innerText = data.message || 'Error occurred';
                        steadfastBalanceResult.classList.remove('d-none');
                        steadfastBalanceResult.classList.add('text-danger');
                        showAdminToast('Failed to retrieve balance', 'danger');
                    }
                })
                .catch(error => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    steadfastBalanceResult.innerText = 'Network error occurred.';
                    steadfastBalanceResult.classList.remove('d-none');
                    steadfastBalanceResult.classList.add('text-danger');
                    showAdminToast('Network error occurred.', 'danger');
                    console.error('Error:', error);
                });
            });
        }
    });
</script>
@endpush
