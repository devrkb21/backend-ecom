<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $settings['invoice_title'] ?? 'Invoice' }} #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: {{ $settings['invoice_font_size'] ?? '12' }}px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
        }
        table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }
        table td {
            padding: 5px;
            vertical-align: top;
        }
        .header {
            margin-bottom: 30px;
        }
        .header .logo {
            font-size: 28px;
            font-weight: bold;
            color: {{ $settings['invoice_primary_color'] ?? '#1e293b' }};
        }
        .header .invoice-label {
            font-size: 32px;
            font-weight: bold;
            text-align: right;
            text-transform: uppercase;
            color: {{ $settings['invoice_primary_color'] ?? '#1e293b' }};
        }
        .info-table {
            margin-bottom: 40px;
        }
        .info-table td {
            width: 50%;
        }
        .details-table {
            margin-bottom: 40px;
        }
        .details-table thead th {
            background: {{ $settings['invoice_primary_color'] ?? '#1e293b' }};
            color: #fff;
            padding: 10px;
            text-align: left;
        }
        .details-table tbody td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .details-table tfoot td {
            padding: 8px 10px;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 10px;
            color: #777;
            text-align: center;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fef9c3; color: #a16207; }

        /* Custom CSS from settings */
        {!! $settings['invoice_custom_css'] ?? '' !!}
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header">
            <tr>
                <td>
                    @php
                        $logoBase64 = null;
                        try {
                            if (!empty($settings['invoice_logo'])) {
                                $imagePath = public_path(parse_url($settings['invoice_logo'], PHP_URL_PATH));
                                if (file_exists($imagePath)) {
                                    $imageData = base64_encode(file_get_contents($imagePath));
                                    $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                                    $logoBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
                                }
                            }
                        } catch (\Exception $e) {}
                    @endphp

                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" style="max-height: 60px; max-width: 200px;">
                    @elseif(!empty($settings['invoice_logo']))
                        <img src="{{ $settings['invoice_logo'] }}" style="max-height: 60px; max-width: 200px;">
                    @else
                        <div class="logo">{{ $settings['invoice_company_name'] ?? config('app.name') }}</div>
                    @endif
                    <div style="margin-top: 10px; font-size: 11px;">
                        @if(!empty($settings['invoice_company_address']))
                            <div>{!! nl2br(e($settings['invoice_company_address'])) !!}</div>
                        @endif
                        @if(!empty($settings['invoice_company_phone']))
                            <div>Phone: {{ $settings['invoice_company_phone'] }}</div>
                        @endif
                        @if(!empty($settings['invoice_company_email']))
                            <div>Email: {{ $settings['invoice_company_email'] }}</div>
                        @endif
                    </div>
                </td>
                <td class="invoice-label">
                    {{ $settings['invoice_title'] ?? 'Invoice' }}
                    <div style="font-size: 14px; color: #666; margin-top: 5px;">
                        #{{ $order->order_number }}
                    </div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px; font-weight: normal;">
                        Date: {{ $order->created_at->format('M d, Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>
                    <div class="font-bold" style="margin-bottom: 5px; color: #666; text-transform: uppercase; font-size: 11px;">{{ $settings['invoice_bill_to_label'] ?? 'Bill To' }}:</div>
                    <div class="font-bold">{{ $order->shipping_name }}</div>
                    <div>{{ $order->shipping_address }}</div>
                    @if($order->shipping_city)<div>{{ $order->shipping_city }}</div>@endif
                    <div>{{ $order->shipping_phone }}</div>
                    @if($order->shipping_email)<div>{{ $order->shipping_email }}</div>@endif
                </td>
                <td class="text-right">
                    <div class="font-bold" style="margin-bottom: 5px; color: #666; text-transform: uppercase; font-size: 11px;">Order Details:</div>
                    <div><strong>Payment Method:</strong> {{ strtoupper($order->payment_method) }}</div>
                    @if($settings['invoice_show_payment_status'] ?? true)
                    <div><strong>Payment Status:</strong> 
                        <span class="badge {{ $order->payment_status === 'paid' ? 'badge-paid' : 'badge-pending' }}">
                            {{ $order->payment_status }}
                        </span>
                    </div>
                    @endif
                    @if(($settings['invoice_show_shipping_method'] ?? true) && $order->shipping_method)
                        <div><strong>Shipping:</strong> {{ $order->shipping_method }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="details-table">
            <thead>
                <tr>
                    <th>{{ $settings['invoice_item_label'] ?? 'Item Description' }}</th>
                    <th class="text-right" style="width: 60px;">{{ $settings['invoice_qty_label'] ?? 'Qty' }}</th>
                    <th class="text-right" style="width: 120px;">{{ $settings['invoice_price_label'] ?? 'Price' }}</th>
                    <th class="text-right" style="width: 150px;">{{ $settings['invoice_total_label'] ?? 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="font-bold">{{ $item->product_name }}</div>
                        @if($item->variant)
                            <div style="font-size: 11px; color: #666;">
                                {{ $item->variant->attributeValues->map(fn($av) => $av->attribute->name . ': ' . $av->value)->join(', ') }}
                            </div>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">Tk. {{ number_format($item->price, 2) }}</td>
                    <td class="text-right">Tk. {{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right font-bold">Subtotal:</td>
                    <td class="text-right font-bold">Tk. {{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount_amount > 0)
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right">Discount:</td>
                    <td class="text-right">-Tk. {{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($order->shipping > 0)
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right">Shipping:</td>
                    <td class="text-right">Tk. {{ number_format($order->shipping, 2) }}</td>
                </tr>
                @endif
                @if($order->tax > 0)
                <tr>
                    <td colspan="2"></td>
                    <td class="text-right">Tax:</td>
                    <td class="text-right">Tk. {{ number_format($order->tax, 2) }}</td>
                </tr>
                @endif
                <tr style="font-size: 1.2em;">
                    <td colspan="2"></td>
                    <td class="text-right font-bold" style="color: {{ $settings['invoice_primary_color'] ?? '#1e293b' }};">Total:</td>
                    <td class="text-right font-bold" style="color: {{ $settings['invoice_primary_color'] ?? '#1e293b' }};">Tk. {{ number_format($order->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if(!empty($settings['invoice_footer_notes']))
            <div style="margin-top: 30px;">
                <div class="font-bold" style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Notes:</div>
                <div style="font-size: 12px;">{!! nl2br(e($settings['invoice_footer_notes'])) !!}</div>
            </div>
        @endif

        @if(!empty($settings['invoice_terms']))
            <div style="margin-top: 20px;">
                <div class="font-bold" style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Terms & Conditions:</div>
                <div style="font-size: 10px; color: #777;">{!! nl2br(e($settings['invoice_terms'])) !!}</div>
            </div>
        @endif

        <div class="footer">
            {{ $settings['invoice_company_name'] ?? config('app.name') }} - {{ $settings['invoice_footer_text'] ?? 'Generated on ' . date('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
