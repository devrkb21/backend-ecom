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

    $siteVerificationEntries = old('site_verification_entries');

    if (!is_array($siteVerificationEntries)) {
        $storedSiteVerificationEntries = data_get($settings, 'site_verification_entries.value', '[]');

        if (is_array($storedSiteVerificationEntries)) {
            $siteVerificationEntries = $storedSiteVerificationEntries;
        } else {
            $decodedSiteVerificationEntries = json_decode((string) $storedSiteVerificationEntries, true);
            $siteVerificationEntries = is_array($decodedSiteVerificationEntries) ? $decodedSiteVerificationEntries : [];
        }
    }

    $siteVerificationEntries = collect($siteVerificationEntries)
        ->filter(fn ($entry) => is_array($entry))
        ->map(function (array $entry) {
            return [
                'provider' => strtolower(trim((string) ($entry['provider'] ?? 'google'))),
                'code' => trim((string) ($entry['code'] ?? '')),
                'meta_name' => trim((string) ($entry['meta_name'] ?? '')),
            ];
        })
        ->values()
        ->all();

    $verificationProviderOptions = [
        'google' => 'Google Search Console',
        'bing' => 'Bing Webmaster',
        'yandex' => 'Yandex Webmaster',
        'pinterest' => 'Pinterest',
        'facebook' => 'Facebook Domain Verification',
        'custom' => 'Custom Meta Name',
    ];
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
                                <input class="form-check-input" type="checkbox" id="gtm_enabled" name="gtm_enabled" value="1" data-integration-toggle="gtm_enabled" {{ $isChecked('gtm_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="gtm_enabled">Enable</label>
                            </div>
                        </div>
                        <div data-integration-form="gtm_enabled" class="{{ $isChecked('gtm_enabled') ? '' : 'd-none' }}">
                            <input type="text" class="form-control @error('gtm_container_id') is-invalid @enderror" id="gtm_container_id" name="gtm_container_id" value="{{ $valueOf('gtm_container_id') }}" placeholder="GTM-XXXXXXX">
                            @error('gtm_container_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="small text-muted mt-2 {{ $isChecked('gtm_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="gtm_enabled">
                            Disabled. Enable this integration to open its configuration form.
                        </div>
                    </div>

                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="google_analytics_measurement_id">Google Analytics (GA4)</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="google_analytics_enabled" name="google_analytics_enabled" value="1" data-integration-toggle="google_analytics_enabled" {{ $isChecked('google_analytics_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="google_analytics_enabled">Enable</label>
                            </div>
                        </div>
                        <div data-integration-form="google_analytics_enabled" class="{{ $isChecked('google_analytics_enabled') ? '' : 'd-none' }}">
                            <input type="text" class="form-control @error('google_analytics_measurement_id') is-invalid @enderror" id="google_analytics_measurement_id" name="google_analytics_measurement_id" value="{{ $valueOf('google_analytics_measurement_id') }}" placeholder="G-XXXXXXXXXX">
                            @error('google_analytics_measurement_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="small text-muted mt-2 {{ $isChecked('google_analytics_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="google_analytics_enabled">
                            Disabled. Enable this integration to open its configuration form.
                        </div>
                    </div>

                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="facebook_pixel_id">Facebook Pixel</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="facebook_pixel_enabled" name="facebook_pixel_enabled" value="1" data-integration-toggle="facebook_pixel_enabled" {{ $isChecked('facebook_pixel_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="facebook_pixel_enabled">Enable</label>
                            </div>
                        </div>
                        <div data-integration-form="facebook_pixel_enabled" class="{{ $isChecked('facebook_pixel_enabled') ? '' : 'd-none' }}">
                            <input type="text" class="form-control @error('facebook_pixel_id') is-invalid @enderror" id="facebook_pixel_id" name="facebook_pixel_id" value="{{ $valueOf('facebook_pixel_id') }}" placeholder="123456789012345">
                            @error('facebook_pixel_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="small text-muted mt-2 {{ $isChecked('facebook_pixel_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="facebook_pixel_enabled">
                            Disabled. Enable this integration to open its configuration form.
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0" for="tiktok_pixel_id">TikTok Pixel</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="tiktok_pixel_enabled" name="tiktok_pixel_enabled" value="1" data-integration-toggle="tiktok_pixel_enabled" {{ $isChecked('tiktok_pixel_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="tiktok_pixel_enabled">Enable</label>
                            </div>
                        </div>
                        <div data-integration-form="tiktok_pixel_enabled" class="{{ $isChecked('tiktok_pixel_enabled') ? '' : 'd-none' }}">
                            <input type="text" class="form-control @error('tiktok_pixel_id') is-invalid @enderror" id="tiktok_pixel_id" name="tiktok_pixel_id" value="{{ $valueOf('tiktok_pixel_id') }}" placeholder="C123ABCDEF12345">
                            @error('tiktok_pixel_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="small text-muted mt-2 {{ $isChecked('tiktok_pixel_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="tiktok_pixel_enabled">
                            Disabled. Enable this integration to open its configuration form.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-patch-check me-2"></i>Site Verification (Header Meta)</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addSiteVerificationEntryBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Code
                    </button>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-3">
                        Add short verification codes from Google or other platforms. Each saved code is rendered as a meta tag in the frontend header.
                    </div>

                    <div id="siteVerificationEntries">
                        @foreach($siteVerificationEntries as $index => $entry)
                            @php
                                $provider = $entry['provider'] ?? 'google';
                                $code = $entry['code'] ?? '';
                                $metaName = $entry['meta_name'] ?? '';
                                $showMetaName = $provider === 'custom';
                            @endphp
                            <div class="border rounded p-3 mb-3" data-site-verification-row>
                                <div class="row g-3 align-items-start">
                                    <div class="col-md-4">
                                        <label class="form-label">Platform</label>
                                        <select class="form-select" data-verification-field="provider" name="site_verification_entries[{{ $index }}][provider]">
                                            @foreach($verificationProviderOptions as $providerKey => $providerLabel)
                                                <option value="{{ $providerKey }}" {{ $provider === $providerKey ? 'selected' : '' }}>{{ $providerLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Short Code</label>
                                        <input type="text" class="form-control" data-verification-field="code" name="site_verification_entries[{{ $index }}][code]" value="{{ $code }}" placeholder="Paste verification code">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger w-100" data-remove-site-verification-row>
                                            <i class="bi bi-trash me-1"></i> Remove
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1 {{ $showMetaName ? '' : 'd-none' }}" data-custom-meta-wrapper>
                                    <div class="col-md-6">
                                        <label class="form-label">Custom Meta Name</label>
                                        <input type="text" class="form-control" data-verification-field="meta_name" name="site_verification_entries[{{ $index }}][meta_name]" value="{{ $metaName }}" placeholder="example-verification-name">
                                        <div class="form-text">Required only for Custom Meta Name platform.</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <template id="siteVerificationEntryTemplate">
                        <div class="border rounded p-3 mb-3" data-site-verification-row>
                            <div class="row g-3 align-items-start">
                                <div class="col-md-4">
                                    <label class="form-label">Platform</label>
                                    <select class="form-select" data-verification-field="provider">
                                        @foreach($verificationProviderOptions as $providerKey => $providerLabel)
                                            <option value="{{ $providerKey }}">{{ $providerLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Short Code</label>
                                    <input type="text" class="form-control" data-verification-field="code" placeholder="Paste verification code">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger w-100" data-remove-site-verification-row>
                                        <i class="bi bi-trash me-1"></i> Remove
                                    </button>
                                </div>
                            </div>

                            <div class="row g-3 mt-1 d-none" data-custom-meta-wrapper>
                                <div class="col-md-6">
                                    <label class="form-label">Custom Meta Name</label>
                                    <input type="text" class="form-control" data-verification-field="meta_name" placeholder="example-verification-name">
                                    <div class="form-text">Required only for Custom Meta Name platform.</div>
                                </div>
                            </div>
                        </div>
                    </template>

                    @if($errors->has('site_verification_entries') || $errors->has('site_verification_entries.*.provider') || $errors->has('site_verification_entries.*.code') || $errors->has('site_verification_entries.*.meta_name'))
                        <div class="alert alert-danger mb-0">
                            Please review site verification rows. Custom entries need a custom meta name and every row needs a code.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-envelope me-2"></i>Mail / SMTP Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label fw-semibold mb-0" for="mail_enabled">Custom Mail Configuration</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mail_enabled" name="mail_enabled" value="1" data-integration-toggle="mail_enabled" {{ $isChecked('mail_enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="mail_enabled">Enable</label>
                            </div>
                        </div>
                    </div>
                    
                    <div data-integration-form="mail_enabled" class="{{ $isChecked('mail_enabled') ? '' : 'd-none' }}">
                        <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="mail_mailer">Mailer Configuration</label>
                            <select class="form-select @error('mail_mailer') is-invalid @enderror" id="mail_mailer" name="mail_mailer">
                                <option value="smtp" {{ $valueOf('mail_mailer', 'smtp') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                                <option value="sendmail" {{ $valueOf('mail_mailer') === 'sendmail' ? 'selected' : '' }}>PHP Mail (Sendmail)</option>
                            </select>
                            @error('mail_mailer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="mail_host">Mail Host</label>
                            <input type="text" class="form-control @error('mail_host') is-invalid @enderror" id="mail_host" name="mail_host" value="{{ $valueOf('mail_host') }}" placeholder="smtp.mailtrap.io">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mail_port">Mail Port</label>
                            <input type="text" class="form-control @error('mail_port') is-invalid @enderror" id="mail_port" name="mail_port" value="{{ $valueOf('mail_port') }}" placeholder="2525">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mail_username">Mail Username</label>
                            <input type="text" class="form-control @error('mail_username') is-invalid @enderror" id="mail_username" name="mail_username" value="{{ $valueOf('mail_username') }}" placeholder="username">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mail_password">Mail Password</label>
                            <input type="password" class="form-control @error('mail_password') is-invalid @enderror" id="mail_password" name="mail_password" value="{{ $valueOf('mail_password') }}" placeholder="••••••••">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mail_encryption">Encryption</label>
                            <select class="form-select @error('mail_encryption') is-invalid @enderror" id="mail_encryption" name="mail_encryption">
                                <option value="tls" {{ $valueOf('mail_encryption', 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ $valueOf('mail_encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="" {{ $valueOf('mail_encryption') === '' ? 'selected' : '' }}>None</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mail_from_address">From Address</label>
                            <input type="email" class="form-control @error('mail_from_address') is-invalid @enderror" id="mail_from_address" name="mail_from_address" value="{{ $valueOf('mail_from_address') }}" placeholder="noreply@example.com">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="mail_from_name">From Name</label>
                            <input type="text" class="form-control @error('mail_from_name') is-invalid @enderror" id="mail_from_name" name="mail_from_name" value="{{ $valueOf('mail_from_name') }}" placeholder="My Store">
                        </div>
                    </div>
                </div>
                
                <div class="small text-muted mt-2 {{ $isChecked('mail_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="mail_enabled" style="padding: 0 1.25rem 1.25rem;">
                    Disabled. Enable this integration to configure custom SMTP or PHP Mail settings. If disabled, Laravel's default mail settings (.env) will be used.
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-square-text me-2"></i>SMS API Integration</h6>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" value="1" data-integration-toggle="sms_enabled" {{ $isChecked('sms_enabled') ? 'checked' : '' }}>
                        <label class="form-check-label" for="sms_enabled">Enable</label>
                    </div>
                </div>
                <div class="card-body">
                    <div data-integration-form="sms_enabled" class="{{ $isChecked('sms_enabled') ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-outline-info btn-sm" id="getSmsBalanceBtn" data-url="{{ route('admin.settings.integrations.sms-balance') }}">
                                <span class="balance-btn-label"><i class="bi bi-wallet2 me-1"></i> Get Balance</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>

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

                    <div class="small text-muted {{ $isChecked('sms_enabled') ? 'd-none' : '' }}" data-integration-disabled-note="sms_enabled">
                        Disabled. Enable SMS integration to open its configuration form.
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
    const verificationContainer = document.getElementById('siteVerificationEntries');
    const verificationTemplate = document.getElementById('siteVerificationEntryTemplate');
    const addVerificationButton = document.getElementById('addSiteVerificationEntryBtn');

    const syncCustomMetaVisibility = (row) => {
        if (!row) {
            return;
        }

        const providerField = row.querySelector('[data-verification-field="provider"]');
        const customMetaWrapper = row.querySelector('[data-custom-meta-wrapper]');

        if (!providerField || !customMetaWrapper) {
            return;
        }

        const isCustomProvider = providerField.value === 'custom';
        customMetaWrapper.classList.toggle('d-none', !isCustomProvider);
    };

    const reindexVerificationRows = () => {
        if (!verificationContainer) {
            return;
        }

        const rows = verificationContainer.querySelectorAll('[data-site-verification-row]');

        rows.forEach((row, index) => {
            row.querySelectorAll('[data-verification-field]').forEach((field) => {
                const key = field.getAttribute('data-verification-field');
                field.setAttribute('name', `site_verification_entries[${index}][${key}]`);
            });

            syncCustomMetaVisibility(row);
        });
    };

    const appendNewVerificationRow = () => {
        if (!verificationContainer || !verificationTemplate) {
            return;
        }

        const templateContent = verificationTemplate.content.cloneNode(true);
        verificationContainer.appendChild(templateContent);
        reindexVerificationRows();
    };

    if (verificationContainer && verificationTemplate && addVerificationButton) {
        if (verificationContainer.querySelectorAll('[data-site-verification-row]').length === 0) {
            appendNewVerificationRow();
        } else {
            reindexVerificationRows();
        }

        addVerificationButton.addEventListener('click', function () {
            appendNewVerificationRow();
        });

        verificationContainer.addEventListener('click', function (event) {
            const removeButton = event.target.closest('[data-remove-site-verification-row]');

            if (!removeButton) {
                return;
            }

            const row = removeButton.closest('[data-site-verification-row]');
            if (row) {
                row.remove();
                reindexVerificationRows();
            }
        });

        verificationContainer.addEventListener('change', function (event) {
            const target = event.target;
            if (target && target.getAttribute('data-verification-field') === 'provider') {
                const row = target.closest('[data-site-verification-row]');
                syncCustomMetaVisibility(row);
            }
        });
    }

    const getBalanceBtn = document.getElementById('getSmsBalanceBtn');
    const balanceAlert = document.getElementById('smsBalanceAlert');

    if (getBalanceBtn && balanceAlert) {
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
    }
});
</script>
@endpush
