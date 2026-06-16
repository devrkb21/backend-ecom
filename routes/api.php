<?php

use App\Http\Controllers\Api\AttributeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BkashController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PageController;
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
use App\Http\Controllers\Api\BangladeshLocationController;
use App\Http\Controllers\Api\FlashSaleController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderNoteController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\SavedPaymentMethodController;
use App\Http\Controllers\Api\LandingPageController as ApiLandingPageController;
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
    // Authentication
    Route::prefix('public')->group(function () {
        Route::get('/loyalty/check', [App\Http\Controllers\Api\CustomerGroupController::class, 'check']);
    });
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Email Verification (signed URL, no auth required)
    Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    // Fully public routes (no internal secret)
    // Stripe config and webhook
    Route::get('/stripe/config', [StripeController::class, 'config']);
    Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->withoutMiddleware('auth:sanctum');
    Route::post('/stripe/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
    Route::post('/stripe/confirm-payment', [StripeController::class, 'confirmPayment']);

    // bKash config and callback
    Route::get('/bkash/config', [BkashController::class, 'config']);
    Route::get('/bkash/callback', [BkashController::class, 'callback'])->withoutMiddleware('auth:sanctum');
    Route::post('/bkash/create-payment', [BkashController::class, 'createPayment']);

    // Public order tracking
    Route::prefix('track')->group(function () {
        Route::get('/order/{orderNumber}', [OrderTrackingController::class, 'trackByOrderNumber']);
        Route::get('/tracking/{trackingNumber}', [OrderTrackingController::class, 'trackByTrackingNumber']);
    });

    // Public frontend data routes protected by internal secret
    Route::middleware('internal.api')->group(function () {
        // Pages
        Route::get('/pages', [PageController::class, 'index']);
        Route::get('/pages/{slug}', [PageController::class, 'show']);

        // Landing Pages
        Route::get('/landing-pages/slug/{slug}', [ApiLandingPageController::class, 'showBySlug']);

        // Categories
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/menu', [CategoryController::class, 'menu']);
        Route::get('/categories/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
        Route::get('/categories/{id}/children', [CategoryController::class, 'children'])->where('id', '[0-9]+');

        // Products (read endpoints)
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/featured', [ProductController::class, 'featured']);
        Route::get('/products/new', [ProductController::class, 'newProducts']);
        Route::get('/products/bestsellers', [ProductController::class, 'bestsellers']);
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
        Route::get('/products/category/{categoryId}', [ProductController::class, 'byCategory'])->where('categoryId', '[0-9]+');
        Route::get('/products/{id}/variants', [ProductController::class, 'variants'])->where('id', '[0-9]+');
        Route::prefix('products/{product}')->where(['product' => '[0-9]+'])->group(function () {
            Route::get('/related', [RelatedProductController::class, 'index']);
            Route::get('/frequently-bought-together', [RelatedProductController::class, 'frequentlyBoughtTogether']);
            Route::get('/upsell', [RelatedProductController::class, 'upsell']);
            Route::get('/cross-sell', [RelatedProductController::class, 'crossSell']);
        });

        // Product page reviews (public read)
        Route::get('/products/{productId}/reviews', [ReviewController::class, 'index'])->where('productId', '[0-9]+');
        Route::get('/products/{productId}/reviews/summary', [ReviewController::class, 'summary'])->where('productId', '[0-9]+');
        Route::get('/products/{productId}/reviews/featured', [ReviewController::class, 'featured'])->where('productId', '[0-9]+');

        // Attributes
        Route::get('/attributes', [AttributeController::class, 'index']);
        Route::get('/attributes/{id}', [AttributeController::class, 'show'])->where('id', '[0-9]+');

        // Payment and shipping methods
        Route::get('/payment-methods', [PaymentGatewayController::class, 'index']);
        Route::get('/payment-methods/{code}', [PaymentGatewayController::class, 'show']);
        Route::get('/shipping-methods', [ShippingMethodController::class, 'index']);
        Route::get('/shipping-methods/{code}', [ShippingMethodController::class, 'show']);
        Route::post('/shipping-methods/calculate', [ShippingMethodController::class, 'calculate']);

        // Bangladesh location dataset
        Route::prefix('locations/bd')->group(function () {
            Route::get('/divisions', [BangladeshLocationController::class, 'divisions']);
            Route::get('/districts', [BangladeshLocationController::class, 'districts']);
            Route::get('/upazilas', [BangladeshLocationController::class, 'upazilas']);
            Route::get('/unions', [BangladeshLocationController::class, 'unions']);
        });

        // Backward-compatible Bangladesh location aliases
        Route::prefix('locations')->group(function () {
            Route::get('/divisions', [BangladeshLocationController::class, 'divisions']);
            Route::get('/districts', [BangladeshLocationController::class, 'districts']);
            Route::get('/upazilas', [BangladeshLocationController::class, 'upazilas']);
            Route::get('/unions', [BangladeshLocationController::class, 'unions']);
        });

        // Frontend settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [FrontendSettingController::class, 'index']);
            Route::get('/hero', [FrontendSettingController::class, 'hero']);
            Route::get('/general', [FrontendSettingController::class, 'general']);
            Route::get('/social', [FrontendSettingController::class, 'social']);
            Route::get('/seo', [FrontendSettingController::class, 'seo']);
            Route::get('/footer', [FrontendSettingController::class, 'footer']);
            Route::get('/banner', [FrontendSettingController::class, 'banner']);
            Route::get('/checkout', [FrontendSettingController::class, 'checkout']);
            Route::get('/{group}', [FrontendSettingController::class, 'showGroup']);
        });

        // Flash sales
        Route::prefix('flash-sales')->group(function () {
            Route::get('/', [FlashSaleController::class, 'index']);
            Route::get('/featured', [FlashSaleController::class, 'featured']);
            Route::get('/upcoming', [FlashSaleController::class, 'upcoming']);
            Route::get('/{slug}', [FlashSaleController::class, 'show']);
            Route::get('/product/{productId}', [FlashSaleController::class, 'checkProduct'])->where('productId', '[0-9]+');
            Route::post('/validate-purchase', [FlashSaleController::class, 'validatePurchase']);
        });
    });

    // Contact form (public)
    Route::post('/contact', [\App\Http\Controllers\Api\ContactController::class, 'store']);

    // Checkout order placement (supports guests and authenticated users)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/cart/coupon', [ApiCouponController::class, 'apply']);
    Route::post('/checkout/track', [AbandonedCartController::class, 'track']);
    Route::get('/orders/{id}/payment-summary', [OrderController::class, 'paymentSummary'])->where('id', '[0-9]+');
    Route::get('/orders/number/{orderNumber}', [OrderController::class, 'showByNumber'])
        ->where('orderNumber', '[A-Za-z0-9._-]+');
    Route::get('/orders/number/{orderNumber}/guest', [OrderController::class, 'showByNumberForGuest'])
        ->where('orderNumber', '[A-Za-z0-9._-]+');

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

        // Users
        Route::middleware('admin_permission:users.manage')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::delete('/users/{id}', [UserController::class, 'destroy'])->where('id', '[0-9]+');
            Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->where('id', '[0-9]+');
        });
        Route::get('/users/{id}', [UserController::class, 'show'])->where('id', '[0-9]+');
        Route::put('/users/{id}', [UserController::class, 'update'])->where('id', '[0-9]+');

        // Categories (admin catalog permission required for CUD)
        Route::middleware('admin_permission:catalog.manage')->group(function () {
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{id}', [CategoryController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->where('id', '[0-9]+');
        });

        // Products (admin catalog permission required for CUD)
        Route::middleware('admin_permission:catalog.manage')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{id}', [ProductController::class, 'update'])->where('id', '[0-9]+');
            Route::delete('/products/{id}', [ProductController::class, 'destroy'])->where('id', '[0-9]+');
            Route::post('/products/bulk-action', [ProductController::class, 'bulkAction']);
        });

        // Cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/items', [CartController::class, 'addItem']);
            Route::put('/items/{productId}', [CartController::class, 'updateItem'])->where('productId', '[0-9]+');
            Route::delete('/items/{productId}', [CartController::class, 'removeItem'])->where('productId', '[0-9]+');
            Route::delete('/', [CartController::class, 'clear']);
            // Coupon
            Route::delete('/coupon', [ApiCouponController::class, 'remove']);
        });

        // Coupons
        Route::prefix('coupons')->group(function () {
            Route::get('/validate', [ApiCouponController::class, 'validate']);
            Route::get('/available', [ApiCouponController::class, 'available']);
        });

        // Related products recommendations (requires authenticated cart context)
        Route::post('/cart/recommendations', [RelatedProductController::class, 'cartRecommendations']);

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

        // Checkout recovery endpoint for authenticated contexts.
        Route::prefix('checkout')->group(function () {
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
            Route::get('/tiers', [LoyaltyController::class, 'tiers']);
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
            Route::get('/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
            Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->where('id', '[0-9]+');
            Route::get('/{id}/tracking', [OrderTrackingController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/invoice', [\App\Http\Controllers\Api\InvoiceController::class, 'show'])->where('id', '[0-9]+');
            // Order Notes
            Route::get('/{id}/notes', [OrderNoteController::class, 'index'])->where('id', '[0-9]+');
            Route::middleware('admin_permission:orders.manage')->group(function () {
                Route::post('/{id}/notes', [OrderNoteController::class, 'store'])->where('id', '[0-9]+');
                Route::delete('/{id}/notes/{noteId}', [OrderNoteController::class, 'destroy'])->where(['id' => '[0-9]+', 'noteId' => '[0-9]+']);
                Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->where('id', '[0-9]+');
                Route::get('/status/{status}', [OrderController::class, 'byStatus']);
            });
        });

        // Payments
        Route::prefix('payments')->group(function () {
            Route::get('/order/{orderId}', [PaymentController::class, 'show'])->where('orderId', '[0-9]+');
            Route::post('/', [PaymentController::class, 'store']);
            Route::post('/{paymentId}/process', [PaymentController::class, 'process'])->where('paymentId', '[0-9]+');
            Route::middleware('admin_permission:returns.manage')->group(function () {
                Route::post('/{paymentId}/refund', [PaymentController::class, 'refund'])->where('paymentId', '[0-9]+');
            });
        });

        // Stripe payment routes
        // bKash payment routes
        Route::prefix('bkash')->group(function () {
            Route::get('/check-status', [BkashController::class, 'checkStatus']);
            Route::middleware('admin_permission:returns.manage')->group(function () {
                Route::post('/refund', [BkashController::class, 'refund']);
            });
        });

        // Saved payment methods (currently Stripe)
        Route::prefix('saved-payment-methods')->group(function () {
            Route::get('/', [SavedPaymentMethodController::class, 'index']);
            Route::post('/{savedPaymentMethod}/set-default', [SavedPaymentMethodController::class, 'setDefault'])
                ->where('savedPaymentMethod', '[0-9]+');
            Route::post('/{savedPaymentMethod}/remove', [SavedPaymentMethodController::class, 'remove'])
                ->where('savedPaymentMethod', '[0-9]+');
        });

        // Admin: Order Export (CSV) & Audit Logs
        Route::prefix('admin')->middleware('is_admin')->group(function () {
            Route::get('/orders/export', [OrderExportController::class, 'export']);
            Route::get('/orders/export/download/{filename}', [OrderExportController::class, 'download']);
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->where('id', '[0-9]+');
        });
    });
});
Route::post('/webhooks/steadfast', [\App\Http\Controllers\Api\SteadfastWebhookController::class, 'handle'])->name('api.webhooks.steadfast');
