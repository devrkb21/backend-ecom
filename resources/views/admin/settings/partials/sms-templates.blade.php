@php
    $templates = $smsTemplates ?? [];
@endphp

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-xs">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Placeholders</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">Use these placeholders in your SMS templates. They will be replaced with actual order data when the SMS is sent.</p>
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td><code>{order_number}</code></td>
                            <td class="text-muted small">Order number</td>
                        </tr>
                        <tr>
                            <td><code>{customer_name}</code></td>
                            <td class="text-muted small">Customer name</td>
                        </tr>
                        <tr>
                            <td><code>{status}</code></td>
                            <td class="text-muted small">New status label</td>
                        </tr>
                        <tr>
                            <td><code>{total}</code></td>
                            <td class="text-muted small">Order total (৳)</td>
                        </tr>
                        <tr>
                            <td><code>{site_name}</code></td>
                            <td class="text-muted small">Your store name</td>
                        </tr>
                        <tr>
                            <td><code>{phone}</code></td>
                            <td class="text-muted small">Customer phone</td>
                        </tr>
                    </tbody>
                </table>

                <hr>
                <p class="small text-muted mb-2"><strong>Example Template:</strong></p>
                <div class="bg-light rounded p-2 small text-dark">
                    <code>Dear {customer_name}, your order #{order_number} has been {status}. Total: ৳{total}. Thank you - {site_name}</code>
                </div>

                @if(!$smsEnabled)
                    <div class="alert alert-warning mt-3 mb-0 py-2 small text-dark">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        SMS integration is currently <strong>disabled</strong>.
                        <a href="{{ route('admin.settings.system.index', ['group' => 'integrations']) }}" class="fw-semibold text-pathao-brand">Enable it here</a> to send notifications.
                    </div>
                @else
                    <div class="alert alert-success mt-3 mb-0 py-2 small text-dark">
                        <i class="bi bi-check-circle me-1"></i>
                        SMS integration is <strong>enabled</strong>.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <form action="{{ route('admin.settings.sms-templates.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card shadow-xs">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-chat-dots me-2 text-primary"></i>SMS Templates per Order Status</h6>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-lg me-1"></i> Save All Templates
                    </button>
                </div>
                <div class="card-body p-0">
                    @forelse($templates as $statusKey => $tpl)
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-white" style="background-color: {{ $tpl['color'] }};">{{ $tpl['label'] }}</span>
                                    <code class="small text-muted">{{ $statusKey }}</code>
                                </div>
                                <div class="form-check form-switch">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="templates[{{ $statusKey }}][enabled]"
                                        value="1"
                                        id="sms_enable_{{ $statusKey }}"
                                        {{ $tpl['enabled'] ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label small" for="sms_enable_{{ $statusKey }}">
                                        Send SMS
                                    </label>
                                </div>
                            </div>
                            <textarea
                                class="form-control form-control-sm"
                                name="templates[{{ $statusKey }}][template]"
                                rows="2"
                                placeholder="Enter SMS message template for {{ $tpl['label'] }} status..."
                                maxlength="500"
                            >{{ old("templates.{$statusKey}.template", $tpl['template']) }}</textarea>
                            @if($tpl['template'])
                                <small class="text-muted mt-1 d-block">{{ strlen($tpl['template']) }} characters</small>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No order statuses found. <a href="{{ route('admin.settings.system.index', ['group' => 'order-statuses']) }}">Create some first</a>.
                        </div>
                    @endforelse
                </div>
                @if(count($templates) > 0)
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-check-lg me-1"></i> Save All Templates
                        </button>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
