@extends('admin.layouts.app')

@section('title', 'Courier Checker')
@section('page-title', 'Courier Checker')

@section('content')
<style>
    .text-courierchecker-brand { color: #4F46E5 !important; }
    .btn-courierchecker { background-color: #4F46E5 !important; border-color: #4F46E5 !important; color: #fff !important; }
    .btn-courierchecker:hover { background-color: #4338CA !important; border-color: #4338CA !important; color: #fff !important; }
    .border-courierchecker-tab { border-bottom-color: #4F46E5 !important; }
    .courierchecker-badge-active { background-color: rgba(79, 70, 229, 0.1) !important; color: #4F46E5 !important; border: 1px solid rgba(79, 70, 229, 0.3) !important; }
    .courierchecker-stat-icon { width: 48px; height: 48px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; }
    .courier-source-icon { width: 34px; height: 34px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    #courierSearchResultBody .table { margin-bottom: 0; }
</style>

@php
    $settings = $credentials ?? collect();
    $valueOf = function($key) use ($settings) {
        return old($key, $settings->get($key)?->value);
    };
    $isChecked = function($key) use ($settings) {
        return old($key, $settings->get($key)?->value) === '1';
    };
    $hasSavedValue = function($key) use ($settings) {
        return old($key) === null && filled($settings->get($key)?->value);
    };
    $couriers = [
        ['key' => 'pathao', 'label' => 'Pathao', 'icon' => 'bi-send-dash', 'color' => '#E83434', 'idField' => 'users', 'idLabel' => 'Users (email)'],
        ['key' => 'steadfast', 'label' => 'Steadfast', 'icon' => 'bi-truck', 'color' => '#00B795', 'idField' => 'users', 'idLabel' => 'Users (email)'],
        ['key' => 'redx', 'label' => 'RedX', 'icon' => 'bi-box-seam', 'color' => '#DC2626', 'idField' => 'phones', 'idLabel' => 'Phones'],
        ['key' => 'paperfly', 'label' => 'Paperfly', 'icon' => 'bi-envelope-paper', 'color' => '#2563EB', 'idField' => 'users', 'idLabel' => 'Users'],
        ['key' => 'carrybee', 'label' => 'Carrybee', 'icon' => 'bi-bicycle', 'color' => '#F59E0B', 'idField' => 'phones', 'idLabel' => 'Phones'],
    ];
    $activeTab = request()->query('tab', 'search') === 'settings' ? 'settings' : 'search';
@endphp

<div class="row mb-4 align-items-center">
    <div class="col-md-7">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-courierchecker-brand" style="width: 44px; height: 44px; background-color: rgba(79, 70, 229, 0.1);">
                <i class="bi bi-shield-check fs-4"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold">Courier Checker</h5>
                <p class="text-muted mb-0 small">Cross-courier delivery/cancel history — Steadfast, Pathao, RedX, Paperfly, Carrybee</p>
            </div>
        </div>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0">
        @if($stats['configured'])
            <span class="badge courierchecker-badge-active rounded-pill px-3 py-2">
                <i class="bi bi-circle-fill small me-1"></i> Credentials configured
            </span>
        @else
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-3 py-2">
                <i class="bi bi-circle-fill small me-1"></i> Not configured
            </span>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="courierchecker-stat-icon text-courierchecker-brand" style="background-color: rgba(79, 70, 229, 0.1);">
                    <i class="bi bi-search fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 text-uppercase small fw-bold">Total Checks</h6>
                    <h3 class="mb-0 fw-bold">{{ $stats['total_checks'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="courierchecker-stat-icon text-danger" style="background-color: rgba(220, 38, 38, 0.1);">
                    <i class="bi bi-flag-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 text-uppercase small fw-bold">Flagged / Blocked</h6>
                    <h3 class="mb-0 fw-bold">{{ $stats['flagged'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="courierchecker-stat-icon {{ $automation['courier_check_enabled'] ? 'text-success' : 'text-secondary' }}" style="background-color: {{ $automation['courier_check_enabled'] ? 'rgba(22, 163, 74, 0.1)' : 'rgba(100, 116, 139, 0.1)' }};">
                    <i class="bi bi-robot fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 text-uppercase small fw-bold">Automatic Checks</h6>
                    <h5 class="mb-0 fw-bold">{{ $automation['courier_check_enabled'] ? 'Enabled' : 'Disabled' }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <ul class="nav nav-tabs border-bottom mb-4" id="courierCheckerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'search' ? 'active' : '' }} fw-semibold" id="cc-search-tab-btn" data-bs-toggle="tab" data-bs-target="#cc-search-tab" type="button" role="tab" onclick="updateCourierCheckerTab('search')">
                    <i class="bi bi-search me-2"></i>Search
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }} fw-semibold" id="cc-settings-tab-btn" data-bs-toggle="tab" data-bs-target="#cc-settings-tab" type="button" role="tab" onclick="updateCourierCheckerTab('settings')">
                    <i class="bi bi-gear me-2"></i>Settings
                </button>
            </li>
        </ul>

        <div class="tab-content" id="courierCheckerTabsContent">
            {{-- SEARCH TAB --}}
            <div class="tab-pane fade {{ $activeTab === 'search' ? 'show active' : '' }}" id="cc-search-tab" role="tabpanel">
                @unless($stats['configured'])
                    <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
                        <div><i class="bi bi-exclamation-triangle-fill me-2"></i>No courier credentials are configured yet — searches below won't return results.</div>
                        <button type="button" class="btn btn-sm btn-warning" onclick="updateCourierCheckerTab('settings')">Configure Now</button>
                    </div>
                @endunless

                <div class="row g-4">
                    <div class="col-lg-7">
                        <h6 class="fw-bold mb-3"><i class="bi bi-search text-courierchecker-brand me-2"></i>Search by Phone Number</h6>
                        <form id="courierSearchForm" class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="courierSearchPhone" placeholder="01712345678" required>
                                <button type="submit" class="btn btn-courierchecker px-4" id="courierSearchBtn">
                                    <i class="bi bi-search me-1"></i> Check
                                </button>
                                <button type="button" class="btn btn-outline-secondary px-3" id="courierSearchRefreshBtn" title="Bypass the 6-hour cache and check live">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>
                            <div class="form-text">Local 11-digit format, e.g. 01712345678 — results are cached for 6 hours; use the refresh button for a live re-check.</div>
                        </form>
                        <div id="courierSearchResultBody" class="border rounded p-3">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0 small">Enter a phone number above to check its delivery history.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock-history text-courierchecker-brand me-2"></i>Recent Checks</span>
                            <span class="badge bg-light text-dark border">{{ $recentChecks->count() }}</span>
                        </h6>
                        <div class="border rounded">
                            @if($recentChecks->isEmpty())
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    <p class="mb-0 small">No checks performed yet.</p>
                                </div>
                            @else
                                <div class="list-group list-group-flush">
                                    @foreach($recentChecks as $check)
                                        @php
                                            $ratio = $check->cancelRatio();
                                            $badgeColor = $ratio >= 40 ? 'danger' : ($ratio >= 20 ? 'warning' : 'success');
                                        @endphp
                                        <button type="button" class="list-group-item list-group-item-action recent-check-item d-flex justify-content-between align-items-center py-3" data-url="{{ route('admin.orders.courier-checker.show', $check) }}" data-phone="{{ $check->normalized_phone }}">
                                            <div class="text-start">
                                                <code class="fw-semibold">{{ $check->normalized_phone }}</code>
                                                <br><small class="text-muted">{{ $check->checked_at?->diffForHumans() }}</small>
                                            </div>
                                            <span class="badge bg-{{ $badgeColor }} bg-opacity-10 text-{{ $badgeColor }} px-2 py-2">
                                                {{ $check->total_deliveries }} · {{ $ratio }}% cancel
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- SETTINGS TAB --}}
            <div class="tab-pane fade {{ $activeTab === 'settings' ? 'show active' : '' }}" id="cc-settings-tab" role="tabpanel">
                {{-- Automation --}}
                <form action="{{ route('admin.orders.courier-checker.automation') }}" method="POST" class="mb-4">
                    @csrf
                    @method('PUT')
                    <div class="card border shadow-sm">
                        <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-robot text-courierchecker-brand me-2"></i>Automatic Checking</h6>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="courier_check_enabled" name="courier_check_enabled" value="1" {{ $automation['courier_check_enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="courier_check_enabled">Enable</label>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small">
                                When enabled, every new order is checked in the background against these thresholds; if
                                crossed, the phone is auto-flagged in
                                <a href="{{ route('admin.fraud-blocks.index') }}">Fraud Blocker</a> for review (or
                                auto-blocked, per the action below).
                            </p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Min. deliveries</label>
                                    <input type="number" min="1" max="100" class="form-control" name="courier_check_min_orders" value="{{ $automation['courier_check_min_orders'] }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Max cancel %</label>
                                    <input type="number" min="0" max="100" step="1" class="form-control" name="courier_check_max_cancel_ratio" value="{{ $automation['courier_check_max_cancel_ratio'] }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Re-check after (days)</label>
                                    <input type="number" min="1" max="365" class="form-control" name="courier_check_freshness_days" value="{{ $automation['courier_check_freshness_days'] }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Action</label>
                                    <select class="form-select" name="courier_check_action">
                                        <option value="flag" {{ $automation['courier_check_action'] === 'flag' ? 'selected' : '' }}>Flag for review</option>
                                        <option value="auto_block" {{ $automation['courier_check_action'] === 'auto_block' ? 'selected' : '' }}>Auto-block immediately</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top p-3 text-end">
                            <button type="submit" class="btn btn-courierchecker px-4">
                                <i class="bi bi-save me-1"></i> Save Automation
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Credentials --}}
                <form action="{{ route('admin.orders.courier-checker.credentials') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card border shadow-sm">
                        <div class="card-header bg-white border-bottom p-4">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-key text-courierchecker-brand me-2"></i>Merchant Credentials</h6>
                            <p class="text-muted small mb-0 mt-1">
                                Multiple accounts per courier are supported — list one per line, keeping the users/phones
                                list and passwords list in the same order. For security, saved passwords are never shown
                                again — leave a password field blank to keep what's already saved.
                            </p>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                @foreach($couriers as $courier)
                                    @php $prefix = $courier['key']; $idKey = $prefix . '_' . $courier['idField']; $pwKey = $prefix . '_passwords'; @endphp
                                    <div class="col-md-6 col-xl-4">
                                        <div class="card border h-100">
                                            <div class="card-header bg-light border-bottom p-3 d-flex align-items-center gap-2">
                                                <div class="courier-source-icon" style="background-color: {{ $courier['color'] }}1a; color: {{ $courier['color'] }};">
                                                    <i class="bi {{ $courier['icon'] }}"></i>
                                                </div>
                                                <h6 class="mb-0 fw-semibold">{{ $courier['label'] }}</h6>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold" for="{{ $idKey }}">{{ $courier['idLabel'] }}</label>
                                                    <textarea class="form-control form-control-sm @error($idKey) is-invalid @enderror" id="{{ $idKey }}" name="{{ $idKey }}" rows="2" placeholder="one per line">{{ $valueOf($idKey) }}</textarea>
                                                    @error($idKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1" for="{{ $pwKey }}">
                                                        Passwords
                                                        @if($hasSavedValue($pwKey))
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size: 0.65rem;"><i class="bi bi-lock-fill me-1"></i>Saved</span>
                                                        @endif
                                                    </label>
                                                    <textarea class="form-control form-control-sm font-monospace @error($pwKey) is-invalid @enderror" id="{{ $pwKey }}" name="{{ $pwKey }}" rows="2" placeholder="{{ $hasSavedValue($pwKey) ? '•••••••• (leave blank to keep saved passwords)' : 'same order as above' }}" autocomplete="new-password">{{ old($pwKey) }}</textarea>
                                                    @error($pwKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="proxy_{{ $prefix }}" name="proxy_{{ $prefix }}" value="1" {{ $isChecked('proxy_'.$prefix) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="proxy_{{ $prefix }}">Route through proxy</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="col-12">
                                    <div class="card border bg-light bg-opacity-50">
                                        <div class="card-body p-3">
                                            <h6 class="fw-semibold mb-3 small text-uppercase text-muted"><i class="bi bi-hdd-network me-1"></i>Proxy</h6>
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-4">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="proxy_all" name="proxy_all" value="1" {{ $isChecked('proxy_all') ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-semibold small" for="proxy_all">Route all couriers through proxy</label>
                                                    </div>
                                                    <div class="form-text">Overrides the per-courier toggles above.</div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label small fw-semibold" for="proxy_address">Proxy address</label>
                                                    <input type="text" class="form-control form-control-sm @error('proxy_address') is-invalid @enderror" id="proxy_address" name="proxy_address" value="{{ $valueOf('proxy_address') }}" placeholder="http://127.0.0.1:8080">
                                                    @error('proxy_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top p-3 text-end">
                            <button type="submit" class="btn btn-courierchecker px-4">
                                <i class="bi bi-save me-1"></i> Save Credentials
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateCourierCheckerTab(tab) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);

        const btn = document.getElementById('cc-' + tab + '-tab-btn');
        if (btn && typeof bootstrap !== 'undefined') {
            bootstrap.Tab.getOrCreateInstance(btn).show();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('courierSearchForm');
        const btn = document.getElementById('courierSearchBtn');
        const refreshBtn = document.getElementById('courierSearchRefreshBtn');
        const phoneInput = document.getElementById('courierSearchPhone');
        const body = document.getElementById('courierSearchResultBody');
        const emptyStateHtml = body.innerHTML;

        function runSearch(forceRefresh) {
            const phone = phoneInput.value.trim();
            if (!phone) {
                phoneInput.focus();
                return;
            }

            const originalBtnHtml = btn.innerHTML;
            const originalRefreshHtml = refreshBtn ? refreshBtn.innerHTML : null;
            btn.disabled = true;
            if (refreshBtn) refreshBtn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (forceRefresh ? 'Refreshing...' : 'Checking...');
            body.innerHTML = '<div class="d-flex align-items-center text-muted small py-4 justify-content-center"><span class="spinner-border spinner-border-sm me-2"></span>' + (forceRefresh ? 'Bypassing cache — contacting couriers live, this can take up to 30 seconds...' : 'Checking cache (falls back to a live check if none within 6 hours)...') + '</div>';

            fetch('{{ route("admin.orders.courier-checker.search") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ phone: phone, refresh: forceRefresh }),
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                btn.disabled = false;
                if (refreshBtn) refreshBtn.disabled = false;
                btn.innerHTML = originalBtnHtml;
                if (refreshBtn) refreshBtn.innerHTML = originalRefreshHtml;

                if (ok && data.success) {
                    body.innerHTML = data.html;
                    showAdminToast(data.from_cache ? 'Showing cached courier history.' : 'Courier history retrieved.', 'success');
                } else {
                    body.innerHTML = emptyStateHtml;
                    showAdminToast(data.message || 'Failed to check courier history.', 'danger');
                }
            })
            .catch(function() {
                btn.disabled = false;
                if (refreshBtn) refreshBtn.disabled = false;
                btn.innerHTML = originalBtnHtml;
                if (refreshBtn) refreshBtn.innerHTML = originalRefreshHtml;
                body.innerHTML = emptyStateHtml;
                showAdminToast('Network error occurred.', 'danger');
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                runSearch(false);
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                runSearch(true);
            });
        }

        // Recent Checks — click an entry to view its cached result (no live
        // courier calls) in the same result panel used for a fresh search.
        document.querySelectorAll('.recent-check-item').forEach(function(item) {
            item.addEventListener('click', function() {
                const url = item.dataset.url;
                const phone = item.dataset.phone;

                document.querySelectorAll('.recent-check-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                if (phoneInput) phoneInput.value = phone;

                body.innerHTML = '<div class="d-flex align-items-center text-muted small py-4 justify-content-center"><span class="spinner-border spinner-border-sm me-2"></span>Loading cached result...</div>';
                body.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        body.innerHTML = '<div class="alert alert-secondary py-2 small mb-3"><i class="bi bi-clock-history me-1"></i>Showing a cached result — click "Check" above for a live re-check.</div>' + data.html;
                    } else {
                        body.innerHTML = emptyStateHtml;
                        showAdminToast(data.message || 'Failed to load cached result.', 'danger');
                    }
                })
                .catch(function() {
                    body.innerHTML = emptyStateHtml;
                    showAdminToast('Network error occurred.', 'danger');
                });
            });
        });
    });
</script>
@endpush
@endsection
