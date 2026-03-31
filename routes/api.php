<?php

use App\Http\Controllers\Api\AttributeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BkashController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FrontendSettingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderExportController;
use App\Http\Controllers\Api\OrderTrackingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShippingMethodController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CouponController as ApiCouponController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AbandonedCartController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\RelatedProductController;
use App\Http\Controllers\Api\FlashSaleController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderNoteController;
use App\Http\Controllers\Api\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication (rate limited: 5 attempts/min)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Email Verification (signed URL, no auth required)
    Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    // Public product and category endpoints
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/menu', [CategoryController::class, 'menu']);
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
    Route::get('/categories/{id}/children', [CategoryController::class, 'children'])->where('id', '[0-9]+');

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/new', [ProductController::class, 'newProducts']);
    Route::get('/products/bestsellers', [ProductController::class, 'bestsellers']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
    Route::get('/products/category/{categoryId}', [ProductController::class, 'byCategory'])->where('categoryId', '[0-9]+');
    Route::get('/products/{id}/variants', [ProductController::class, 'variants'])->where('id', '[0-9]+');

    // Public Reviews (for product pages)
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index'])->where('productId', '[0-9]+');
    Route::get('/products/{productId}/reviews/summary', [ReviewController::class, 'summary'])->where('productId', '[0-9]+');
    Route::get('/products/{productId}/reviews/featured', [ReviewController::class, 'featured'])->where('productId', '[0-9]+');

    // Product Attributes (public - for frontend filters)
    Route::get('/attributes', [AttributeController::class, 'index']);
    Route::get('/attributes/{id}', [AttributeController::class, 'show'])->where('id', '[0-9]+');

    // Payment Gateways (public - for checkout)
    Route::get('/payment-methods', [PaymentGatewayController::class, 'index']);
    Route::get('/payment-methods/{code}', [PaymentGatewayController::class, 'show']);

    // Shipping Methods (public - for checkout)
    Route::get('/shipping-methods', [ShippingMethodController::class, 'index']);
    Route::get('/shipping-methods/{code}', [ShippingMethodController::class, 'show']);
    Route::post('/shipping-methods/calculate', [ShippingMethodController::class, 'calculate']);

    // Frontend Settings (public - for CMS)
    Route::prefix('settings')->group(function () {
        Route::get('/', [FrontendSettingController::class, 'index']);
        Route::get('/hero', [FrontendSettingController::class, 'hero']);
        Route::get('/general', [FrontendSettingController::class, 'general']);
        Route::get('/social', [FrontendSettingController::class, 'social']);
        Route::get('/seo', [FrontendSettingController::class, 'seo']);
        Route::get('/footer', [FrontendSettingController::class, 'footer']);
        Route::get('/banner', [FrontendSettingController::class, 'banner']);
        Route::get('/{group}', [FrontendSettingController::class, 'showGroup']);
    });

    // Stripe (public config)
    Route::get('/stripe/config', [StripeController::class, 'config']);
    
    // Stripe webhook (no auth, verified by signature)
    Route::post('/stripe/webhook', [StripeController::class, 'webhook']);

    // bKash (public config)
    Route::get('/bkash/config', [BkashController::class, 'config']);
    
    // bKash callback (no auth, from bKash redirect)
    Route::get('/bkash/callback', [BkashController::class, 'callback']);

    // Order Tracking (public - by order number or tracking number)
    Route::prefix('track')->group(function () {
        Route::get('/order/{orderNumber}', [OrderTrackingController::class, 'trackByOrderNumber']);
        Route::get('/tracking/{trackingNumber}', [OrderTrackingController::class, 'trackByTrackingNumber']);
    });

    // Related Products (public)
    Route::prefix('products/{product}')->where(['product' => '[0-9]+'])->group(function () {
        Route::get('/related', [RelatedProductController::class, 'index']);
        Route::get('/frequently-bought-together', [RelatedProductController::class, 'frequentlyBoughtTogether']);
        Route::get('/upsell', [RelatedProductController::class, 'upsell']);
        Route::get('/cross-sell', [RelatedProductController::class, 'crossSell']);
    });
    Route::post('/cart/recommendations', [RelatedProductController::class, 'cartRecommendations']);

    // Flash Sales (public)
    Route::prefix('flash-sales')->group(function () {
        Route::get('/', [FlashSaleController::class, 'index']);
        Route::get('/featured', [FlashSaleController::class, 'featured']);
        Route::get('/upcoming', [FlashSaleController::class, 'upcoming']);
        Route::get('/{slug}', [FlashSaleController::class, 'show']);
        Route::get('/product/{productId}', [FlashSaleController::class, 'checkProduct'])->where('productId', '[0-9]+');
        Route::post('/validate-purchase', [FlashSaleController::class, 'validatePurchase']);
    });

    // Loyalty Tiers (public)
    Route::get('/loyalty/tiers', [LoyaltyController::class, 'tiers']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/email/resend', [AuthController::class, 'resendVerification']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        // User profile
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);

        // Users (Admin only for full CRUD)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show'])->where('id', '[0-9]+');
        Route::put('/users/{id}', [UserController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->where('id', '[0-9]+');
        Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->where('id', '[0-9]+');

        // Categories (Admin only for CUD)
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->where('id', '[0-9]+');

        // Products (Admin only for CUD)
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->where('id', '[0-9]+');
        Route::post('/products/bulk-action', [ProductController::class, 'bulkAction']);

        // Cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/items', [CartController::class, 'addItem']);
            Route::put('/items/{productId}', [CartController::class, 'updateItem'])->where('productId', '[0-9]+');
            Route::delete('/items/{productId}', [CartController::class, 'removeItem'])->where('productId', '[0-9]+');
            Route::delete('/', [CartController::class, 'clear']);
            // Coupon
            Route::post('/coupon', [ApiCouponController::class, 'apply']);
            Route::delete('/coupon', [ApiCouponController::class, 'remove']);
        });

        // Coupons
        Route::prefix('coupons')->group(function () {
            Route::get('/validate', [ApiCouponController::class, 'validate']);
            Route::get('/available', [ApiCouponController::class, 'available']);
        });

        // Wishlist
        Route::prefix('wishlist')->group(function () {
            Route::get('/', [WishlistController::class, 'index']);
            Route::post('/', [WishlistController::class, 'store']);
            Route::post('/toggle', [WishlistController::class, 'toggle']);
            Route::get('/check', [WishlistController::class, 'check']);
            Route::get('/count', [WishlistController::class, 'count']);
            Route::delete('/clear', [WishlistController::class, 'clear']);
            Route::delete('/{wishlist}', [WishlistController::class, 'destroy']);
            Route::delete('/product', [WishlistController::class, 'removeByProduct']);
            Route::post('/{wishlist}/move-to-cart', [WishlistController::class, 'moveToCart']);
        });

        // Reviews (authenticated)
        Route::prefix('reviews')->group(function () {
            Route::get('/my', [ReviewController::class, 'myReviews']);
            Route::post('/', [ReviewController::class, 'store']);
            Route::put('/{review}', [ReviewController::class, 'update']);
            Route::delete('/{review}', [ReviewController::class, 'destroy']);
            Route::post('/{review}/vote', [ReviewController::class, 'vote']);
            Route::delete('/{review}/vote', [ReviewController::class, 'removeVote']);
            Route::get('/can-review/{productId}', [ReviewController::class, 'canReview'])->where('productId', '[0-9]+');
        });

        // Addresses
        Route::prefix('addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index']);
            Route::post('/', [AddressController::class, 'store']);
            Route::get('/default/shipping', [AddressController::class, 'defaultShipping']);
            Route::get('/default/billing', [AddressController::class, 'defaultBilling']);
            Route::get('/{address}', [AddressController::class, 'show']);
            Route::put('/{address}', [AddressController::class, 'update']);
            Route::delete('/{address}', [AddressController::class, 'destroy']);
            Route::post('/{address}/set-default', [AddressController::class, 'setDefault']);
        });

        // Checkout Tracking (Abandoned Cart)
        Route::prefix('checkout')->group(function () {
            Route::post('/track', [AbandonedCartController::class, 'track']);
            Route::post('/recovered', [AbandonedCartController::class, 'markRecovered']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
        });

        // Returns & Refunds
        Route::prefix('returns')->group(function () {
            Route::get('/', [ReturnController::class, 'index']);
            Route::post('/', [ReturnController::class, 'store']);
            Route::get('/check-eligibility', [ReturnController::class, 'checkEligibility']);
            Route::get('/{return}', [ReturnController::class, 'show']);
            Route::post('/{return}/cancel', [ReturnController::class, 'cancel']);
            Route::post('/{return}/upload-images', [ReturnController::class, 'uploadImages']);
        });

        // Loyalty Points
        Route::prefix('loyalty')->group(function () {
            Route::get('/summary', [LoyaltyController::class, 'summary']);
            Route::get('/transactions', [LoyaltyController::class, 'transactions']);
            Route::get('/rewards', [LoyaltyController::class, 'rewards']);
            Route::post('/redeem', [LoyaltyController::class, 'redeem']);
            Route::get('/redemptions', [LoyaltyController::class, 'redemptions']);
            Route::get('/redemptions/active', [LoyaltyController::class, 'activeRedemptions']);
            Route::post('/redemptions/{redemption}/cancel', [LoyaltyController::class, 'cancelRedemption']);
            Route::post('/validate-coupon', [LoyaltyController::class, 'validateCoupon']);
            Route::get('/leaderboard', [LoyaltyController::class, 'leaderboard']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/number/{orderNumber}', [OrderController::class, 'showByNumber']);
            Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->where('id', '[0-9]+');
            Route::get('/{id}/tracking', [OrderTrackingController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/invoice', [\App\Http\Controllers\Api\InvoiceController::class, 'show'])->where('id', '[0-9]+');
            // Order Notes
            Route::get('/{id}/notes', [OrderNoteController::class, 'index'])->where('id', '[0-9]+');
            Route::post('/{id}/notes', [OrderNoteController::class, 'store'])->where('id', '[0-9]+');
            Route::delete('/{id}/notes/{noteId}', [OrderNoteController::class, 'destroy'])->where(['id' => '[0-9]+', 'noteId' => '[0-9]+']);
            // Admin only
            Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->where('id', '[0-9]+');
            Route::get('/status/{status}', [OrderController::class, 'byStatus']);
        });

        // Payments
        Route::prefix('payments')->group(function () {
            Route::get('/order/{orderId}', [PaymentController::class, 'show'])->where('orderId', '[0-9]+');
            Route::post('/', [PaymentController::class, 'store']);
            Route::post('/{paymentId}/process', [PaymentController::class, 'process'])->where('paymentId', '[0-9]+');
            // Admin only
            Route::post('/{paymentId}/refund', [PaymentController::class, 'refund'])->where('paymentId', '[0-9]+');
        });

        // Stripe payment routes
        Route::prefix('stripe')->group(function () {
            Route::post('/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
            Route::post('/confirm-payment', [StripeController::class, 'confirmPayment']);
        });

        // bKash payment routes
        Route::prefix('bkash')->group(function () {
            Route::post('/create-payment', [BkashController::class, 'createPayment']);
            Route::get('/check-status', [BkashController::class, 'checkStatus']);
            // Admin only
            Route::post('/refund', [BkashController::class, 'refund']);
        });

        // Admin: Order Export (CSV) & Audit Logs
        Route::prefix('admin')->group(function () {
            Route::get('/orders/export', [OrderExportController::class, 'export']);
            Route::get('/orders/export/download/{filename}', [OrderExportController::class, 'download']);
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->where('id', '[0-9]+');
        });
    });
});
