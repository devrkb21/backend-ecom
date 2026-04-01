@extends('admin.layouts.app')

@section('title', 'Integrations')
@section('page-title', 'Integrations')

@section('content')
@php
    $settings = $settings ?? collect();

    $valueOf = function (string $key, string $default = '') use ($settings) {
        return old($key, data_get($settings, $key . '.value', $default));
    };

    $isChecked = function (string $key) use ($valueOf) {
        return filter_var($valueOf($key, '0'), FILTER_VALIDATE_BOOLEAN);
    };
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <form action="{{ route('admin.settings.integrations.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-activity me-2"></i>Tracking Integrations</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="gtm_container_id">Google Tag Manager</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="gtm_enabled" name="gtm_enabled" value="1" {{ $isChecked('gtm_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="gtm_enabled">Enable</label>
                            </div>
                        </div>
                        <input type="text" class="form-control @error('gtm_container_id') is-invalid @enderror" id="gtm_container_id" name="gtm_container_id" value="{{ $valueOf('gtm_container_id') }}" placeholder="GTM-XXXXXXX">
                        @error('gtm_container_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="google_analytics_measurement_id">Google Analytics (GA4)</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="google_analytics_enabled" name="google_analytics_enabled" value="1" {{ $isChecked('google_analytics_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="google_analytics_enabled">Enable</label>
                            </div>
                        </div>
                        <input type="text" class="form-control @error('google_analytics_measurement_id') is-invalid @enderror" id="google_analytics_measurement_id" name="google_analytics_measurement_id" value="{{ $valueOf('google_analytics_measurement_id') }}" placeholder="G-XXXXXXXXXX">
                        @error('google_analytics_measurement_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="facebook_pixel_id">Facebook Pixel</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="facebook_pixel_enabled" name="facebook_pixel_enabled" value="1" {{ $isChecked('facebook_pixel_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="facebook_pixel_enabled">Enable</label>
                            </div>
                        </div>
                        <input type="text" class="form-control @error('facebook_pixel_id') is-invalid @enderror" id="facebook_pixel_id" name="facebook_pixel_id" value="{{ $valueOf('facebook_pixel_id') }}" placeholder="123456789012345">
                        @error('facebook_pixel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="tiktok_pixel_id">TikTok Pixel</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="tiktok_pixel_enabled" name="tiktok_pixel_enabled" value="1" {{ $isChecked('tiktok_pixel_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="tiktok_pixel_enabled">Enable</label>
                            </div>
                        </div>
                        <input type="text" class="form-control @error('tiktok_pixel_id') is-invalid @enderror" id="tiktok_pixel_id" name="tiktok_pixel_id" value="{{ $valueOf('tiktok_pixel_id') }}" placeholder="C123ABCDEF12345">
                        @error('tiktok_pixel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-square-text me-2"></i>SMS API Integration</h6>
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-outline-info btn-sm" id="getSmsBalanceBtn" data-url="{{ route('admin.settings.integrations.sms-balance') }}">
                            <span class="balance-btn-label"><i class="bi bi-wallet2 me-1"></i> Get Balance</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" value="1" {{ $isChecked('sms_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label" for="sms_enabled">Enable</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert d-none" id="smsBalanceAlert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sms_provider">Provider Name</label>
                            <input type="text" class="form-control @error('sms_provider') is-invalid @enderror" id="sms_provider" name="sms_provider" value="{{ $valueOf('sms_provider') }}" placeholder="BulkSMSBD">
                            @error('sms_provider')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="sms_api_base_url">SMS Send API URL</label>
                            <input type="url" class="form-control @error('sms_api_base_url') is-invalid @enderror" id="sms_api_base_url" name="sms_api_base_url" value="{{ $valueOf('sms_api_base_url') }}" placeholder="https://api.provider.com/v1/send">
                            @error('sms_api_base_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sms_api_key">API Key</label>
                            <input type="text" class="form-control @error('sms_api_key') is-invalid @enderror" id="sms_api_key" name="sms_api_key" value="{{ $valueOf('sms_api_key') }}" placeholder="Enter API key">
                            @error('sms_api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sms_sender_id">Sender ID</label>
                            <input type="text" class="form-control @error('sms_sender_id') is-invalid @enderror" id="sms_sender_id" name="sms_sender_id" value="{{ $valueOf('sms_sender_id') }}" placeholder="8809617XXXXXX">
                            @error('sms_sender_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="sms_balance_url">Balance API URL</label>
                            <input type="url" class="form-control @error('sms_balance_url') is-invalid @enderror" id="sms_balance_url" name="sms_balance_url" value="{{ $valueOf('sms_balance_url') }}" placeholder="http://www.bulksmsbd.net/api/getBalanceApi">
                            @error('sms_balance_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>OTP Format:</strong> Your {Brand/Company Name} OTP is XXXX
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Integration Settings
                </button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-2"></i>Security Note</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-0">
                    SMS API credentials are stored as private settings and not exposed in public settings APIs. Use only production credentials in live environment.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-lightning-charge me-2"></i>Examples</h6>
            </div>
            <div class="card-body">
                <ul class="small text-muted mb-0 ps-3">
                    <li>GTM: GTM-XXXXXXX</li>
                    <li>GA4: G-XXXXXXXXXX</li>
                    <li>Facebook Pixel: numeric ID</li>
                    <li>TikTok Pixel: alphanumeric ID</li>
                    <li>BulkSMSBD Send URL: http://www.bulksmsbd.net/api/smsapi</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const getBalanceBtn = document.getElementById('getSmsBalanceBtn');
    const balanceAlert = document.getElementById('smsBalanceAlert');

    if (!getBalanceBtn || !balanceAlert) {
        return;
    }

    const label = getBalanceBtn.querySelector('.balance-btn-label');
    const spinner = getBalanceBtn.querySelector('.spinner-border');

    const setLoading = (loading) => {
        getBalanceBtn.disabled = loading;
        spinner?.classList.toggle('d-none', !loading);
        label?.classList.toggle('d-none', loading);
    };

    const showAlert = (success, message) => {
        balanceAlert.classList.remove('d-none', 'alert-success', 'alert-danger');
        balanceAlert.classList.add(success ? 'alert-success' : 'alert-danger');
        balanceAlert.textContent = message;
    };

    getBalanceBtn.addEventListener('click', async function () {
        const url = getBalanceBtn.dataset.url;
        if (!url) {
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();

            if (response.ok && payload.success) {
                const balanceText = payload.balance !== null && payload.balance !== undefined
                    ? `Current SMS Balance: ${payload.balance}`
                    : 'Balance fetched successfully.';
                showAlert(true, balanceText);
                return;
            }

            showAlert(false, payload.message || 'Failed to fetch SMS balance.');
        } catch (error) {
            showAlert(false, 'Network error while fetching SMS balance.');
        } finally {
            setLoading(false);
        }
    });
});
</script>
@endpush
