@extends('admin.layouts.app')

@section('title', 'Print Order #' . $order->order_number)
@section('page-title', 'Print Label/Invoice')

@section('content')
@php
    $logoPath = $invoiceSettings['invoice_logo'] ?? '';
    $logoUrl = '';
    if ($logoPath) {
        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            $logoUrl = $logoPath;
        } elseif (str_starts_with($logoPath, 'media/') || str_starts_with($logoPath, 'storage/')) {
            $logoUrl = Storage::url($logoPath);
        } else {
            $logoUrl = Storage::disk('public')->url($logoPath);
        }
    }

    $isDhaka = (optional($order->shippingDistrict)->name === 'Dhaka') || (optional($order->shippingDivision)->name === 'Dhaka');
    $sortType = $isDhaka ? 'ISD' : 'OSD';

    // Courier variables calculation for barcodes
    $trackingNumber = $order->tracking_number;
    $carrier = strtolower($order->carrier ?? '');
    
    $barcodeValue = $trackingNumber ?: $order->order_number;
    $barcodeText = $order->order_number;

    if ($trackingNumber) {
        if ($carrier === 'pathao') {
            $barcodeText = 'Pathao: ' . $trackingNumber;
        } elseif ($carrier === 'steadfast') {
            $barcodeText = 'SteadFast: ' . $trackingNumber;
        } else {
            $barcodeText = ucfirst($carrier) . ': ' . $trackingNumber;
        }
    }

    // Build rich payload for the Invoice QR Code
    $invoiceQrParts = [];
    $invoiceQrParts[] = "Order ID: " . $order->order_number;
    $invoiceQrParts[] = "Date: " . ($order->created_at ? $order->created_at->format('d/m/Y h:i:s A') : 'N/A');
    $invoiceQrParts[] = "Customer: " . ($order->shipping_name ?? $order->user?->name);
    $invoiceQrParts[] = "Phone: " . ($order->shipping_phone ?? $order->user?->phone);
    $invoiceQrParts[] = "Address: " . $order->shipping_address . 
                        ($order->shippingDistrict ? ", " . $order->shippingDistrict->name : "") . 
                        ($order->shippingDivision ? ", " . $order->shippingDivision->name : "");
    $invoiceQrParts[] = "";
    $invoiceQrParts[] = "ITEMS:";
    foreach ($order->items as $idx => $item) {
        $variantStr = ($item->variant && $item->variant->attributeValues->count() > 0) 
            ? " (" . $item->variant->attributeValues->pluck('value')->implode('/') . ")" 
            : "";
        $invoiceQrParts[] = ($idx + 1) . ". " . ($item->product->name ?? $item->product_name) . $variantStr . " x " . $item->quantity . " @ " . number_format($item->price) . " BDT = " . number_format($item->price * $item->quantity) . " BDT";
    }
    $invoiceQrParts[] = "";
    $invoiceQrParts[] = "Subtotal: " . number_format($order->subtotal ?? ($order->total - ($order->shipping ?? 0))) . " BDT";
    if ((bool) \App\Models\Setting::getValue('checkout', 'tax_enabled', false)) {
        $taxPercentage = (float) \App\Models\Setting::getValue('checkout', 'tax_percentage', 0);
        $invoiceQrParts[] = "Tax (" . $taxPercentage . "%): " . number_format($order->tax ?? 0) . " BDT";
    }
    $invoiceQrParts[] = "Shipping: " . number_format($order->shipping ?? 0) . " BDT";
    $invoiceQrParts[] = "Total: " . number_format($order->total) . " BDT";
    $invoiceQrParts[] = "Payment: " . ($order->payment_method === 'cod' ? 'Cash on Delivery' : ($order->payment_method === 'bkash' ? 'bKash' : ($order->payment_method ?: 'Online Payment')));
    if ($order->tracking_number) {
        $courierLabel = $order->carrier ? (strtolower($order->carrier) === 'pathao' ? 'Pathao' : (strtolower($order->carrier) === 'steadfast' ? 'Steadfast' : ucfirst($order->carrier))) : 'Courier';
        $invoiceQrParts[] = $courierLabel . " ID: " . $order->tracking_number;
    }
    $qrInvoiceText = implode("\n", $invoiceQrParts);
@endphp

