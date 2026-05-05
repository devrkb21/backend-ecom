@extends('admin.layouts.app')

@section('title', 'Site Settings')
@section('page-title', 'Site Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2"></i>Site Settings</h6>
                <a href="{{ route('admin.settings.site.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Add Setting
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Manage your website content, branding, and appearance settings.</p>
                
                <div class="row g-4">
                    @php
                        $groupIcons = [
                            'hero' => 'bi-card-image',
                            'general' => 'bi-house-gear',
                            'social' => 'bi-share',
                            'seo' => 'bi-search',
                            'footer' => 'bi-layout-text-window-reverse',
                            'banner' => 'bi-megaphone',
                            'checkout' => 'bi-cart-check',
                            'navigation' => 'bi-list',
                            'appearance' => 'bi-palette',
                            'invoice' => 'bi-file-earmark-pdf',
                        ];
                        $groupDescriptions = [
                            'hero' => 'Homepage hero section with title, subtitle, and background image',
                            'general' => 'Site name, logo, contact information, order hotline/WhatsApp settings, side-cart behavior, and currency',
                            'social' => 'Social media profile links for Facebook, Instagram, etc.',
                            'seo' => 'Meta tags, descriptions, and Open Graph settings',
                            'footer' => 'Footer text, copyright, and newsletter settings',
                            'banner' => 'Promotional banner displayed at the top of the site',
                            'checkout' => 'Use the dedicated field manager to control checkout visibility, required states, guest access, and location mode',
                            'navigation' => 'Fully customize your header menu items and links',
                            'appearance' => 'Customize primary colors, link colors, and general site styling',
                            'invoice' => 'Customize your PDF invoices and packaging slips with logo, company details, and theme colors',
                        ];
                    @endphp

                    @foreach($groups as $group)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border hover-shadow">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                            <i class="bi {{ $groupIcons[$group] ?? 'bi-gear' }} fs-4 text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">{{ ucfirst($group) }}</h6>
                                            <small class="text-muted">{{ $settings[$group]->count() }} settings</small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        {{ $groupDescriptions[$group] ?? 'Manage ' . $group . ' settings' }}
                                    </p>
                                    <a href="{{ route('admin.settings.site.edit-group', $group) }}" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-pencil me-1"></i> Edit Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Preview Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-eye me-2"></i>Quick Preview</h6>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @if(isset($settings['general']))
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">General Info</h6>
                            <table class="table table-sm table-borderless">
                                @foreach($settings['general'] as $setting)
                                    @if($setting->type !== 'image')
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">{{ $setting->label }}</td>
                                            <td>
                                                @if($setting->type === 'boolean')
                                                    @if($setting->value)
                                                        <span class="badge bg-success">Yes</span>
                                                    @else
                                                        <span class="badge bg-secondary">No</span>
                                                    @endif
                                                @else
                                                    {{ Str::limit($setting->value, 50) ?: '-' }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </div>
                    @endif

                    @if(isset($settings['hero']))
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Hero Section</h6>
                            <table class="table table-sm table-borderless">
                                @foreach($settings['hero'] as $setting)
                                    @if($setting->type !== 'image')
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">{{ $setting->label }}</td>
                                            <td>
                                                @if($setting->type === 'boolean')
                                                    @if($setting->value)
                                                        <span class="badge bg-success">Enabled</span>
                                                    @else
                                                        <span class="badge bg-secondary">Disabled</span>
                                                    @endif
                                                @else
                                                    {{ Str::limit($setting->value, 50) ?: '-' }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hover-shadow {
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
        transform: translateY(-2px);
    }
</style>
@endpush
