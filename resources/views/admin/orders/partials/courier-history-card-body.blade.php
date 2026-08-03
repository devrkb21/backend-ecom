@if(!empty($courierCheckError))
    <div class="alert alert-danger py-2 small mb-0">
        <i class="bi bi-exclamation-octagon me-1"></i>
        {{ $courierCheckError }}
        @if(!empty($courierCheckSettingsUrl))
            <a href="{{ $courierCheckSettingsUrl }}" class="alert-link">Add credentials →</a>
        @endif
    </div>
@elseif(!$courierCheckResult)
    <p class="text-muted small mb-0">Not checked yet. A background check runs automatically after each order, or click "Check Now" above.</p>
@else
    @php
        $cancelRatio = $courierCheckResult->cancelRatio();
        $riskThreshold = (float) \App\Models\Setting::getValue('fraud_blocks', 'courier_check_max_cancel_ratio', 40);
        $riskBadge = $cancelRatio >= $riskThreshold ? 'danger' : ($cancelRatio >= $riskThreshold / 2 ? 'warning' : 'success');
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted d-flex align-items-center gap-2">
            Checked {{ $courierCheckResult->checked_at?->diffForHumans() }}
            @if(($fromCache ?? false))
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle" title="Served from cache — click Refresh for a live re-check.">
                    <i class="bi bi-clock-history me-1"></i>Cached
                </span>
            @else
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" title="Just checked live against the couriers.">
                    <i class="bi bi-broadcast me-1"></i>Fresh
                </span>
            @endif
        </span>
        <span class="badge bg-{{ $riskBadge }} bg-opacity-10 text-{{ $riskBadge }}">
            {{ $courierCheckResult->total_deliveries }} deliveries · {{ $cancelRatio }}% cancel rate
        </span>
    </div>
    @if($courierCheckResult->couriers_ok === 0)
        <div class="alert alert-danger py-2 small mb-2">
            <i class="bi bi-exclamation-octagon me-1"></i>
            None of the 5 couriers responded successfully — check your credentials in
            <a href="{{ route('admin.orders.courier-checker', ['tab' => 'settings']) }}" class="alert-link">Courier Checker settings</a>.
        </div>
    @elseif($courierCheckResult->couriers_failed > 0)
        <div class="alert alert-warning py-2 small mb-2">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ $courierCheckResult->couriers_failed }} of 5 couriers could not be checked (login/rate-limit issue) — this result is partial.
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr class="text-muted small">
                    <th>Courier</th><th>Success</th><th>Cancel</th><th>Ratio</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['steadfast' => 'Steadfast', 'pathao' => 'Pathao', 'redx' => 'RedX', 'paperfly' => 'Paperfly', 'carrybee' => 'Carrybee'] as $key => $label)
                    @php $row = $courierCheckResult->raw_result[$key] ?? null; @endphp
                    <tr>
                        <td class="small">
                            {{ $label }}
                            @if($key === 'carrybee' && is_array($row) && !empty($row['new_customer']))
                                <span class="badge bg-info bg-opacity-10 text-info ms-1">New customer</span>
                            @endif
                        </td>
                        @if(is_array($row) && !isset($row['error']))
                            <td class="small">{{ $row['success'] ?? 0 }}</td>
                            <td class="small">{{ $row['cancel'] ?? 0 }}</td>
                            <td class="small">
                                @if($key === 'pathao' && !empty($row['customer_rating']))
                                    @php
                                        $ratingRaw = (string) $row['customer_rating'];
                                        $ratingLabel = Str::title(str_replace('_', ' ', $ratingRaw));
                                        $ratingColor = match(true) {
                                            str_contains($ratingRaw, 'risky') || str_contains($ratingRaw, 'suspicious') || str_contains($ratingRaw, 'bad') => 'danger',
                                            str_contains($ratingRaw, 'new') => 'info',
                                            str_contains($ratingRaw, 'excellent') || str_contains($ratingRaw, 'trusted') || str_contains($ratingRaw, 'good') => 'success',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $ratingColor }} bg-opacity-10 text-{{ $ratingColor }}">{{ $ratingLabel }}</span>
                                @else
                                    {{ $row['success_ratio'] ?? 0 }}%
                                @endif
                            </td>
                        @else
                            <td colspan="3" class="small text-muted" title="{{ $row['message'] ?? '' }}">
                                {{ $row['error'] ?? 'Not checked' }}
                                @if(!empty($row['message']) && $row['message'] !== ($row['error'] ?? null))
                                    <br><span class="text-danger">{{ Str::limit($row['message'], 120) }}</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
