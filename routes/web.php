<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderTrackingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SavedPaymentMethodController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\IntegrationSettingController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\AbandonedCartController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\BusinessIntelligenceController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\LoyaltyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Global login route for auth middleware redirect
Route::get('login', fn() => redirect()->route('admin.login'))->name('login');

// Admin Auth Routes (Guest)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');

    // Admin Protected Routes
    Route::middleware(['auth:web', 'is_admin', 'admin_permission'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // Global Search
        Route::get('/global-search', [\App\Http\Controllers\Admin\GlobalSearchController::class, 'search'])->name('global-search');

        // Categories CRUD
        Route::resource('categories', CategoryController::class)->except(['show']);

        // Pages CRUD
        Route::resource('pages', PageController::class)->except(['show']);

        // Contact Messages
        Route::prefix('contact-messages')->name('contact-messages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('index');
            Route::get('/{contactMessage}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'show'])->name('show');
            Route::delete('/{contactMessage}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])->name('destroy');
            Route::post('/{contactMessage}/mark-read', [\App\Http\Controllers\Admin\ContactMessageController::class, 'markAsRead'])->name('mark-read');
            Route::post('/mark-all-read', [\App\Http\Controllers\Admin\ContactMessageController::class, 'markAllAsRead'])->name('mark-all-read');
        });

        // Products CRUD
        Route::resource('products', ProductController::class);

        // Product Variants
        Route::post('products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
        Route::put('products/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
        Route::put('products/{product}/variants-matrix', [ProductController::class, 'updateVariantMatrix'])->name('products.variants.matrix-update');
        Route::post('products/{product}/variants/generate', [ProductController::class, 'generateVariants'])->name('products.variants.generate');
        Route::put('products/{product}/variants-bulk', [ProductController::class, 'bulkUpdateVariants'])->name('products.variants.bulk-update');
        Route::delete('products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');

        // Product Images
        Route::post('products/{product}/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->name('products.images.primary');
        Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');

        // Attributes (Size, Color, etc.)
        Route::resource('attributes', AttributeController::class)->except(['show']);
        Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
        Route::put('attributes/values/{value}', [AttributeController::class, 'updateValue'])->name('attributes.values.update');
        Route::delete('attributes/values/{value}', [AttributeController::class, 'destroyValue'])->name('attributes.values.destroy');

        // Orders (Read + Status Update)
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders/bulk-action', [OrderController::class, 'bulkAction'])->name('orders.bulk-action');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::patch('orders/{order}/source', [OrderController::class, 'updateSource'])->name('orders.update-source');

        // Order Tracking
        Route::get('orders/{order}/tracking', [OrderTrackingController::class, 'edit'])->name('orders.tracking.edit');
        Route::put('orders/{order}/tracking', [OrderTrackingController::class, 'update'])->name('orders.tracking.update');
        Route::post('orders/{order}/tracking/event', [OrderTrackingController::class, 'addEvent'])->name('orders.tracking.add-event');
        Route::delete('orders/{order}/tracking/event/{eventId}', [OrderTrackingController::class, 'deleteEvent'])->name('orders.tracking.delete-event');
        Route::post('orders/{order}/mark-delivered', [OrderTrackingController::class, 'markDelivered'])->name('orders.mark-delivered');

        // Payments (Read Only)
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/saved-methods', [SavedPaymentMethodController::class, 'index'])->name('payments.saved-methods');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');

        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{id}/role', [UserController::class, 'updateRole'])->name('users.update-role');
        Route::patch('users/{id}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('users/{id}', [UserController::class, 'show'])->name('users.show');

        // Roles & Permissions
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [AdminRoleController::class, 'index'])->name('index');
            Route::post('/', [AdminRoleController::class, 'store'])->name('store');
            Route::get('{role}/edit', [AdminRoleController::class, 'edit'])->name('edit');
            Route::put('{role}', [AdminRoleController::class, 'update'])->name('update');
            Route::delete('{role}', [AdminRoleController::class, 'destroy'])->name('destroy');
        });

        // Media Library
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::get('media/list', [MediaController::class, 'list'])->name('media.list');
        Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
        Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
        Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        Route::post('media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');


        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            // Site Settings (CMS)
            Route::get('site', [SiteSettingController::class, 'index'])->name('site.index');
            Route::get('site/create', [SiteSettingController::class, 'create'])->name('site.create');
            Route::post('site', [SiteSettingController::class, 'store'])->name('site.store');
            Route::get('site/{group}/edit', [SiteSettingController::class, 'editGroup'])->name('site.edit-group');
            Route::put('site/{group}', [SiteSettingController::class, 'updateGroup'])->name('site.update-group');
            Route::delete('site/{group}/{key}/delete-image', [SiteSettingController::class, 'deleteImage'])->name('site.delete-image');
            Route::delete('site/{setting}', [SiteSettingController::class, 'destroy'])->name('site.destroy');

            // Payment Gateways
            Route::get('payment-gateways', [PaymentGatewayController::class, 'index'])->name('payment-gateways');
            Route::get('payment-gateways/{gateway}/edit', [PaymentGatewayController::class, 'edit'])->name('payment-gateways.edit');
            Route::put('payment-gateways/{gateway}', [PaymentGatewayController::class, 'update'])->name('payment-gateways.update');
            Route::patch('payment-gateways/{gateway}/toggle', [PaymentGatewayController::class, 'toggle'])->name('payment-gateways.toggle');
            Route::post('payment-gateways/order', [PaymentGatewayController::class, 'updateOrder'])->name('payment-gateways.order');

            // Shipping Methods
            Route::get('shipping-methods', [ShippingMethodController::class, 'index'])->name('shipping-methods');
            Route::get('shipping-methods/create', [ShippingMethodController::class, 'create'])->name('shipping-methods.create');
            Route::post('shipping-methods', [ShippingMethodController::class, 'store'])->name('shipping-methods.store');
            Route::get('shipping-methods/{method}/edit', [ShippingMethodController::class, 'edit'])->name('shipping-methods.edit');
            Route::put('shipping-methods/{method}', [ShippingMethodController::class, 'update'])->name('shipping-methods.update');
            Route::delete('shipping-methods/{method}', [ShippingMethodController::class, 'destroy'])->name('shipping-methods.destroy');
            Route::patch('shipping-methods/{method}/toggle', [ShippingMethodController::class, 'toggle'])->name('shipping-methods.toggle');
            Route::post('shipping-methods/order', [ShippingMethodController::class, 'updateOrder'])->name('shipping-methods.order');

            // Integrations
            Route::get('integrations', [IntegrationSettingController::class, 'index'])->name('integrations');
            Route::put('integrations', [IntegrationSettingController::class, 'update'])->name('integrations.update');
            Route::get('integrations/sms-balance', [IntegrationSettingController::class, 'smsBalance'])->name('integrations.sms-balance');

            // Order Statuses
            Route::get('order-statuses', [OrderStatusController::class, 'index'])->name('order-statuses');
            Route::post('order-statuses', [OrderStatusController::class, 'store'])->name('order-statuses.store');
            Route::put('order-statuses/{orderStatus}', [OrderStatusController::class, 'update'])->name('order-statuses.update');
            Route::delete('order-statuses/{orderStatus}', [OrderStatusController::class, 'destroy'])->name('order-statuses.destroy');
        });

        // Coupons
        Route::resource('coupons', CouponController::class);
        Route::patch('coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');
        Route::post('coupons/{coupon}/duplicate', [CouponController::class, 'duplicate'])->name('coupons.duplicate');

        // Reviews
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', [ReviewController::class, 'index'])->name('index');
            Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
            Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
            Route::post('/{review}/reject', [ReviewController::class, 'reject'])->name('reject');
            Route::patch('/{review}/toggle-featured', [ReviewController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::post('/{review}/reply', [ReviewController::class, 'reply'])->name('reply');
            Route::delete('/{review}/reply', [ReviewController::class, 'removeReply'])->name('remove-reply');
            Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-approve', [ReviewController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/bulk-delete', [ReviewController::class, 'bulkDelete'])->name('bulk-delete');
        });

        // Abandoned Carts
        Route::prefix('abandoned-carts')->name('abandoned-carts.')->group(function () {
            Route::get('/', [AbandonedCartController::class, 'index'])->name('index');
            Route::get('/export', [AbandonedCartController::class, 'export'])->name('export');
            Route::post('/bulk-action', [AbandonedCartController::class, 'bulkAction'])->name('bulk-action');
            Route::get('/{abandonedCart}', [AbandonedCartController::class, 'show'])->name('show');
            Route::post('/{abandonedCart}/mark-follow-up', [AbandonedCartController::class, 'markFollowUp'])->name('mark-follow-up');
            Route::post('/{abandonedCart}/mark-recovered', [AbandonedCartController::class, 'markRecovered'])->name('mark-recovered');
            Route::post('/{abandonedCart}/mark-cancelled', [AbandonedCartController::class, 'markCancelled'])->name('mark-cancelled');
            Route::put('/{abandonedCart}/notes', [AbandonedCartController::class, 'updateNotes'])->name('update-notes');
            Route::delete('/{abandonedCart}', [AbandonedCartController::class, 'destroy'])->name('destroy');
        });

        // Returns & Refunds
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [ReturnController::class, 'index'])->name('index');
            Route::get('/export', [ReturnController::class, 'export'])->name('export');
            Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
            Route::post('/{return}/approve', [ReturnController::class, 'approve'])->name('approve');
            Route::post('/{return}/reject', [ReturnController::class, 'reject'])->name('reject');
            Route::post('/{return}/mark-received', [ReturnController::class, 'markReceived'])->name('mark-received');
            Route::post('/{return}/process-refund', [ReturnController::class, 'processRefund'])->name('process-refund');
            Route::post('/{return}/update-refund-method', [ReturnController::class, 'updateRefundMethod'])->name('update-refund-method');
            Route::post('/{return}/add-notes', [ReturnController::class, 'addNotes'])->name('add-notes');
        });

        // Business Intelligence
        Route::prefix('bi')->name('bi.')->group(function () {
            Route::get('/', [BusinessIntelligenceController::class, 'index'])->name('index');
            Route::get('/sales-reports', [BusinessIntelligenceController::class, 'salesReports'])->name('sales-reports');
            Route::get('/inventory-alerts', [BusinessIntelligenceController::class, 'inventoryAlerts'])->name('inventory-alerts');
            Route::get('/customer-analytics', [BusinessIntelligenceController::class, 'customerAnalytics'])->name('customer-analytics');
            Route::get('/product-performance', [BusinessIntelligenceController::class, 'productPerformance'])->name('product-performance');

            // AJAX endpoints
            Route::get('/product-trends', [BusinessIntelligenceController::class, 'productTrends'])->name('product-trends');
            Route::get('/frequently-bought-together', [BusinessIntelligenceController::class, 'frequentlyBoughtTogether'])->name('frequently-bought-together');

            // Export endpoints
            Route::get('/export-sales', [BusinessIntelligenceController::class, 'exportSalesReport'])->name('export-sales');
            Route::get('/export-inventory', [BusinessIntelligenceController::class, 'exportInventoryReport'])->name('export-inventory');
            Route::get('/export-customers', [BusinessIntelligenceController::class, 'exportCustomerReport'])->name('export-customers');
        });

        // Flash Sales
        Route::prefix('flash-sales')->name('flash-sales.')->group(function () {
            Route::get('/', [FlashSaleController::class, 'index'])->name('index');
            Route::get('/create', [FlashSaleController::class, 'create'])->name('create');
            Route::post('/', [FlashSaleController::class, 'store'])->name('store');
            Route::get('/{flashSale}', [FlashSaleController::class, 'show'])->name('show');
            Route::get('/{flashSale}/edit', [FlashSaleController::class, 'edit'])->name('edit');
            Route::put('/{flashSale}', [FlashSaleController::class, 'update'])->name('update');
            Route::delete('/{flashSale}', [FlashSaleController::class, 'destroy'])->name('destroy');
            Route::post('/{flashSale}/products', [FlashSaleController::class, 'addProduct'])->name('add-product');
            Route::delete('/{flashSale}/products/{productId}', [FlashSaleController::class, 'removeProduct'])->name('remove-product');
            Route::patch('/{flashSale}/products/{product}/toggle', [FlashSaleController::class, 'toggleProduct'])->name('toggle-product');
            Route::post('/{flashSale}/end', [FlashSaleController::class, 'endEarly'])->name('end');
            Route::post('/{flashSale}/extend', [FlashSaleController::class, 'extend'])->name('extend');
            Route::post('/{flashSale}/duplicate', [FlashSaleController::class, 'duplicate'])->name('duplicate');
        });

        // Loyalty Program
        Route::prefix('loyalty')->name('loyalty.')->group(function () {
            Route::get('/', [LoyaltyController::class, 'index'])->name('index');

            // Rewards
            Route::get('/rewards', [LoyaltyController::class, 'rewards'])->name('rewards.index');
            Route::get('/rewards/create', [LoyaltyController::class, 'createReward'])->name('rewards.create');
            Route::post('/rewards', [LoyaltyController::class, 'storeReward'])->name('rewards.store');
            Route::get('/rewards/{reward}/edit', [LoyaltyController::class, 'editReward'])->name('rewards.edit');
            Route::put('/rewards/{reward}', [LoyaltyController::class, 'updateReward'])->name('rewards.update');
            Route::delete('/rewards/{reward}', [LoyaltyController::class, 'destroyReward'])->name('rewards.destroy');

            // Tiers
            Route::get('/tiers', [LoyaltyController::class, 'tiers'])->name('tiers.index');
            Route::get('/tiers/create', [LoyaltyController::class, 'createTier'])->name('tiers.create');
            Route::post('/tiers', [LoyaltyController::class, 'storeTier'])->name('tiers.store');
            Route::get('/tiers/{tier}/edit', [LoyaltyController::class, 'editTier'])->name('tiers.edit');
            Route::put('/tiers/{tier}', [LoyaltyController::class, 'updateTier'])->name('tiers.update');
            Route::delete('/tiers/{tier}', [LoyaltyController::class, 'destroyTier'])->name('tiers.destroy');

            // Members
            Route::get('/members', [LoyaltyController::class, 'members'])->name('members.index');
            Route::get('/members/export', [LoyaltyController::class, 'exportMembers'])->name('members.export');
            Route::get('/members/{user}', [LoyaltyController::class, 'showMember'])->name('members.show');
            Route::post('/members/{user}/adjust', [LoyaltyController::class, 'adjustPoints'])->name('members.adjust');

            // Redemptions
            Route::get('/redemptions', [LoyaltyController::class, 'redemptions'])->name('redemptions');

            // Transactions
            Route::get('/transactions', [LoyaltyController::class, 'transactions'])->name('transactions');
        });
    });
});

// Redirect root to admin login
Route::get('/', function () {
    return redirect()->route('admin.login');
});
