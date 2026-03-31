@extends('admin.layouts.app')

@section('title', 'Configure ' . $gateway->name)
@section('page-title', 'Configure ' . $gateway->name)

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <form action="{{ route('admin.settings.payment-gateways.update', $gateway) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <a href="{{ route('admin.settings.payment-gateways') }}" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h6 class="mb-0 fw-semibold">
                        {!! $gateway->getIconHtml() !!}
                        <span class="ms-2">{{ $gateway->name }} Settings</span>
                    </h6>
                </div>
                <div class="card-body">
                    <!-- General Settings -->
                    <h6 class="text-muted small text-uppercase mb-3">General Settings</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label small text-muted">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $gateway->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label small text-muted">Sort Order</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                       id="sort_order" name="sort_order" value="{{ old('sort_order', $gateway->sort_order) }}" min="0">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label small text-muted">Short Description</label>
                        <input type="text" class="form-control @error('description') is-invalid @enderror" 
                               id="description" name="description" value="{{ old('description', $gateway->description) }}" maxlength="500">
                        <div class="form-text">Shown to customers during checkout</div>
                    </div>

                    <div class="mb-3">
                        <label for="instructions" class="form-label small text-muted">Customer Instructions</label>
                        <textarea class="form-control @error('instructions') is-invalid @enderror" 
                                  id="instructions" name="instructions" rows="3">{{ old('instructions', $gateway->instructions) }}</textarea>
                        <div class="form-text">Detailed instructions shown after order placement</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_amount" class="form-label small text-muted">Minimum Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control @error('min_amount') is-invalid @enderror" 
                                           id="min_amount" name="min_amount" value="{{ old('min_amount', $gateway->min_amount) }}" 
                                           min="0" step="0.01">
                                </div>
                                <div class="form-text">Leave empty for no minimum</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_amount" class="form-label small text-muted">Maximum Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control @error('max_amount') is-invalid @enderror" 
                                           id="max_amount" name="max_amount" value="{{ old('max_amount', $gateway->max_amount) }}" 
                                           min="0" step="0.01">
                                </div>
                                <div class="form-text">Leave empty for no maximum</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gateway Specific Settings -->
            @if($gateway->code === 'cod')
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2"></i>COD Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="extra_charge" class="form-label small text-muted">Extra Charge</label>
                                    <input type="number" class="form-control" id="extra_charge" 
                                           name="settings[extra_charge]" 
                                           value="{{ old('settings.extra_charge', $gateway->getSetting('extra_charge', 0)) }}" 
                                           min="0" step="0.01">
                                    <div class="form-text">Additional charge for COD orders</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="extra_charge_type" class="form-label small text-muted">Charge Type</label>
                                    <select class="form-select" id="extra_charge_type" name="settings[extra_charge_type]">
                                        <option value="fixed" {{ $gateway->getSetting('extra_charge_type') === 'fixed' ? 'selected' : '' }}>
                                            Fixed Amount
                                        </option>
                                        <option value="percentage" {{ $gateway->getSetting('extra_charge_type') === 'percentage' ? 'selected' : '' }}>
                                            Percentage of Total
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($gateway->code === 'stripe')
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card me-2"></i>Stripe API Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Get your API keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a>
                        </div>

                        <div class="mb-3">
                            <label for="mode" class="form-label small text-muted">Mode</label>
                            <select class="form-select" id="mode" name="settings[mode]">
                                <option value="test" {{ $gateway->getSetting('mode') === 'test' ? 'selected' : '' }}>
                                    Test Mode (Use test keys)
                                </option>
                                <option value="live" {{ $gateway->getSetting('mode') === 'live' ? 'selected' : '' }}>
                                    Live Mode (Real transactions)
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="public_key" class="form-label small text-muted">Publishable Key</label>
                            <input type="text" class="form-control font-monospace" id="public_key" 
                                   name="settings[public_key]" 
                                   value="{{ old('settings.public_key', $gateway->getSetting('public_key')) }}"
                                   placeholder="pk_test_...">
                        </div>

                        <div class="mb-3">
                            <label for="secret_key" class="form-label small text-muted">Secret Key</label>
                            <input type="password" class="form-control font-monospace" id="secret_key" 
                                   name="settings[secret_key]" 
                                   value="{{ old('settings.secret_key', $gateway->getSetting('secret_key')) }}"
                                   placeholder="sk_test_...">
                            <div class="form-text">Keep this secret! Never expose on frontend.</div>
                        </div>

                        <div class="mb-3">
                            <label for="webhook_secret" class="form-label small text-muted">Webhook Secret</label>
                            <input type="password" class="form-control font-monospace" id="webhook_secret" 
                                   name="settings[webhook_secret]" 
                                   value="{{ old('settings.webhook_secret', $gateway->getSetting('webhook_secret')) }}"
                                   placeholder="whsec_...">
                            <div class="form-text">For receiving payment confirmations</div>
                        </div>
                    </div>
                </div>
            @elseif($gateway->code === 'bkash')
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-phone me-2"></i>bKash API Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Get your API credentials from <a href="https://developer.bka.sh" target="_blank">bKash Developer Portal</a>
                        </div>

                        <div class="mb-3">
                            <label for="bkash_mode" class="form-label small text-muted">Mode</label>
                            <select class="form-select" id="bkash_mode" name="settings[mode]">
                                <option value="sandbox" {{ $gateway->getSetting('mode') === 'sandbox' ? 'selected' : '' }}>
                                    Sandbox (Testing)
                                </option>
                                <option value="live" {{ $gateway->getSetting('mode') === 'live' ? 'selected' : '' }}>
                                    Live (Production)
                                </option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="app_key" class="form-label small text-muted">App Key</label>
                                    <input type="text" class="form-control font-monospace" id="app_key" 
                                           name="settings[app_key]" 
                                           value="{{ old('settings.app_key', $gateway->getSetting('app_key')) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="app_secret" class="form-label small text-muted">App Secret</label>
                                    <input type="password" class="form-control font-monospace" id="app_secret" 
                                           name="settings[app_secret]" 
                                           value="{{ old('settings.app_secret', $gateway->getSetting('app_secret')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label small text-muted">Username</label>
                                    <input type="text" class="form-control" id="username" 
                                           name="settings[username]" 
                                           value="{{ old('settings.username', $gateway->getSetting('username')) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label small text-muted">Password</label>
                                    <input type="password" class="form-control" id="password" 
                                           name="settings[password]" 
                                           value="{{ old('settings.password', $gateway->getSetting('password')) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Settings
                </button>
                <a href="{{ route('admin.settings.payment-gateways') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-md-4">
        <!-- Status Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">Gateway Status</h6>
            </div>
            <div class="card-body text-center">
                <div class="fs-1 mb-2">{!! $gateway->getIconHtml() !!}</div>
                @if($gateway->is_active)
                    <span class="badge bg-success fs-6">
                        <i class="bi bi-check-circle me-1"></i> Active
                    </span>
                @else
                    <span class="badge bg-secondary fs-6">
                        <i class="bi bi-circle me-1"></i> Inactive
                    </span>
                @endif

                <form action="{{ route('admin.settings.payment-gateways.toggle', $gateway) }}" method="POST" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm {{ $gateway->is_active ? 'btn-outline-danger' : 'btn-success' }}">
                        @if($gateway->is_active)
                            <i class="bi bi-pause-circle me-1"></i> Disable
                        @else
                            <i class="bi bi-play-circle me-1"></i> Enable
                        @endif
                    </button>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">Information</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <strong>Code:</strong> <code>{{ $gateway->code }}</code>
                    </li>
                    <li class="mb-2">
                        <strong>Currencies:</strong> 
                        @if($gateway->supported_currencies)
                            {{ implode(', ', $gateway->supported_currencies) }}
                        @else
                            All
                        @endif
                    </li>
                    <li class="mb-2">
                        <strong>Requires Redirect:</strong>
                        {{ $gateway->requiresRedirect() ? 'Yes' : 'No' }}
                    </li>
                    <li>
                        <strong>Pay on Delivery:</strong>
                        {{ $gateway->isPayOnDelivery() ? 'Yes' : 'No' }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
