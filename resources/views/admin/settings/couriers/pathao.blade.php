@extends('admin.layouts.app')

@section('title', 'Pathao Courier')

@section('content')
<style>
    /* Pathao Brand styling overrides */
    .text-pathao-brand {
        color: #E83434 !important;
    }
    .btn-pathao {
        background-color: #E83434 !important;
        border-color: #E83434 !important;
        color: #fff !important;
    }
    .btn-pathao:hover {
        background-color: #c92c2c !important;
        border-color: #c92c2c !important;
        color: #fff !important;
    }
    .border-pathao-tab {
        border-bottom-color: #E83434 !important;
    }
    .pathao-light-box {
        background-color: #FFF5F5 !important;
        border: 1px solid #FCD2D2 !important;
        color: #2D3748 !important;
    }
    .pathao-badge-active {
        background-color: rgba(232, 52, 52, 0.1) !important;
        color: #E83434 !important;
        border: 1px solid rgba(232, 52, 52, 0.3) !important;
    }
    .small-label {
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-weight: 700;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0">Pathao Courier</h4>
            <p class="text-muted">Manage Pathao Courier integration, API settings, and store configurations.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            @if(old('pathao_enabled', $settings->get('pathao_enabled')?->value) === '1')
                <span class="badge pathao-badge-active rounded-pill px-3 py-2">
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
    <ul class="nav nav-tabs mb-4 border-bottom-0" id="pathaoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-2 text-dark bg-transparent rounded-top border-bottom border-3 border-pathao-tab fw-semibold" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">
                <i class="bi bi-speedometer2 me-2 text-pathao-brand"></i> Dashboard
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 text-muted bg-transparent border-0" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">
                <i class="bi bi-gear me-2"></i> API Settings
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="pathaoTabsContent">
        
        <!-- DASHBOARD TAB -->
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border h-100 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="p-3 rounded me-3 text-pathao-brand" style="background-color: rgba(232, 52, 52, 0.1);">
                                <i class="bi bi-send-fill fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 text-uppercase small fw-bold">Sent to Pathao</h6>
                                <h3 class="mb-0 fw-bold">{{ $sentToCourierCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border h-100 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="p-3 rounded me-3 text-pathao-brand" style="background-color: rgba(232, 52, 52, 0.1);">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 text-uppercase small fw-bold">Pending Send</h6>
                                <h3 class="mb-0 fw-bold text-pathao-brand">{{ $pendingSendCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stores, Profile & Locations Settings -->
            <div class="row g-4 mb-4">
                <!-- Left Stack: Profile and Cache -->
                <div class="col-md-6 d-flex flex-column gap-4">
                    <!-- Merchant Profile Card -->
                    <div class="card border shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center"><i class="bi bi-person-badge text-pathao-brand me-2"></i>Merchant Profile</h6>
                            <form action="{{ route('admin.settings.couriers.pathao.test-connection') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-pathao text-white fw-semibold">
                                    <i class="bi bi-lightning-charge-fill me-1"></i>Test Connection
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            @if(!empty($merchantInfo) && !empty($merchantInfo['merchant_name']))
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">Merchant Name</small>
                                        <strong class="text-dark">{{ $merchantInfo['merchant_name'] }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">Merchant ID</small>
                                        <code class="text-dark">{{ $merchantInfo['merchant_id'] ?? 'N/A' }}</code>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">Email Address</small>
                                        <strong class="text-dark text-truncate d-block" title="{{ $merchantInfo['merchant_email'] ?? 'N/A' }}">{{ $merchantInfo['merchant_email'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">Contact Number</small>
                                        <strong class="text-dark">{{ $merchantInfo['merchant_contact_number'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="col-sm-12">
                                        <small class="text-muted d-block mb-1">Country</small>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-globe me-1 text-pathao-brand"></i>{{ ($merchantInfo['country_id'] ?? 1) == 1 ? 'Bangladesh' : 'Nepal' }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning py-3 mb-0 small text-dark" style="background-color: rgba(232, 52, 52, 0.05); border-color: rgba(232, 52, 52, 0.15);">
                                    <i class="bi bi-info-circle-fill text-pathao-brand me-2"></i>
                                    No merchant profile info cached. Click the <strong>Test Connection</strong> button above to verify API credentials and cache profile details.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Location Directory Cache Card -->
                    <div class="card border shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt text-pathao-brand me-2"></i>Location Directory Cache</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted lh-base">
                                Pathao requires precise City, Zone, and Area identifiers to process shipments. Run a local sync to download and cache the latest location datasets from Pathao API to enable location mapping dropdowns.
                            </p>
                            <div class="row g-2 text-center my-3">
                                <div class="col-4">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block small-label">Cities</small>
                                        <strong class="fs-5 text-dark">{{ DB::table('pathao_cities')->count() }}</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block small-label">Zones</small>
                                        <strong class="fs-5 text-dark">{{ DB::table('pathao_zones')->count() }}</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 border rounded bg-light">
                                        <small class="text-muted d-block small-label">Areas</small>
                                        <strong class="fs-5 text-dark">{{ DB::table('pathao_areas')->count() }}</strong>
                                    </div>
                                </div>
                            </div>
                            <form action="{{ route('admin.settings.couriers.pathao.sync') }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-pathao fw-semibold w-100" id="syncLocationsBtn">
                                    <i class="bi bi-arrow-repeat me-1"></i> Sync Pathao Locations
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Registered Stores -->
                <div class="col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center"><i class="bi bi-shop text-pathao-brand me-2"></i>Registered Stores</h6>
                            @if($connectionSuccess)
                                <button type="button" class="btn btn-sm text-white fw-semibold" style="background-color: #E83434;" data-bs-toggle="modal" data-bs-target="#createPathaoStoreModal">
                                    <i class="bi bi-plus-circle me-1"></i>New Store
                                </button>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($connectionSuccess && !empty($stores))
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Store Name</th>
                                                <th>Contact</th>
                                                <th>Address</th>
                                                <th>ID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stores as $st)
                                                <tr>
                                                    <td><strong>{{ $st['store_name'] ?? 'N/A' }}</strong></td>
                                                    <td>
                                                        <small class="d-block text-dark">{{ $st['contact_name'] ?? 'N/A' }}</small>
                                                        <small class="text-muted">{{ $st['contact_number'] ?? 'N/A' }}</small>
                                                    </td>
                                                    <td><small class="text-muted">{{ $st['store_address'] ?? 'N/A' }}</small></td>
                                                    <td><code>{{ $st['store_id'] ?? 'N/A' }}</code></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @elseif($connectionSuccess)
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-shop fs-2 d-block mb-2"></i>
                                    <small>No registered stores found in your Pathao account.</small>
                                </div>
                            @else
                                <div class="alert alert-warning py-3 mb-0 text-dark">
                                    <i class="bi bi-exclamation-triangle-fill text-pathao-brand me-2"></i>
                                    <strong>API Connection Status:</strong> {{ $connectionMessage ?: 'Unauthenticated. Please enter valid Client credentials in API Settings tab.' }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description & Guideline -->
            <div class="card border shadow-sm pathao-light-box">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text text-pathao-brand me-2"></i>Pathao Courier Setup Guideline</h6>
                    <ol class="text-dark mb-0 small lh-lg">
                        <li>Go to <strong>API Settings</strong> and enter your Pathao Client ID and Client Secret from Developer portal.</li>
                        <li>Toggle the switch to enable Pathao integration.</li>
                        <li>Click <strong>Save Changes</strong>. The application will authenticate and acquire credentials in the background.</li>
                        <li>Once authenticated, click <strong>Sync Pathao Locations</strong> to cache the city/zone lists.</li>
                        <li>Make sure to select your <strong>Default Store ID</strong> from the settings list.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- SETTINGS TAB -->
        <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
            <div class="row">
                <div class="col-md-8 col-lg-6">
                    <form action="{{ route('admin.settings.couriers.pathao.update') }}" method="POST">
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
                                <h6 class="mb-0 fw-semibold"><i class="bi bi-truck text-pathao-brand me-2"></i>Pathao Courier Credentials</h6>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="pathao_enabled" name="pathao_enabled" value="1" data-integration-toggle="pathao_enabled" {{ $isChecked('pathao_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="pathao_enabled">Enable</label>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div data-integration-form="pathao_enabled" class="{{ $isChecked('pathao_enabled') ? '' : 'd-none' }}">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="pathao_client_id">Client ID</label>
                                            <input type="text" class="form-control @error('pathao_client_id') is-invalid @enderror" id="pathao_client_id" name="pathao_client_id" value="{{ $valueOf('pathao_client_id') }}" placeholder="Enter Pathao Client ID">
                                            @error('pathao_client_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="pathao_client_secret">Client Secret</label>
                                            <input type="text" class="form-control @error('pathao_client_secret') is-invalid @enderror" id="pathao_client_secret" name="pathao_client_secret" value="{{ $valueOf('pathao_client_secret') }}" placeholder="Enter Pathao Client Secret">
                                            @error('pathao_client_secret')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold" for="pathao_webhook_integration_secret">Webhook Integration Secret</label>
                                            <input type="text" class="form-control @error('pathao_webhook_integration_secret') is-invalid @enderror" id="pathao_webhook_integration_secret" name="pathao_webhook_integration_secret" value="{{ $valueOf('pathao_webhook_integration_secret') }}" placeholder="Provide the exact UUID from Pathao dashboard">
                                            @error('pathao_webhook_integration_secret')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Challenge token UUID from Pathao Dashboard for webhook URL verification.</div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="pathao_secret_token">Webhook Signature Token</label>
                                            <input type="text" class="form-control @error('pathao_secret_token') is-invalid @enderror" id="pathao_secret_token" name="pathao_secret_token" value="{{ $valueOf('pathao_secret_token') }}" placeholder="Secure random string">
                                            @error('pathao_secret_token')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Secure string to validate signatures. Supply this token as signature verification header in Pathao developers console.</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" for="pathao_store_id">Default Store</label>
                                            <select class="form-select @error('pathao_store_id') is-invalid @enderror" id="pathao_store_id" name="pathao_store_id">
                                                <option value="">-- Select Default Store --</option>
                                                @if(!empty($stores))
                                                    @foreach($stores as $st)
                                                        <option value="{{ $st['store_id'] }}" {{ $valueOf('pathao_store_id') == $st['store_id'] ? 'selected' : '' }}>
                                                            {{ $st['store_name'] ?? 'Store ID: ' . $st['store_id'] }}
                                                        </option>
                                                    @endforeach
                                                @elseif($valueOf('pathao_store_id'))
                                                    <option value="{{ $valueOf('pathao_store_id') }}" selected>{{ 'Store ID: ' . $valueOf('pathao_store_id') }}</option>
                                                @endif
                                            </select>
                                            @error('pathao_store_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Choose default warehouse/store address for bulk pickup dispatches.</div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-2">
                                        <div class="col-md-12">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" id="pathao_sandbox" name="pathao_sandbox" value="1" {{ $isChecked('pathao_sandbox') ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="pathao_sandbox">Enable Sandbox Mode</label>
                                                <div class="form-text mt-0">Connect to Pathao Test API endpoints rather than production environment.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 p-3 rounded pathao-light-box">
                                        <label class="form-label fw-bold mb-1"><i class="bi bi-link-45deg text-pathao-brand me-1"></i>Callback / Webhook URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm bg-white text-dark" value="{{ url('/api/pathao/webhook') }}" readonly id="pathaoWebhookUrl">
                                            <button class="btn btn-sm btn-pathao" type="button" onclick="navigator.clipboard.writeText(document.getElementById('pathaoWebhookUrl').value); alert('Copied to clipboard!');">
                                                <i class="bi bi-clipboard"></i> Copy
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-2">Enter this callback endpoint and the Webhook Token above inside Pathao developers dashboard to receive order updates.</small>
                                    </div>
                                </div>

                                <div class="text-muted {{ $isChecked('pathao_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="pathao_enabled">
                                    <div class="d-flex align-items-center p-3 bg-light rounded">
                                        <i class="bi bi-info-circle me-3 fs-4"></i>
                                        <p class="mb-0 small">Pathao Courier integration is disabled. Toggle Enable switcher to access API credential settings.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-start">
                            <button type="submit" class="btn btn-pathao px-4">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Store Modal -->
<div class="modal fade" id="createPathaoStoreModal" tabindex="-1" aria-labelledby="createPathaoStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.settings.couriers.pathao.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPathaoStoreModalLabel">Create New Pathao Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="store_name">Store Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="store_name" name="name" required placeholder="e.g. Dhaka Branch">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_name">Contact Person Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" required placeholder="e.g. John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="contact_number">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" required placeholder="e.g. 01712345678">
                        <div class="form-text">Valid format: 01XXX...</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="address">Full Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="address" name="address" required rows="2" placeholder="House 1, Road 2, Block B"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="city_id">City <span class="text-danger">*</span></label>
                        <select class="form-select" id="city_id" name="city_id" required>
                            <option value="">-- Select City --</option>
                            @foreach(\Illuminate\Support\Facades\DB::table('pathao_cities')->orderBy('name')->get() as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="zone_id">Zone <span class="text-danger">*</span></label>
                        <select class="form-select" id="zone_id" name="zone_id" required disabled>
                            <option value="">-- Select Zone --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="area_id">Area <span class="text-danger">*</span></label>
                        <select class="form-select" id="area_id" name="area_id" required disabled>
                            <option value="">-- Select Area --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background-color: #E83434;">Create Store</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab styling toggles
        const tabs = document.querySelectorAll('#pathaoTabs .nav-link');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                tabs.forEach(t => {
                    t.classList.remove('text-dark', 'border-bottom', 'border-3', 'border-pathao-tab', 'fw-semibold');
                    t.classList.add('text-muted', 'border-0');
                    const icon = t.querySelector('i');
                    if(icon) icon.classList.remove('text-pathao-brand');
                });
                event.target.classList.remove('text-muted', 'border-0');
                event.target.classList.add('text-dark', 'border-bottom', 'border-3', 'border-pathao-tab', 'fw-semibold');
                const activeIcon = event.target.querySelector('i');
                if(activeIcon) activeIcon.classList.add('text-pathao-brand');
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

        // Sync button loading feedback
        const syncForm = document.querySelector('form[action="{{ route("admin.settings.couriers.pathao.sync") }}"]');
        const syncLocationsBtn = document.getElementById('syncLocationsBtn');
        if (syncForm && syncLocationsBtn) {
            syncForm.addEventListener('submit', function() {
                syncLocationsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Syncing Locations...';
                syncLocationsBtn.disabled = true;
            });
        }

        // Location dropdowns logic
        const citySelect = document.getElementById('city_id');
        const zoneSelect = document.getElementById('zone_id');
        const areaSelect = document.getElementById('area_id');
        
        if (citySelect) {
            citySelect.addEventListener('change', function() {
                const cityId = this.value;
                zoneSelect.innerHTML = '<option value="">-- Select Zone --</option>';
                areaSelect.innerHTML = '<option value="">-- Select Area --</option>';
                zoneSelect.disabled = true;
                areaSelect.disabled = true;

                if(cityId) {
                    fetch(`{{ route('admin.pathao.zones') }}?city_id=${cityId}`)
                        .then(r => r.json())
                        .then(data => {
                            let options = '<option value="">-- Select Zone --</option>';
                            let zones = data.data && data.data.data !== undefined ? data.data.data : data.data; 
                            if(Array.isArray(zones)) {
                                zones.forEach(z => {
                                    options += `<option value="${z.zone_id || z.id}">${z.zone_name || z.name}</option>`;
                                });
                            }
                            zoneSelect.innerHTML = options;
                            zoneSelect.disabled = false;
                        });
                }
            });
        }

        if (zoneSelect) {
            zoneSelect.addEventListener('change', function() {
                const zoneId = this.value;
                areaSelect.innerHTML = '<option value="">-- Select Area --</option>';
                areaSelect.disabled = true;

                if(zoneId) {
                    fetch(`{{ route('admin.pathao.areas') }}?zone_id=${zoneId}`)
                        .then(r => r.json())
                        .then(data => {
                            let options = '<option value="">-- Select Area --</option>';
                            let areas = data.data && data.data.data !== undefined ? data.data.data : data.data; 
                            if(Array.isArray(areas)) {
                                areas.forEach(a => {
                                    options += `<option value="${a.area_id || a.id}">${a.area_name || a.name}</option>`;
                                });
                            }
                            areaSelect.innerHTML = options;
                            areaSelect.disabled = false;
                        });
                }
            });
        }
    });
</script>
@endpush