<!-- CSS for screen rendering and print page sizing -->
<style>
    /* Styling for the print page controls */
    .print-controls-bar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 24px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .print-preview-area {
        background: #f1f5f9;
        min-height: calc(100vh - 200px);
        border-radius: 8px;
    }

    /* Thermal 2x3 in sizing */
    .label-2x3 {
        width: 2in;
        height: 3in;
        padding: 0.08in;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
        color: #000;
        background: #fff;
        font-size: 6.5pt;
        line-height: 1.1;
        border: 1px dashed #94a3b8;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Thermal 3x3 in sizing */
    .label-3x3 {
        width: 3in;
        height: 3in;
        padding: 8px;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
        color: #000;
        background: #fff;
        font-size: 7.8pt;
        line-height: 1.15;
        border: 1px dashed #94a3b8;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* A4 landscape styling */
    .label-a4 {
        width: 100%;
        max-width: 800px;
        padding: 24px;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
        color: #000;
        background: #fff;
        border: 2px solid #000;
        border-radius: 0;
        box-shadow: none;
    }

    /* Under development overlay for Invoice placeholder */
    .under-dev-overlay {
        text-align: center;
        padding: 50px 20px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        max-width: 500px;
        width: 100%;
    }

    /* Custom COD Highlights */
    .cod-box-thermal {
        border: 1.5px solid #000;
        padding: 3px;
        text-align: center;
        background: #f8fafc;
        font-weight: bold;
        border-radius: 4px;
    }

    .cod-box-thermal .cod-title {
        font-size: 5.5pt;
        display: block;
        font-weight: normal;
        letter-spacing: 0.5px;
    }

    .cod-box-thermal .cod-amount {
        font-size: 9.5pt;
        display: block;
        font-weight: 800;
    }

    /* Hide elements in print mode */
    @media print {
        /* Force A4 portrait for all print sizes to prevent stretching on A4 paper */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        /* Hide layout navigation sidebar, top navbar, control bar, header, and footer */
        header, footer, nav, .sidebar, .top-navbar, #sidebar, #sidebarToggle, #sidebarCollapseToggle, .print-controls-bar, .card-header, .page-title-box, .btn, .breadcrumb-item, .zoom-controls-floating {
            display: none !important;
        }
        
        /* Reset wrapper page-level layout adjustments and strictly constrain HTML/body size */
        html, body, .main-content, .page-content, .container-fluid, .content-wrapper, #layout-wrapper {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            margin-left: 0 !important;
            margin-top: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            min-height: 100% !important;
            max-height: 100% !important;
            width: 100% !important;
            height: 100% !important;
            overflow: visible !important;
        }

        /* Ensure parent wrappers do not restrict print flow or introduce margins */
        .page-content, .content-wrapper, .main-content {
            box-shadow: none !important;
            border: none !important;
        }
        
        .print-preview-area {
            background: none !important;
            padding: 0 !important;
            min-height: auto !important;
            display: block !important;
            border-radius: 0 !important;
        }
        
        .zoom-wrapper {
            padding: 0 !important;
            overflow: visible !important;
        }
        
        #label-mode-container {
            transform: none !important; /* Never apply zoom factor during physical printing */
            margin-bottom: 0 !important;
            display: block !important;
            width: 100% !important;
        }

        .preview-card {
            display: none !important; /* Hide all size containers by default */
            border: none !important;
            box-shadow: none !important;
            background: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Active sizing triggers */
        body.print-active-2x3 #label-container-2x3 {
            display: block !important;
        }
        body.print-active-2x3 .label-2x3 {
            border: 1px solid #000 !important;
            width: 2in !important;
            height: 3in !important;
            margin: 0 auto !important; /* Centered in the page */
        }

        body.print-active-3x3 #label-container-3x3 {
            display: block !important;
        }
        body.print-active-3x3 .label-3x3 {
            border: 1px solid #000 !important;
            width: 3in !important;
            height: 3in !important;
            margin: 0 auto !important; /* Centered in the page */
        }

        body.print-active-a4 #label-container-a4 {
            display: block !important;
            width: 100% !important;
        }
        body.print-active-a4 .label-a4 {
            border: 2px solid #000 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 260mm !important; /* Forces full height on A4 Portrait sheet */
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            padding: 30px !important;
        }

        /* Scale up typography for A4 print to occupy full page beautifully */
        body.print-active-a4 .label-a4 h6 {
            font-size: 13pt !important;
        }
        body.print-active-a4 .label-a4 .small {
            font-size: 10pt !important;
        }
        body.print-active-a4 .label-a4 div, body.print-active-a4 .label-a4 span {
            font-size: 10.5pt !important;
        }
        body.print-active-a4 .label-a4 .fs-3 {
            font-size: 28pt !important;
        }
        body.print-active-a4 .label-a4 .badge {
            font-size: 9pt !important;
        }

        /* Print-specific invoice rules */
        body.print-active-invoice #invoice-mode-container {
            display: block !important;
            width: 100% !important;
        }
        body.print-active-invoice .invoice-a4 {
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
            height: 276mm !important;
            min-height: 276mm !important;
            max-height: 276mm !important;
            padding: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }
        body.print-active-invoice .invoice-footer-bar {
            margin: 0 !important;
            width: 100% !important;
            padding: 10px 20px !important;
        }
        body.print-active-invoice #label-mode-container {
            display: none !important;
        }
    }

    /* ========================================
       Invoice A4 Screen Styles (outside @media print)
       ======================================== */

    /* Invoice A4 styling for screen preview */
    .invoice-a4 {
        width: 210mm;
        min-height: 297mm;
        padding: 20mm;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
        color: #000;
        background: #fff;
        border: 1px solid #cbd5e1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Product name clamp for table row */
    .product-name-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        word-break: break-word;
        max-height: 2.8em;
        line-height: 1.4;
        margin: 0 auto;
        text-align: center;
    }

    /* Center-align invoice table items */
    .invoice-table th, .invoice-table td {
        text-align: center !important;
        vertical-align: middle !important;
    }

    /* Courier badges styling for thermal labels */
    .courier-badge-thermal {
        border-radius: 4px;
        padding: 2.5px 5px;
        font-weight: 700;
        text-align: center;
        margin-top: 4px;
        font-size: 6pt;
        display: block;
        background: transparent !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .courier-badge-pathao {
        color: #ef4444 !important;
        border: 1.5px solid #ef4444 !important;
    }
    .courier-badge-steadfast {
        color: #22c55e !important;
        border: 1.5px solid #22c55e !important;
    }

    /* Base styling for the invoice footer bar on screen */
    .invoice-footer-bar {
        color: #fff;
        padding: 10px 20px;
        font-size: 8.5pt;
        margin: 0 -20mm -20mm -20mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<div class="container-fluid py-3 no-print">
    <!-- Top control panel -->
    <div class="print-controls-bar d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-primary" id="btn-toggle-invoice" onclick="toggleMode('invoice')">Invoice</button>
                <button type="button" class="btn btn-primary" id="btn-toggle-label" onclick="toggleMode('label')">Print Label</button>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="btn-group btn-group-sm label-size-selector" role="group">
                <button type="button" class="btn btn-outline-primary" id="btn-size-3x3" onclick="selectSize('3x3')">3 x 3 in</button>
                <button type="button" class="btn btn-primary" id="btn-size-2x3" onclick="selectSize('2x3')">2 x 3 in</button>
                <button type="button" class="btn btn-outline-primary" id="btn-size-a4" onclick="selectSize('a4')">A4 Page</button>
            </div>

            <button type="button" class="btn btn-success btn-sm px-4 fw-bold" id="btn-trigger-print" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Print Label
            </button>
        </div>
    </div>

    <!-- Interactive preview area with Zoom capabilities -->
    <div class="print-preview-area position-relative d-flex flex-column" id="previewArea">
        <!-- Floating zoom controls in the top right -->
        <div class="zoom-controls-floating d-flex gap-2 no-print p-3 position-absolute" style="top: 0; right: 0; z-index: 10;">
            <button type="button" class="btn btn-sm btn-light border shadow-sm px-2.5 py-1" onclick="zoom(0.1)" title="Zoom In">
                <i class="bi bi-zoom-in me-1"></i> Zoom In
            </button>
            <button type="button" class="btn btn-sm btn-light border shadow-sm px-2.5 py-1" onclick="zoom(-0.1)" title="Zoom Out">
                <i class="bi bi-zoom-out me-1"></i> Zoom Out
            </button>
            <button type="button" class="btn btn-sm btn-light border shadow-sm px-2.5 py-1" onclick="resetZoom()" title="Reset Zoom">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </div>


        <!-- 2. Label Mode Container with Scroll and Scaled Zoom Wrapper -->
        <div class="zoom-wrapper w-100 flex-grow-1 d-flex justify-content-center align-items-start py-5" style="overflow: auto;">
            <div id="label-mode-container" class="d-flex justify-content-center" style="transform-origin: top center; transition: transform 0.1s ease;">
                <!-- 2x3 Thermal Label -->
                <div id="label-container-2x3" class="preview-card print-size-2x3">
                    <div class="label-2x3">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1" style="height: 25px; margin-bottom: 4px;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 22px; max-width: 40%; object-fit: contain;">
                                <div class="text-end" style="max-width: 55%; line-height: 1.1;">
                                    <div class="fw-bold text-truncate" style="font-size: 6.2pt; color: #1e293b;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</div>
                                    <div class="text-muted text-truncate" style="font-size: 5pt; font-weight: 500;">{{ $invoiceSettings['invoice_company_phone'] ?? '' }}</div>
                                </div>
                            @else
                                <div class="text-center w-100" style="line-height: 1.1;">
                                    <div class="fw-bold text-truncate text-uppercase" style="font-size: 7.2pt; color: #1e293b;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</div>
                                    <div class="text-muted text-truncate" style="font-size: 5.5pt; font-weight: 500;">{{ $invoiceSettings['invoice_company_phone'] ?? '' }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Barcode element -->
                        <div class="text-center" style="margin-bottom: 4px;">
                            <svg id="barcode-thermal-2x3" style="display: block; margin: 0 auto;"></svg>
                        </div>

                        <!-- QR & Info -->
                        <div class="row g-0 align-items-center border-bottom pb-1 mb-2">
                            <div class="col-4 text-center d-flex align-items-center justify-content-center">
                                <canvas id="qr-thermal-2x3" style="display: block;"></canvas>
                            </div>
                            <div class="col-8 ps-2 text-start" style="font-size: 6.2pt; line-height: 1.25;">
                                <div class="row g-0">
                                    <div class="col-5 text-muted">ORDER ID:</div>
                                    <div class="col-7 fw-bold text-end text-truncate">{{ $order->order_number }}</div>
                                </div>
                                <div class="row g-0">
                                    <div class="col-5 text-muted">DELIVERY:</div>
                                    <div class="col-7 text-end text-truncate">{{ $order->payment_method === 'cod' ? 'Home (COD)' : 'Home (Prepaid)' }}</div>
                                </div>
                                <div class="row g-0">
                                    <div class="col-5 text-muted">WEIGHT:</div>
                                    <div class="col-7 text-end">0.5 KG</div>
                                </div>
                                @if($order->carrier)
                                    <div class="courier-badge-thermal {{ strtolower($order->carrier) === 'pathao' ? 'courier-badge-pathao' : 'courier-badge-steadfast' }}">
                                        Shipped to: {{ ucfirst($order->carrier) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Recipient Block -->
                        <div class="text-start flex-grow-1" style="font-size: 5.5pt; line-height: 1.2; overflow: hidden; margin-bottom: 6px; padding-top: 2px;">
                            <div class="text-truncate"><strong>NAME:</strong> <span class="fw-semibold">{{ $order->shipping_name ?? $order->user?->name }}</span></div>
                            <div class="text-truncate"><strong>PHONE:</strong> <span class="fw-semibold">{{ $order->shipping_phone ?? $order->user?->phone }}</span></div>
                            <div style="margin-top: 1px;"><strong>ADDRESS:</strong> {{ $order->shipping_address }}</div>
                        </div>

                        <!-- COD Box -->
                        <div class="cod-box-thermal" style="margin-bottom: 4px;">
                            <span class="cod-title" style="font-size: 5pt; line-height: 1;">CASH ON DELIVERY</span>
                            <span class="cod-amount" style="font-size: 8.5pt; line-height: 1.1; margin-top: 1px;">৳ {{ number_format($order->total) }}</span>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center border-top pt-1 text-muted" style="font-size: 4.8pt; line-height: 1; height: 12px;">
                            <span>{{ now()->format('d/m/y h:ia') }}</span>
                            <a href="https://coderzonebd.com/" target="_blank" class="text-decoration-none text-muted fw-bold">Generated by Coder Zone BD ecom</a>
                        </div>
                    </div>
                </div>

                <!-- 3x3 Thermal Label -->
                <div id="label-container-3x3" class="preview-card print-size-3x3 d-none">
                    <div class="label-3x3">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1" style="height: 30px; margin-bottom: 4px;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 26px; max-width: 45%; object-fit: contain;">
                                <div class="text-end" style="max-width: 50%; line-height: 1.15;">
                                    <div class="fw-bold text-truncate" style="font-size: 7.5pt; color: #1e293b;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</div>
                                    <div class="text-muted text-truncate" style="font-size: 6.2pt; font-weight: 500;">{{ $invoiceSettings['invoice_company_phone'] ?? '' }}</div>
                                </div>
                            @else
                                <div class="text-center w-100" style="line-height: 1.15;">
                                    <div class="fw-bold text-truncate text-uppercase" style="font-size: 8.5pt; color: #1e293b;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</div>
                                    <div class="text-muted text-truncate" style="font-size: 7.2pt; font-weight: 500;">{{ $invoiceSettings['invoice_company_phone'] ?? '' }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Barcode element -->
                        <div class="text-center" style="margin-bottom: 4px;">
                            <svg id="barcode-thermal-3x3" style="display: block; margin: 0 auto;"></svg>
                        </div>

                        <!-- QR & Info -->
                        <div class="row g-0 align-items-center border-bottom pb-1 mb-1">
                            <div class="col-4 text-center d-flex align-items-center justify-content-center">
                                <canvas id="qr-thermal-3x3" style="display: block;"></canvas>
                            </div>
                            <div class="col-8 ps-2 text-start" style="font-size: 7.5pt; line-height: 1.3;">
                                <div class="row g-0">
                                    <div class="col-5 text-muted">ORDER ID:</div>
                                    <div class="col-7 fw-bold text-end text-truncate">{{ $order->order_number }}</div>
                                </div>
                                <div class="row g-0">
                                    <div class="col-5 text-muted">DELIVERY:</div>
                                    <div class="col-7 text-end text-truncate">{{ $order->payment_method === 'cod' ? 'Home (COD)' : 'Home (Prepaid)' }}</div>
                                </div>
                                <div class="row g-0">
                                    <div class="col-5 text-muted">WEIGHT:</div>
                                    <div class="col-7 text-end">0.5 KG</div>
                                </div>
                                @if($order->carrier)
                                    <div class="courier-badge-thermal {{ strtolower($order->carrier) === 'pathao' ? 'courier-badge-pathao' : 'courier-badge-steadfast' }}">
                                        Shipped to: {{ ucfirst($order->carrier) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Recipient Block -->
                        <div class="text-start flex-grow-1" style="font-size: 5.5pt; line-height: 1.2; overflow: hidden; margin-bottom: 4px; padding-top: 2px;">
                            <div class="text-truncate"><strong>NAME:</strong> <span class="fw-semibold">{{ $order->shipping_name ?? $order->user?->name }}</span></div>
                            <div class="text-truncate"><strong>PHONE:</strong> <span class="fw-semibold">{{ $order->shipping_phone ?? $order->user?->phone }}</span></div>
                            <div style="margin-top: 2px;"><strong>ADDRESS:</strong> {{ $order->shipping_address }}</div>
                        </div>

                        <!-- COD Box -->
                        <div class="cod-box-thermal" style="margin-bottom: 4px;">
                            <span class="cod-title" style="font-size: 6pt; line-height: 1;">CASH ON DELIVERY</span>
                            <span class="cod-amount" style="font-size: 10pt; line-height: 1.1; margin-top: 1px;">৳ {{ number_format($order->total) }}</span>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center border-top pt-1 text-muted" style="font-size: 5.5pt; line-height: 1; height: 14px;">
                            <span>{{ now()->format('d/m/y h:ia') }}</span>
                            <a href="https://coderzonebd.com/" target="_blank" class="text-decoration-none text-muted fw-bold">Generated by Coder Zone BD ecom</a>
                        </div>
                    </div>
                </div>

                <!-- A4 Page Label -->
                <div id="label-container-a4" class="preview-card print-size-a4 d-none">
                    <div class="label-a4">
                        <!-- Shipped From Header -->
                        <div class="row g-0 align-items-center border-bottom border-dark pb-3 mb-3">
                            <div class="col-9 text-start">
                                <h6 class="fw-bold mb-1" style="font-size: 11pt; color: #1e293b; line-height: 1.3;">Shipped From:<br>{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</h6>
                                <div class="small text-muted" style="font-size: 8.5pt;">{{ $invoiceSettings['invoice_company_address'] ?? 'Uttara, Dhaka' }}</div>
                                <div class="small text-muted" style="font-size: 8.5pt;">Contact: {{ $invoiceSettings['invoice_company_phone'] ?? '' }}@if(!empty($invoiceSettings['invoice_company_email'])), {{ $invoiceSettings['invoice_company_email'] }}@endif</div>
                            </div>
                            <div class="col-3 text-end">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 45px; max-width: 100%; object-fit: contain;">
                                @else
                                    <h5 class="fw-bold text-danger mb-0" style="font-size: 12pt;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</h5>
                                    <small class="text-muted" style="font-size: 8pt;">Best Destination for Technology</small>
                                @endif
                            </div>
                        </div>

                        <!-- Shipped To & QR Section -->
                        <div class="row g-0 border-bottom border-dark pb-3 mb-3 align-items-center">
                            <div class="col-8 text-start">
                                <div class="badge bg-dark text-white mb-2 px-2 py-1" style="font-size: 8pt;">Regular</div>
                                <h6 class="fw-bold mb-1" style="font-size: 11pt; line-height: 1.3;">Shipped To:<br>{{ $order->shipping_name ?? $order->user?->name }}</h6>
                                <div class="fw-semibold small" style="font-size: 9.5pt;">Phone: {{ $order->shipping_phone ?? $order->user?->phone }}</div>
                                <div class="text-muted small" style="font-size: 8.5pt;">Secondary Phone: N/A</div>
                                <div class="mt-2 text-dark" style="font-size: 9.5pt; line-height: 1.4;"><strong class="text-muted">Address:</strong> {{ $order->shipping_address }}, {{ $order->shippingDistrict?->name ?? '' }}, {{ $order->shippingDivision?->name ?? '' }}</div>
                            </div>
                            <div class="col-4 text-end">
                                <canvas id="qr-a4"></canvas>
                            </div>
                        </div>

                        <!-- Bottom Details Grid -->
                        <div class="row g-0 align-items-stretch">
                            <!-- Left column -->
                            <div class="col-6 border-end border-dark pe-3 text-start d-flex flex-column justify-content-between">
                                <div class="mb-3">
                                    <div class="row g-0 small py-1.5 border-bottom border-light">
                                        <div class="col-5 text-muted">Weight:</div>
                                        <div class="col-7 fw-bold">0.50 Kg</div>
                                    </div>
                                    @if($order->carrier)
                                        <div class="mt-2 p-2 rounded text-center fw-bold" 
                                             style="font-size: 10pt; background-color: transparent !important; color: {{ strtolower($order->carrier) === 'pathao' ? '#ef4444' : '#22c55e' }} !important; border: 2px solid {{ strtolower($order->carrier) === 'pathao' ? '#ef4444' : '#22c55e' }} !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; margin-bottom: 8px;">
                                            Shipped to: {{ ucfirst($order->carrier) }}
                                        </div>
                                    @endif
                                    <div class="row g-0 small py-1.5 border-bottom border-light">
                                        <div class="col-5 text-muted">Order ID:</div>
                                        <div class="col-7 fw-bold">{{ $order->order_number }}</div>
                                    </div>
                                </div>
                                <div class="mt-auto py-3 px-3 border border-dark rounded bg-light">
                                    <div class="small text-muted fw-bold" style="font-size: 8.5pt;">Collectable Amount:</div>
                                    <div class="fs-3 fw-bold" style="color: #1e293b;">BDT {{ number_format($order->total, 2) }}</div>
                                </div>
                            </div>

                            <!-- Right column -->
                            <div class="col-6 ps-3 text-start d-flex flex-column justify-content-between">
                                <div class="text-center mb-2">
                                    <svg id="barcode-a4"></svg>
                                </div>
                                <div class="small" style="font-size: 8.5pt; line-height: 1.4;">
                                    <div class="row g-0 py-1 border-bottom border-light">
                                        <div class="col-5 text-muted">Order Date:</div>
                                        <div class="col-7 fw-bold">{{ $order->created_at->format('Y-m-d h:i:s A') }}</div>
                                    </div>
                                    <div class="row g-0 py-1 border-bottom border-light">
                                        <div class="col-5 text-muted">Target Hub:</div>
                                        <div class="col-7 fw-bold">{{ $order->shippingDistrict?->name ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row g-0 py-1 border-bottom border-light">
                                        <div class="col-5 text-muted">Sort:</div>
                                        <div class="col-7 fw-bold">{{ $sortType }}</div>
                                    </div>
                                    <div class="row g-0 py-1 border-bottom border-light">
                                        <div class="col-5 text-muted">Route:</div>
                                        <div class="col-7 fw-bold">{{ $order->carrier ? ucfirst($order->carrier) : 'N/A' }}</div>
                                    </div>
                                    <div class="row g-0 py-1">
                                        <div class="col-5 text-muted">Zone:</div>
                                        <div class="col-7 fw-bold">{{ $order->shippingUpazila?->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="d-flex justify-content-between align-items-center border-top border-dark mt-3 pt-2 text-muted small" style="font-size: 8pt;">
                            <span>Printed: {{ now()->format('Y-m-d h:i:s A') }}</span>
                            <a href="https://coderzonebd.com/" target="_blank" class="text-decoration-none text-muted fw-bold">Generated by Coder Zone BD ecom</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1. Invoice Mode Container (Inside zoom-wrapper for zoom support) -->
            <div id="invoice-mode-container" class="d-none" style="transform-origin: top center; transition: transform 0.1s ease;">
                <div class="invoice-a4">
                    <!-- Invoice Content Top Section -->
                    <div>
                        <!-- 1. Header (Logo left, Shop info right) -->
                        <div class="d-flex justify-content-between align-items-start" style="padding-bottom: 20px; margin-bottom: 25px; border-bottom: 2px solid #000;">
                            <!-- Logo (left) -->
                            <div>
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 60px; object-fit: contain;">
                                @else
                                    <h2 class="fw-bold text-uppercase m-0" style="font-size: 22pt; letter-spacing: 1px;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</h2>
                                @endif
                            </div>
                            
                            <!-- Shop details (right) -->
                            <div class="text-end" style="font-size: 10pt; line-height: 1.5; color: #333;">
                                <div class="fw-bold" style="font-size: 16pt; color: #000; margin-bottom: 4px;">{{ $invoiceSettings['invoice_company_name'] ?? 'Inner Collection' }}</div>
                                @if(!empty($invoiceSettings['invoice_company_address']))
                                    <div>{!! nl2br(e($invoiceSettings['invoice_company_address'])) !!}</div>
                                @endif
                                @if(!empty($invoiceSettings['invoice_company_phone']))
                                    <div>{{ $invoiceSettings['invoice_company_phone'] }}</div>
                                @endif
                                @if(!empty($invoiceSettings['invoice_company_email']))
                                    <div>{{ $invoiceSettings['invoice_company_email'] }}</div>
                                @endif
                                @if(!empty($invoiceSettings['invoice_company_domain']))
                                    <div>{{ $invoiceSettings['invoice_company_domain'] }}</div>
                                @endif
                            </div>
                        </div>

                        <!-- 2. Customer and Invoice Details Grid -->
                        <div class="d-flex justify-content-between" style="margin-bottom: 30px;">
                            <!-- Customer Details (left) -->
                            <div style="max-width: 55%;">
                                <div style="font-size: 10pt; color: #555; margin-bottom: 8px;">Invoice to:</div>
                                <div class="fw-bold" style="font-size: 14pt; color: #000; margin-bottom: 5px;">{{ $order->shipping_name ?? $order->user?->name }}</div>
                                <div style="font-size: 10pt; line-height: 1.5; color: #333;">
                                    {{ $order->shipping_address }}
                                    @if($order->shippingDistrict)
                                        , {{ $order->shippingDistrict->name }}
                                    @endif
                                    @if($order->shippingDivision)
                                        , {{ $order->shippingDivision->name }}
                                    @endif
                                </div>
                                <div style="font-size: 10pt; line-height: 1.5; color: #333; margin-top: 4px;">
                                    <strong>Phone:</strong> {{ $order->shipping_phone ?? $order->user?->phone }}
                                </div>
                            </div>
                            
                            <!-- Invoice Info (right) -->
                            <div class="text-end">
                                <div style="font-size: 36pt; font-weight: 300; letter-spacing: 2px; line-height: 1; color: #000; margin-bottom: 12px;">INVOICE</div>
                                <div style="font-size: 11pt; line-height: 1.6; color: #333;">
                                    <div><span style="letter-spacing: 1px;">Order No#</span> <strong style="font-size: 12pt; color: #000; margin-left: 8px;">{{ $order->order_number }}</strong></div>
                                    <div>
                                        <span style="letter-spacing: 1px;">Date</span> 
                                        <strong style="color: #000; margin-left: 8px;">
                                            {{ $order->created_at ? $order->created_at->format('d / m / Y  h:i:s A') : '' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Items Table -->
                        <table class="table invoice-table" style="margin-bottom: 20px;">
                            <thead>
                                <tr style="border-top: 2px solid #000; border-bottom: 2px solid #000;">
                                    <th class="text-center" style="width: 48%; font-weight: 700; text-transform: uppercase; font-size: 10pt; padding: 10px 8px; color: #000; border: none;">Item</th>
                                    <th class="text-center" style="width: 16%; font-weight: 700; text-transform: uppercase; font-size: 10pt; padding: 10px 8px; color: #000; border: none;">Quantity</th>
                                    <th class="text-center" style="width: 18%; font-weight: 700; text-transform: uppercase; font-size: 10pt; padding: 10px 8px; color: #000; border: none;">Unit Price</th>
                                    <th class="text-center" style="width: 18%; font-weight: 700; text-transform: uppercase; font-size: 10pt; padding: 10px 8px; color: #000; border: none;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                        <td class="text-center align-middle" style="padding: 14px 8px; border: none;">
                                            <div class="product-name-clamp" style="font-size: 10pt; color: #000;">
                                                {{ $item->product->name ?? $item->product_name }}
                                                @if($item->variant && $item->variant->attributeValues->count() > 0)
                                                    <span style="color: #555;">({{ $item->variant->attributeValues->pluck('value')->implode('/') }})</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center align-middle" style="font-size: 10pt; padding: 14px 8px; border: none; color: #000;">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="text-center align-middle" style="font-size: 10pt; padding: 14px 8px; border: none; color: #000;">
                                            ৳ {{ number_format($item->price) }}
                                        </td>
                                        <td class="text-center align-middle fw-semibold" style="font-size: 10pt; padding: 14px 8px; border: none; color: #000;">
                                            ৳ {{ number_format($item->price * $item->quantity) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- 4. Bottom details section (Payment left, Totals right) -->
                        <div class="d-flex justify-content-between align-items-start" style="padding-top: 8px;">
                            <!-- Payment Method Details (left) -->
                            <div style="max-width: 50%;">
                                <div class="fw-bold text-uppercase" style="font-size: 10.5pt; letter-spacing: 0.5px; color: #000; margin-bottom: 10px;">Payment Method</div>
                                
                                @if($order->payment_method === 'cod')
                                    <!-- Cash on Delivery Rounded Border Box (temp2 style) -->
                                    <div style="display: inline-block; padding: 8px 18px; border: 2px solid #000; border-radius: 8px; font-weight: 700; font-size: 12pt; font-style: italic; color: #000; background: #fff;">
                                        Cash on Delivery
                                    </div>
                                @else
                                    <!-- Wallet / Gateway payment details (temp1 style with dump fallback) -->
                                    <div style="font-size: 10pt; line-height: 1.6; color: #000;">
                                        <div class="fw-bold" style="font-size: 11pt; margin-bottom: 4px;">
                                            {{ $order->payment_method === 'bkash' ? 'bKash' : ($order->payment_method ?: 'Online Payment') }}
                                        </div>
                                        
                                        @php
                                            $checkoutFields = $order->checkout_fields_payload ?? [];
                                            $walletNo = $checkoutFields['bkash_customer_wallet'] ?? 'N/A';
                                            $transactionId = $checkoutFields['bkash_transaction_id'] ?? $order->transaction_id ?: 'N/A';
                                            $paidOn = $checkoutFields['bkash_payment_time'] ?? ($order->created_at ? $order->created_at->format('d-m-Y H:i:s') : 'N/A');
                                            
                                            if ($order->payment) {
                                                $paidOn = $order->payment->paid_at ? $order->payment->paid_at->format('d-m-Y H:i:s') : $paidOn;
                                                if ($order->payment->payment_details) {
                                                    $details = $order->payment->payment_details;
                                                    if (!empty($details['wallet_no'])) {
                                                        $walletNo = $details['wallet_no'];
                                                    }
                                                    if (!empty($details['trx_id'])) {
                                                        $transactionId = $details['trx_id'];
                                                    }
                                                    if (!empty($details['paid_on'])) {
                                                        $paidOn = $details['paid_on'];
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        <div>Wallet No: <span class="fw-semibold">{{ $walletNo }}</span></div>
                                        <div>Transection ID: <span class="fw-semibold">{{ $transactionId }}</span></div>
                                        <div>Paid on: <span class="fw-semibold">{{ $paidOn }}</span></div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Calculations Table (right) -->
                            <div style="min-width: 240px;">
                                <table class="table table-borderless" style="font-size: 10.5pt; margin: 0;">
                                    <tr>
                                        <td class="text-end" style="padding: 5px 12px 5px 0; border: none; color: #555; font-weight: 600;">Subtotal</td>
                                        <td class="text-end" style="padding: 5px 0; border: none; color: #000; font-weight: 600;">৳ {{ number_format($order->subtotal ?? ($order->total - ($order->shipping ?? 0))) }}</td>
                                    </tr>
                                    
                                    @php
                                        $taxEnabled = (bool) \App\Models\Setting::getValue('checkout', 'tax_enabled', false);
                                        $taxPercentage = (float) \App\Models\Setting::getValue('checkout', 'tax_percentage', 0);
                                    @endphp
                                    
                                    @if($taxEnabled)
                                        <tr>
                                            <td class="text-end" style="padding: 5px 12px 5px 0; border: none; color: #555; font-weight: 600;">Tax ({{ $taxPercentage }}%)</td>
                                            <td class="text-end" style="padding: 5px 0; border: none; color: #000; font-weight: 600;">৳ {{ number_format($order->tax ?? 0) }}</td>
                                        </tr>
                                    @endif
                                    
                                    <tr>
                                        <td class="text-end" style="padding: 5px 12px 5px 0; border: none; color: #555; font-weight: 600;">Shipping Charge</td>
                                        <td class="text-end" style="padding: 5px 0; border: none; color: #000; font-weight: 600;">৳ {{ number_format($order->shipping ?? 0) }}</td>
                                    </tr>
                                    
                                    <tr style="border-top: 1.5px solid #ccc;">
                                        <td class="text-end" style="padding: 12px 12px 5px 0; border: none; color: #000; font-size: 14pt; font-weight: 800;">Total</td>
                                        <td class="text-end" style="padding: 12px 0 5px 0; border: none; color: #000; font-size: 15pt; font-weight: 800;">৳ {{ number_format($order->total) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- 5. Invoice QR Code & Courier Badge (Centered) -->
                        <div class="d-flex flex-column align-items-center justify-content-center" style="margin-top: 50px; margin-bottom: 20px; text-align: center; width: 100%;">
                            <!-- QR Code canvas -->
                            <canvas id="qr-invoice" style="display: block; margin: 0 auto;"></canvas>
                            
                            <!-- Courier Badge (if carrier is set) -->
                            @if($order->carrier)
                                <div class="fw-bold text-center" 
                                     style="font-size: 13pt; display: inline-block; margin-top: 12px; padding: 7px 22px; border-radius: 6px; background-color: transparent !important; color: {{ strtolower($order->carrier) === 'pathao' ? '#ef4444' : '#22c55e' }} !important; border: 2.5px solid {{ strtolower($order->carrier) === 'pathao' ? '#ef4444' : '#22c55e' }} !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                    Shipped to: {{ ucfirst($order->carrier) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Invoice Footer (bottom section, pushed to bottom by flex) -->
                    <div style="margin-top: auto; padding-top: 40px;">
                        <!-- Authorized sign line & Thank you note -->
                        <div class="d-flex justify-content-between align-items-end" style="margin-bottom: 30px;">
                            <div style="font-size: 12pt; font-style: italic; font-weight: 600; color: #000;">
                                Thank you for your business!
                            </div>
                            
                            <div class="text-center" style="width: 200px;">
                                <div style="border-bottom: 2px solid #000; width: 100%; margin-bottom: 6px;"></div>
                                <div style="font-size: 9.5pt; font-weight: 700; text-transform: uppercase; color: #000; letter-spacing: 0.5px;">Authorized Signed</div>
                            </div>
                        </div>
                        
                        <!-- Bottom Colored Bar -->
                        @php
                            $footerBgColor = $invoiceSettings['invoice_footer_bg_color'] ?? '';
                            if (empty($footerBgColor)) {
                                $footerBgColor = $primaryColor;
                            }
                        @endphp
                        <div class="invoice-footer-bar" style="background-color: {{ $footerBgColor }} !important;">
                            <div>
                                Printed: <span class="printed-time-span">{{ now()->setTimezone('Asia/Dhaka')->format('Y-m-d h:i:s A') }}</span>
                            </div>
                            <div>
                                Generated by <a href="https://coderzonebd.com/" target="_blank" style="color: #fff; text-decoration: none; font-weight: 700;">Coder Zone BD ecom</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Barcode and QR Code generation libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

<script>
    // State management variables
    let currentMode = localStorage.getItem('print_pref_mode') || 'label'; // 'label' or 'invoice'
    let currentSize = localStorage.getItem('print_pref_size') || '2x3'; // '2x3', '3x3', or 'a4'
    let currentScale = parseFloat(localStorage.getItem('print_pref_scale') || '1.0'); // Zoom scale factor
    const orderNumber = '{{ $order->order_number }}';
    const barcodeValue = '{{ $barcodeValue }}';
    const barcodeText = '{{ $barcodeText }}';

    // Structured text data for label QR Codes
    const qrValueText = `Order ID: ${orderNumber}
Name: {{ $order->shipping_name ?? $order->user?->name }}
Phone: {{ $order->shipping_phone ?? $order->user?->phone }}
Address: {{ $order->shipping_address ?? '' }}, {{ $order->shippingDistrict?->name ?? '' }}, {{ $order->shippingDivision?->name ?? '' }}
Amount to Collect: {{ number_format($order->total) }} BDT
Created: {{ $order->created_at->format('d/m/Y') }}@if($order->tracking_number)

{{ $order->carrier ? (strtolower($order->carrier) === 'pathao' ? 'Pathao' : (strtolower($order->carrier) === 'steadfast' ? 'Steadfast' : ucfirst($order->carrier))) : 'Courier' }} ID: {{ $order->tracking_number }}@endif`;

    // Detailed text data for Invoice QR Code
    const qrInvoiceValueText = {!! json_encode($qrInvoiceText) !!};

    document.addEventListener('DOMContentLoaded', function () {
        initializeCodes();
        updatePrintedTime();

        // Apply initial states
        if (currentMode === 'invoice') {
            toggleMode('invoice');
        } else {
            toggleMode('label');
            selectSize(currentSize);
        }
        applyZoom();

        // Prevent default browser zoom on Ctrl + Wheel and call custom zoom
        const previewArea = document.getElementById('previewArea');
        if (previewArea) {
            previewArea.addEventListener('wheel', function (e) {
                if (e.ctrlKey) {
                    e.preventDefault();
                    if (e.deltaY < 0) {
                        zoom(0.05); // Scroll Up -> Zoom In
                    } else {
                        zoom(-0.05); // Scroll Down -> Zoom Out
                    }
                }
            }, { passive: false });
        }
    });

    window.addEventListener('beforeprint', function() {
        updatePrintedTime();
    });

    function updatePrintedTime() {
        const now = new Date();
        const options = { 
            timeZone: 'Asia/Dhaka', 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true 
        };
        const formatter = new Intl.DateTimeFormat('en-US', options);
        const parts = formatter.formatToParts(now);
        
        let year, month, day, hour, minute, second, dayPeriod;
        for (const part of parts) {
            if (part.type === 'year') year = part.value;
            if (part.type === 'month') month = part.value;
            if (part.type === 'day') day = part.value;
            if (part.type === 'hour') hour = part.value;
            if (part.type === 'minute') minute = part.value;
            if (part.type === 'second') second = part.value;
            if (part.type === 'dayPeriod') dayPeriod = part.value;
        }
        
        const formatted = `${year}-${month}-${day} ${hour}:${minute}:${second} ${dayPeriod}`;
        document.querySelectorAll('.printed-time-span').forEach(el => {
            el.textContent = formatted;
        });
    }

    function initializeCodes() {
        // Determine dynamic barcode width based on character length to prevent container overflow
        const isLongBarcode = barcodeValue.length > 10;
        const width2x3 = isLongBarcode ? 0.8 : 1.2;
        const width3x3 = isLongBarcode ? 1.0 : 1.5;
        const widthA4 = isLongBarcode ? 1.3 : 2.0;

        // 1. Generate Barcodes using JsBarcode
        try {
            // Thermal 2x3 Barcode
            JsBarcode("#barcode-thermal-2x3", barcodeValue, {
                format: "CODE128",
                width: width2x3,
                height: 20,
                displayValue: true,
                text: barcodeText,
                fontSize: 7,
                margin: 0
            });

            // Thermal 3x3 Barcode
            JsBarcode("#barcode-thermal-3x3", barcodeValue, {
                format: "CODE128",
                width: width3x3,
                height: 25,
                displayValue: true,
                text: barcodeText,
                fontSize: 8,
                margin: 0
            });

            // A4 Barcode
            JsBarcode("#barcode-a4", barcodeValue, {
                format: "CODE128",
                width: widthA4,
                height: 48,
                displayValue: true,
                text: barcodeText,
                fontSize: 10,
                margin: 2
            });
        } catch (e) {
            console.error("Barcode generation failed: ", e);
        }

        // 2. Helper function to generate high-resolution QR codes as img elements, fixing UTF-8/Bengali encoding issues
        function generateQrImage(canvasId, value, displaySize) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            try {
                // Fix UTF-8 encoding bug in QRious (crucial for Bengali/Unicode script in Customer name/Address)
                const encodedValue = unescape(encodeURIComponent(value));
                
                // Create QR code on the temporary canvas at 2x resolution
                const qr = new QRious({
                    element: canvas,
                    value: encodedValue,
                    size: displaySize * 2,
                    level: 'L'
                });
                
                // Replace canvas with a crisp image element to prevent print blurriness and blank render bugs
                const img = document.createElement('img');
                img.src = canvas.toDataURL('image/png');
                img.style.width = displaySize + 'px';
                img.style.height = displaySize + 'px';
                img.style.display = 'block';
                
                // Copy margin and class attributes from original canvas
                if (canvas.style.margin) img.style.margin = canvas.style.margin;
                if (canvas.className) img.className = canvas.className;
                
                canvas.parentNode.replaceChild(img, canvas);
            } catch (err) {
                console.error(`Failed to generate QR Image for #${canvasId}:`, err);
            }
        }

        // 3. Generate QR Codes
        generateQrImage('qr-thermal-2x3', qrValueText, 60);
        generateQrImage('qr-thermal-3x3', qrValueText, 80);
        generateQrImage('qr-a4', qrValueText, 120);
        generateQrImage('qr-invoice', qrInvoiceValueText, 180);
    }

    // Zoom functionalities
    function zoom(delta) {
        currentScale = Math.min(Math.max(currentScale + delta, 0.5), 2.5);
        localStorage.setItem('print_pref_scale', currentScale);
        applyZoom();
    }

    function resetZoom() {
        currentScale = 1.0;
        localStorage.setItem('print_pref_scale', currentScale);
        applyZoom();
    }

    function applyZoom() {
        const container = currentMode === 'label' ? document.getElementById('label-mode-container') : document.getElementById('invoice-mode-container');
        if (container) {
            container.style.transform = `scale(${currentScale})`;
            // Adjust margin to handle flex containment scroll overlap
            if (currentScale > 1.0) {
                const offsetFactor = currentMode === 'label' ? 320 : 900;
                container.style.marginBottom = `${(currentScale - 1) * offsetFactor}px`;
            } else {
                container.style.marginBottom = '0px';
            }
        }
    }

    // Toggle between Invoice and Print Label modes
    function toggleMode(mode) {
        currentMode = mode;
        localStorage.setItem('print_pref_mode', mode);

        const btnInvoice = document.getElementById('btn-toggle-invoice');
        const btnLabel = document.getElementById('btn-toggle-label');
        const invoiceWrap = document.getElementById('invoice-mode-container');
        const labelWrap = document.getElementById('label-mode-container');
        const sizeSelector = document.querySelector('.label-size-selector');
        const btnPrint = document.getElementById('btn-trigger-print');
        const zoomControls = document.querySelector('.zoom-controls-floating');

        // Reset dynamic body print classes
        document.body.classList.remove('print-active-2x3', 'print-active-3x3', 'print-active-a4', 'print-active-invoice');

        if (mode === 'invoice') {
            btnInvoice.className = 'btn btn-primary';
            btnLabel.className = 'btn btn-outline-primary';
            invoiceWrap.classList.remove('d-none');
            labelWrap.classList.add('d-none');
            sizeSelector.classList.add('d-none');
            zoomControls.classList.remove('d-none');
            btnPrint.disabled = false;
            btnPrint.innerHTML = '<i class="bi bi-printer-fill me-1"></i> Print Invoice';

            document.body.classList.add('print-active-invoice');

            let styleTag = document.getElementById('dynamic-print-page-style');
            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = 'dynamic-print-page-style';
                document.head.appendChild(styleTag);
            }
            styleTag.innerHTML = `@media print { @page { size: A4 portrait; margin: 10mm; } }`;
        } else {
            btnInvoice.className = 'btn btn-outline-primary';
            btnLabel.className = 'btn btn-primary';
            invoiceWrap.classList.add('d-none');
            labelWrap.classList.remove('d-none');
            sizeSelector.classList.remove('d-none');
            zoomControls.classList.remove('d-none');
            btnPrint.disabled = false;
            btnPrint.innerHTML = '<i class="bi bi-printer-fill me-1"></i> Print Label';

            // Reset label size classes
            selectSize(currentSize);
        }

        // Call applyZoom to preserve the user's zoom factor instead of resetting it
        applyZoom();
    }

    // Change sizes dynamically
    function selectSize(size) {
        currentSize = size;
        localStorage.setItem('print_pref_size', size);

        const btn2x3 = document.getElementById('btn-size-2x3');
        const btn3x3 = document.getElementById('btn-size-3x3');
        const btnA4 = document.getElementById('btn-size-a4');

        const label2x3 = document.getElementById('label-container-2x3');
        const label3x3 = document.getElementById('label-container-3x3');
        const labelA4 = document.getElementById('label-container-a4');

        // Reset button states
        btn2x3.className = 'btn btn-outline-primary';
        btn3x3.className = 'btn btn-outline-primary';
        btnA4.className = 'btn btn-outline-primary';

        // Hide all templates
        label2x3.classList.add('d-none');
        label3x3.classList.add('d-none');
        labelA4.classList.add('d-none');

        // Reset dynamic body print classes
        document.body.classList.remove('print-active-2x3', 'print-active-3x3', 'print-active-a4');
        document.body.classList.add('print-active-' + size);

        // Dynamically update the print page size CSS
        let styleTag = document.getElementById('dynamic-print-page-style');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'dynamic-print-page-style';
            document.head.appendChild(styleTag);
        }

        // Apply dynamic sizing to style tag
        if (size === '2x3') {
            btn2x3.className = 'btn btn-primary';
            label2x3.classList.remove('d-none');
            styleTag.innerHTML = `@media print { @page { size: A4 portrait; margin: 8mm; } }`;
        } else if (size === '3x3') {
            btn3x3.className = 'btn btn-primary';
            label3x3.classList.remove('d-none');
            styleTag.innerHTML = `@media print { @page { size: A4 portrait; margin: 8mm; } }`;
        } else if (size === 'a4') {
            btnA4.className = 'btn btn-primary';
            labelA4.classList.remove('d-none');
            styleTag.innerHTML = `@media print { @page { size: A4 portrait; margin: 8mm; } }`;
        }

        // Call applyZoom to preserve the user's zoom factor instead of resetting it
        applyZoom();
    }
</script>
@endpush
