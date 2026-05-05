<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $settings['packaging_slip_title'] ?? 'Packaging Slip' }} #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: {{ $settings['invoice_font_size'] ?? '12' }}px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .slip-box {
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
            border-bottom: 2px solid {{ $settings['invoice_primary_color'] ?? '#1e293b' }};
            padding-bottom: 20px;
        }
        .header .logo {
            font-size: 28px;
            font-weight: bold;
            color: {{ $settings['invoice_primary_color'] ?? '#1e293b' }};
        }
        .header .slip-label {
            font-size: 28px;
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
        .items-table {
            margin-bottom: 40px;
        }
        .items-table thead th {
            border-bottom: 2px solid #333;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table tbody td {
            padding: 15px 10px;
            border-bottom: 1px solid #eee;
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
        .checkbox-cell {
            width: 30px;
            border: 1px solid #ccc;
            height: 20px;
            display: inline-block;
        }

        /* Custom CSS from settings */
        {!! $settings['invoice_custom_css'] ?? '' !!}
    </style>
</head>
<body>
    <div class="slip-box">
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
                        <img src="{{ $logoBase64 }}" style="max-height: 50px; max-width: 180px;">
                    @elseif(!empty($settings['invoice_logo']))
                        <img src="{{ $settings['invoice_logo'] }}" style="max-height: 50px; max-width: 180px;">
                    @else
                        <div class="logo">{{ $settings['invoice_company_name'] ?? config('app.name') }}</div>
                    @endif
                </td>
                <td class="slip-label">
                    {{ $settings['packaging_slip_title'] ?? 'Packaging Slip' }}
                    <div style="font-size: 14px; color: #666; margin-top: 5px; font-weight: normal;">
                        Order #{{ $order->order_number }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="info-table">
            <tr>
                <td>
                    <div class="font-bold" style="margin-bottom: 5px; color: #666; text-transform: uppercase; font-size: 11px;">{{ $settings['invoice_ship_to_label'] ?? 'Ship To' }}:</div>
                    <div class="font-bold" style="font-size: 1.2em;">{{ $order->shipping_name }}</div>
                    <div>{{ $order->shipping_address }}</div>
                    @if($order->shipping_city)<div>{{ $order->shipping_city }}</div>@endif
                    <div style="margin-top: 5px;"><strong>Phone:</strong> {{ $order->shipping_phone }}</div>
                </td>
                <td class="text-right">
                    <div class="font-bold" style="margin-bottom: 5px; color: #666; text-transform: uppercase; font-size: 11px;">Shipment Details:</div>
                    <div><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y') }}</div>
                    @if(($settings['invoice_show_shipping_method'] ?? true) && $order->shipping_method)
                        <div><strong>Shipping Method:</strong> {{ $order->shipping_method }}</div>
                    @endif
                    @if($order->notes)
                        <div style="margin-top: 10px; background: #f9f9f9; padding: 10px; border-left: 3px solid #ccc; text-align: left;">
                            <strong>Customer Notes:</strong><br>
                            {{ $order->notes }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">Check</th>
                    <th>{{ $settings['invoice_item_label'] ?? 'Product Item & SKU' }}</th>
                    <th class="text-right" style="width: 100px;">{{ $settings['invoice_qty_label'] ?? 'Quantity' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="vertical-align: middle;"><div class="checkbox-cell"></div></td>
                    <td>
                        <div class="font-bold" style="font-size: 1.1em;">{{ $item->product_name }}</div>
                        <div style="color: #666;">SKU: {{ $item->product_sku ?: 'N/A' }}</div>
                        @if($item->variant)
                            <div style="font-size: 0.9em; color: #333; margin-top: 4px; padding: 4px; background: #f0f0f0; display: inline-block;">
                                {{ $item->variant->attributeValues->map(fn($av) => $av->attribute->name . ': ' . $av->value)->join(', ') }}
                            </div>
                        @endif
                    </td>
                    <td class="text-right font-bold" style="font-size: 1.2em;">
                        x {{ $item->quantity }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 40px; border: 1px dashed #ccc; padding: 20px;">
            <div class="font-bold" style="margin-bottom: 10px;">Packer's Checklist:</div>
            <table style="font-size: 0.9em;">
                <tr>
                    <td width="30"><div class="checkbox-cell"></div></td>
                    <td>All items verified against order</td>
                    <td width="30"><div class="checkbox-cell"></div></td>
                    <td>Items inspected for quality/damage</td>
                </tr>
                <tr>
                    <td><div class="checkbox-cell"></div></td>
                    <td>Securely packed for transit</td>
                    <td><div class="checkbox-cell"></div></td>
                    <td>Invoice/Gift message included</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            {{ $settings['invoice_company_name'] ?? config('app.name') }} - Thank you for shopping with us!
        </div>
    </div>
</body>
</html>
