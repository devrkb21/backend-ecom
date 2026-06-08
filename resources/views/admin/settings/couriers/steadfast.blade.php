@extends('admin.layouts.app')

@section('title', 'SteadFast Courier')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0">SteadFast Courier</h4>
            <p class="text-muted">Manage courier integration and API settings</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            @if(old('steadfast_enabled', $settings->get('steadfast_enabled')?->value) === '1')
                <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-2">
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
            <button class="nav-link active px-4 py-2 text-dark bg-transparent rounded-top border-bottom border-3 border-warning fw-semibold" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">
                <i class="bi bi-speedometer2 me-2 text-warning"></i> Dashboard
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 text-muted bg-transparent border-0" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">
                <i class="bi bi-gear me-2"></i> API Settings
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="steadfastTabsContent">
        
        <!-- DASHBOARD TAB -->
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border h-100 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-3 rounded me-3 text-warning">
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
                            <div class="bg-warning bg-opacity-10 p-3 rounded me-3 text-warning">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 text-uppercase small fw-bold">Pending Send</h6>
                                <h3 class="mb-0 fw-bold text-warning">{{ $pendingSendCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border h-100 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded me-3 text-success">
                                <i class="bi bi-wallet2 fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 text-uppercase small fw-bold">Account Balance</h6>
                                <h3 class="mb-0 fw-bold text-success" id="balanceDisplay">{{ $settings->get('steadfast_last_balance')?->value !== null && $settings->get('steadfast_last_balance')?->value !== '' ? '৳' . number_format((float)$settings->get('steadfast_last_balance')->value, 2) : '--' }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 text-warning me-2"></i>SteadFast Account Balance</h6>
                    <button type="button" class="btn btn-warning fw-semibold text-dark px-4" id="checkBalanceBtn">
                        <i class="bi bi-arrow-repeat me-1"></i> Check Balance
                    </button>
                    <div id="balanceResult" class="mt-2 text-muted small d-none"></div>
                </div>
            </div>

            <div class="card border shadow-sm bg-warning bg-opacity-10 border-warning">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text text-warning me-2"></i>How to use SteadFast Courier</h6>
                    <ol class="text-muted mb-0 small lh-lg">
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
        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
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
                                <h6 class="mb-0 fw-semibold"><i class="bi bi-truck text-warning me-2"></i>SteadFast Courier Integration</h6>
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
                                            <input type="text" class="form-control @error('steadfast_webhook_token') is-invalid @enderror" id="steadfast_webhook_token" name="steadfast_webhook_token" value="{{ $valueOf('steadfast_webhook_token') }}" placeholder="Enter a random secure string for Webhook Authentication">
                                            @error('steadfast_webhook_token')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Generate a secure token here and provide it to SteadFast to authenticate webhook requests.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-4 mb-0 bg-info bg-opacity-10 border-info">
                                        <strong>Callback / Webhook URL:</strong> <code class="text-primary">{{ route('api.webhooks.steadfast') }}</code><br>
                                        <small>Copy this URL and the Auth Token above, then paste them in your SteadFast / Packzy merchant dashboard to receive automatic status updates.</small>
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
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab styling toggles
        const tabs = document.querySelectorAll('#steadfastTabs .nav-link');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                tabs.forEach(t => {
                    t.classList.remove('text-dark', 'border-bottom', 'border-3', 'border-warning', 'fw-semibold');
                    t.classList.add('text-muted', 'border-0');
                    // Handle icon color
                    const icon = t.querySelector('i');
                    if(icon) icon.classList.remove('text-warning');
                });
                event.target.classList.remove('text-muted', 'border-0');
                event.target.classList.add('text-dark', 'border-bottom', 'border-3', 'border-warning', 'fw-semibold');
                const activeIcon = event.target.querySelector('i');
                if(activeIcon) activeIcon.classList.add('text-warning');
            });
        });

        // Integration Toggle
        const toggleCheckboxes = document.querySelectorAll('[data-integration-toggle]');
        toggleCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const targetKey = this.dataset.integrationToggle;
                const formSection = document.querySelector(`[data-integration-form="${targetKey}"]`);
                const disabledNote = document.querySelector(`[data-integration-disabled-note="${targetKey}"]`);
                
                if (this.checked) {
                    formSection.classList.remove('d-none');
                    disabledNote.classList.add('d-none');
                } else {
                    formSection.classList.add('d-none');
                    disabledNote.classList.remove('d-none');
                }
            });
        });

        // Check Balance AJAX
        const checkBalanceBtn = document.getElementById('checkBalanceBtn');
        const balanceDisplay = document.getElementById('balanceDisplay');
        const balanceResult = document.getElementById('balanceResult');

        if (checkBalanceBtn) {
            checkBalanceBtn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Checking...';
                this.disabled = true;
                balanceResult.classList.add('d-none');

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
                        balanceDisplay.innerText = '৳' + parseFloat(data.balance).toFixed(2);
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({icon: 'success', title: 'Balance retrieved successfully'});
                        }
                    } else {
                        balanceResult.innerText = data.message || 'Error occurred';
                        balanceResult.classList.remove('d-none');
                        balanceResult.classList.add('text-danger');
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({icon: 'error', title: 'Failed to retrieve balance'});
                        }
                    }
                })
                .catch(error => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    balanceResult.innerText = 'Network error occurred.';
                    balanceResult.classList.remove('d-none');
                    balanceResult.classList.add('text-danger');
                    console.error('Error:', error);
                });
            });
        }
    });
</script>
@endpush
