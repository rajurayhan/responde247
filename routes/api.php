<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SaasPublicController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/features', [App\Http\Controllers\FeatureController::class, 'index'])->middleware('reseller');
Route::get('/subscriptions/packages', [SubscriptionController::class, 'getPackages']);
Route::get('/stripe/config', [App\Http\Controllers\StripeController::class, 'getConfig'])->middleware('reseller');

// SaaS public logo route
Route::get('/saas-public/logo.png', [App\Http\Controllers\SaasPublicController::class, 'getLogo'])->middleware('reseller');

// Contact routes
Route::post('/contact', [App\Http\Controllers\ContactController::class, 'store'])->middleware('reseller');

// Stripe webhook route (no auth required but needs reseller context)
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->middleware('reseller');

// Vapi webhook route (no auth required)
Route::post('/vapi/webhook', [App\Http\Controllers\VapiWebhookController::class, 'handleWebhook']);

// Email verification route
Route::get('/verify-email/{hash}', [App\Http\Controllers\Auth\VerifyEmailController::class, '__invoke'])
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

Route::middleware(['reseller'])->group(function () {
    // Auth routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    
    // Reseller registration routes (no auth required)
    Route::post('/register-reseller', [App\Http\Controllers\Auth\ResellerRegistrationController::class, 'register']);
    Route::get('/reseller-packages', [App\Http\Controllers\Auth\ResellerRegistrationController::class, 'getPackages']);
    Route::post('/check-domain-availability', [App\Http\Controllers\Auth\ResellerRegistrationController::class, 'checkDomainAvailability']);
    Route::post('/check-email-availability', [App\Http\Controllers\Auth\ResellerRegistrationController::class, 'checkEmailAvailability']);
    Route::post('/get-reseller-domain-by-session', [App\Http\Controllers\Auth\ResellerRegistrationController::class, 'getResellerDomainBySession']);

    // Password reset routes (require reseller context)
    Route::post('/reset-password', [App\Http\Controllers\Auth\NewPasswordController::class, 'store'])->middleware('reseller');
    Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])->middleware('reseller');
});

// Email verification notification route (requires auth + reseller context)
Route::post('/email/verification-notification', [App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'reseller', 'throttle:6,1'])
    ->name('verification.send');


// User profile routes
Route::middleware(['auth:sanctum','reseller'])->group(function () {
    Route::get('/user', [App\Http\Controllers\UserController::class, 'show']);
    Route::put('/user', [App\Http\Controllers\UserController::class, 'update']);
    Route::post('/user', [App\Http\Controllers\UserController::class, 'update']); // Alternative for file uploads
    Route::put('/user/password', [App\Http\Controllers\UserController::class, 'changePassword']);
});

// Admin routes (protected)
Route::middleware(['auth:sanctum', 'admin','reseller'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{user}', [UserController::class, 'updateUser']);
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
    Route::put('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    
    // Get users for assistant assignment
    Route::get('/admin/users/for-assignment', [UserController::class, 'getUsersForAssignment']);
    
    // Contact management routes
    Route::get('/admin/contacts', [App\Http\Controllers\ContactController::class, 'index']);
    Route::get('/admin/contacts/{id}', [App\Http\Controllers\ContactController::class, 'show']);
    Route::put('/admin/contacts/{id}/status', [App\Http\Controllers\ContactController::class, 'updateStatus']);
});

// Assistant routes (protected)
Route::middleware(['auth:sanctum','reseller'])->group(function () {
    Route::prefix('assistants')->group(function () {
        Route::get('/', [App\Http\Controllers\AssistantController::class, 'index']);
        Route::post('/', [App\Http\Controllers\AssistantController::class, 'store']);
        Route::get('/{assistantId}', [App\Http\Controllers\AssistantController::class, 'show']);
        Route::put('/{assistantId}', [App\Http\Controllers\AssistantController::class, 'update']);
        Route::delete('/{assistantId}', [App\Http\Controllers\AssistantController::class, 'destroy']);
        Route::get('/{assistantId}/stats', [App\Http\Controllers\AssistantController::class, 'stats']);
    });
    
    // Admin assistant routes
    Route::middleware('admin')->prefix('admin/assistants')->group(function () {
        Route::get('/', [App\Http\Controllers\AssistantController::class, 'adminIndex']);
    });
    
    // Twilio phone number routes
    Route::prefix('twilio')->group(function () {
        Route::get('/available-numbers', [App\Http\Controllers\TwilioController::class, 'getAvailableNumbers']);
        Route::post('/purchase-number', [App\Http\Controllers\TwilioController::class, 'purchaseNumber']);
        Route::get('/purchased-numbers', [App\Http\Controllers\TwilioController::class, 'getPurchasedNumbers']);
        Route::delete('/release-number', [App\Http\Controllers\TwilioController::class, 'releaseNumber']);
    });
});

// Dashboard routes
Route::middleware(['auth:sanctum', 'reseller'])->group(function () {
    Route::get('/dashboard/stats', [App\Http\Controllers\DashboardController::class, 'stats']);
    Route::get('/dashboard/activity', [App\Http\Controllers\DashboardController::class, 'activity']);
    
    // Call logs routes
    Route::prefix('call-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\CallLogController::class, 'index']);
        Route::get('/list', [App\Http\Controllers\CallLogController::class, 'list']); // Optimized list endpoint
        Route::get('/search', [App\Http\Controllers\CallLogController::class, 'search']); // Full-text search endpoint
        Route::get('/stats', [App\Http\Controllers\CallLogController::class, 'stats']);
        Route::get('/{callId}', [App\Http\Controllers\CallLogController::class, 'show']);
    });
    

});

// Admin dashboard routes
Route::middleware(['auth:sanctum', 'admin', 'reseller'])->group(function () {
    Route::get('/admin/dashboard/stats', [App\Http\Controllers\DashboardController::class, 'adminStats']);
    Route::get('/admin/dashboard/activity', [App\Http\Controllers\DashboardController::class, 'adminActivity']);
});

// Subscription routes (protected)
Route::middleware(['auth:sanctum', 'reseller'])->group(function () {
    Route::get('/subscriptions/packages', [SubscriptionController::class, 'getPackages']);
    Route::get('/subscriptions/current', [SubscriptionController::class, 'getCurrentSubscription']);
    Route::get('/subscriptions/usage', [SubscriptionController::class, 'getUsage']); 
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);
    Route::post('/subscriptions/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('/subscriptions/downgrade', [SubscriptionController::class, 'downgrade']);
    Route::post('/subscriptions/reactivate', [SubscriptionController::class, 'reactivate']);
    Route::get('/subscriptions/{id}', [SubscriptionController::class, 'getSubscription']);
    
    // Stripe routes
    Route::prefix('stripe')->group(function () {
        Route::post('/payment-intent', [App\Http\Controllers\StripeController::class, 'createPaymentIntent']);
        Route::post('/subscription', [App\Http\Controllers\StripeController::class, 'createSubscription']);
        Route::post('/subscription/cancel', [App\Http\Controllers\StripeController::class, 'cancelSubscription']);
        Route::post('/subscription/update', [App\Http\Controllers\StripeController::class, 'updateSubscription']);
        Route::get('/subscription/details', [App\Http\Controllers\StripeController::class, 'getSubscriptionDetails']);
    });
    
    // Reseller Stripe configuration routes (admin only)
    Route::prefix('admin/stripe')->group(function () {
        Route::get('/config', [App\Http\Controllers\ResellerStripeController::class, 'getConfig']);
        Route::post('/config', [App\Http\Controllers\ResellerStripeController::class, 'updateConfig']);
        Route::post('/test', [App\Http\Controllers\ResellerStripeController::class, 'testConfig']);
    });
    
    // Public Stripe config for frontend
    Route::get('/stripe/public-config', [App\Http\Controllers\ResellerStripeController::class, 'getPublicConfig']);
    
    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\TransactionController::class, 'store']);
        Route::get('/{transactionId}', [App\Http\Controllers\TransactionController::class, 'show']);
        Route::put('/{transactionId}/status', [App\Http\Controllers\TransactionController::class, 'updateStatus']);
        Route::post('/{transactionId}/process', [App\Http\Controllers\TransactionController::class, 'processPayment']);
    });
});

// Reseller Admin specific routes (reseller_admin only)
Route::middleware(['auth:sanctum', 'reseller_admin'])->group(function () {
    Route::prefix('reseller')->group(function () {
        Route::get('/subscription', [App\Http\Controllers\ResellerController::class, 'getSubscription']);
        Route::get('/transactions', [App\Http\Controllers\ResellerController::class, 'getTransactions']);
        Route::get('/usage', [App\Http\Controllers\ResellerController::class, 'getUsage']);
        Route::post('/subscribe', [App\Http\Controllers\ResellerController::class, 'subscribe']);
        Route::post('/retry-payment', [App\Http\Controllers\ResellerController::class, 'retryPayment']);
    });
});

// Admin subscription routes (protected)
Route::middleware(['auth:sanctum', 'admin', 'reseller'])->group(function () {
    Route::prefix('admin/subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'adminGetSubscriptions']);
        Route::get('/usage-overview', [SubscriptionController::class, 'adminUsageOverview']);
        Route::get('/packages', [SubscriptionController::class, 'adminGetPackages']);
        Route::post('/packages', [SubscriptionController::class, 'adminCreatePackage']);
        Route::post('/packages/initialize-defaults', [SubscriptionController::class, 'adminInitializeDefaultPackages']);
        Route::put('/packages/{id}', [SubscriptionController::class, 'adminUpdatePackage']);
        Route::delete('/packages/{id}', [SubscriptionController::class, 'adminDeletePackage']);
        
        // Custom subscription routes
        Route::prefix('custom')->group(function () {
            Route::post('/create', [App\Http\Controllers\CustomSubscriptionController::class, 'createCustomSubscription']);
            Route::get('/', [App\Http\Controllers\CustomSubscriptionController::class, 'getCustomSubscriptions']);
            Route::post('/activate', [App\Http\Controllers\CustomSubscriptionController::class, 'activateSubscription']);
            Route::post('/resend-payment-link', [App\Http\Controllers\CustomSubscriptionController::class, 'resendPaymentLink']);
        });
    });
    
    // Admin transaction routes
    Route::prefix('admin/transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\TransactionController::class, 'adminIndex']);
        Route::get('/stats', [App\Http\Controllers\TransactionController::class, 'adminStats']);
    });

    // Admin feature routes
    Route::prefix('admin/features')->group(function () {
        Route::get('/', [App\Http\Controllers\FeatureController::class, 'adminIndex']);
        Route::post('/', [App\Http\Controllers\FeatureController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\FeatureController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\FeatureController::class, 'destroy']);
    });
    
    // Admin call logs routes
    Route::prefix('admin/call-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CallLogController::class, 'index']);
        Route::get('/stats', [App\Http\Controllers\Admin\CallLogController::class, 'stats']);
        Route::get('/{callId}', [App\Http\Controllers\Admin\CallLogController::class, 'show']);
    });
    
    // Admin users routes
    // Route::get('/admin/users', function (Request $request) {
    //     $users = \App\Models\User::select('id', 'name', 'email', 'role', 'status')
    //         ->orderBy('name')
    //         ->get();
        
    //     return response()->json([
    //         'success' => true,
    //         'data' => $users
    //     ]);
    // });
});

// Super Admin routes (super_admin only)
Route::middleware(['auth:sanctum', 'super_admin'])->group(function () {
    // Reseller Management
    Route::prefix('super-admin/resellers')->group(function () {
        Route::get('/', [App\Http\Controllers\SuperAdmin\ResellerController::class, 'index']);
        Route::get('/{resellerId}', [App\Http\Controllers\SuperAdmin\ResellerController::class, 'show']);
    });

    // Reseller Packages Management
    Route::prefix('super-admin/reseller-packages')->group(function () {
        Route::get('/', [App\Http\Controllers\SuperAdmin\ResellerPackageController::class, 'index']);
        Route::post('/', [App\Http\Controllers\SuperAdmin\ResellerPackageController::class, 'store']);
        Route::get('/{resellerPackage}', [App\Http\Controllers\SuperAdmin\ResellerPackageController::class, 'show']);
        Route::put('/{resellerPackage}', [App\Http\Controllers\SuperAdmin\ResellerPackageController::class, 'update']);
        Route::delete('/{resellerPackage}', [App\Http\Controllers\SuperAdmin\ResellerPackageController::class, 'destroy']);
        Route::patch('/{resellerPackage}/toggle-status', [App\Http\Controllers\SuperAdmin\ResellerPackageController::class, 'toggleStatus']);
    });

    // Reseller Subscriptions Management
    Route::prefix('super-admin/reseller-subscriptions')->group(function () {
        Route::get('/', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'store']);
        Route::get('/{resellerSubscription}', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'show']);
        Route::put('/{resellerSubscription}', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'update']);
        Route::patch('/{resellerSubscription}/cancel', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'cancel']);
        Route::patch('/{resellerSubscription}/reactivate', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'reactivate']);
        Route::post('/{resellerSubscription}/resend-payment-link', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'resendPaymentLink']);
        Route::get('/usage/statistics', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'usageStatistics']);
        Route::get('/usage/overage-report', [App\Http\Controllers\SuperAdmin\ResellerSubscriptionController::class, 'overageReport']);
    });

    // Reseller Transactions Management
    Route::prefix('super-admin/reseller-transactions')->group(function () {
        Route::get('/', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'index']);
        Route::post('/', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'store']);
        Route::get('/{resellerTransaction}', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'show']);
        Route::put('/{resellerTransaction}', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'update']);
        Route::patch('/{resellerTransaction}/mark-completed', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'markCompleted']);
        Route::patch('/{resellerTransaction}/mark-failed', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'markFailed']);
        Route::post('/{resellerTransaction}/refund', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'processRefund']);
        Route::get('/financial/summary', [App\Http\Controllers\SuperAdmin\ResellerTransactionController::class, 'financialSummary']);
    });

    // Reseller Stripe Integration
    Route::prefix('super-admin/reseller-stripe')->group(function () {
        Route::post('/subscription', [App\Http\Controllers\ResellerStripeController::class, 'createSubscription']);
        Route::post('/subscription/cancel', [App\Http\Controllers\ResellerStripeController::class, 'cancelSubscription']);
        Route::post('/subscription/update', [App\Http\Controllers\ResellerStripeController::class, 'updateSubscription']);
        Route::get('/subscription/details', [App\Http\Controllers\ResellerStripeController::class, 'getSubscriptionDetails']);
        Route::post('/payment-link', [App\Http\Controllers\ResellerStripeController::class, 'createPaymentLink']);
    });

    // Usage Reports
    Route::prefix('super-admin/usage-reports')->group(function () {
        Route::get('/', [App\Http\Controllers\SuperAdmin\UsageReportController::class, 'index']);
        Route::get('/export', [App\Http\Controllers\SuperAdmin\UsageReportController::class, 'export']);
    });
});

// Settings routes (admin only)
Route::middleware(['auth:sanctum', 'admin', 'reseller'])->group(function () {
    Route::prefix('admin/settings')->group(function () {
        Route::get('/', [App\Http\Controllers\SettingController::class, 'index']);
        Route::get('/{key}', [App\Http\Controllers\SettingController::class, 'show']);
        Route::put('/{key}', [App\Http\Controllers\SettingController::class, 'update']);
    });
});

// Public assistant templates endpoint
Route::get('/assistant-templates', [App\Http\Controllers\SettingController::class, 'getAssistantTemplates'])->middleware('reseller');

// System settings routes
Route::middleware(['auth:sanctum', 'reseller'])->group(function () {
    Route::get('/system-settings', [App\Http\Controllers\SystemSettingController::class, 'index']);
    Route::post('/system-settings', [App\Http\Controllers\SystemSettingController::class, 'update']);
    Route::post('/system-settings/initialize-defaults', [App\Http\Controllers\SystemSettingController::class, 'initializeDefaults']);
});
Route::get('/public-settings', [App\Http\Controllers\SystemSettingController::class, 'getPublicSettings'])->middleware('reseller');

// Reseller settings routes (reseller admin only)
Route::middleware(['auth:sanctum', 'reseller'])->group(function () {
    Route::prefix('reseller/settings')->group(function () {
        Route::get('/', [App\Http\Controllers\ResellerSettingController::class, 'index']);
        Route::post('/', [App\Http\Controllers\ResellerSettingController::class, 'update']);
        Route::post('/initialize', [App\Http\Controllers\ResellerSettingController::class, 'initializeDefaults']);
        Route::post('/test-mail', [App\Http\Controllers\ResellerSettingController::class, 'testMailConfig']);
        Route::get('/{key}', [App\Http\Controllers\ResellerSettingController::class, 'getSetting']);
        Route::put('/{key}', [App\Http\Controllers\ResellerSettingController::class, 'setSetting']);
    });
});
Route::get('/reseller/public-settings', [App\Http\Controllers\ResellerSettingController::class, 'getPublicSettings']);



// Temporary Twilio diagnostic route
Route::get('/twilio/diagnostics', function () {
    $twilioService = app(\App\Services\TwilioService::class);
    $results = $twilioService->runDiagnostics();
    
    return response()->json([
        'success' => true,
        'data' => $results,
        'message' => 'Twilio diagnostics completed'
    ]);
})->middleware(['auth:sanctum', 'reseller']);

// Demo request routes
Route::post('/demo-request', [App\Http\Controllers\DemoRequestController::class, 'store'])->middleware('reseller');
Route::post('/demo-request/check', [App\Http\Controllers\DemoRequestController::class, 'checkExistingRequest'])->middleware('reseller');

// Admin demo request routes
Route::middleware(['auth:sanctum', 'admin', 'reseller'])->prefix('admin')->group(function () {
    Route::get('/demo-requests', [App\Http\Controllers\DemoRequestController::class, 'adminIndex']);
    Route::get('/demo-requests/stats', [App\Http\Controllers\DemoRequestController::class, 'adminStats']);
    Route::get('/demo-requests/{demoRequest}', [App\Http\Controllers\DemoRequestController::class, 'show']);
    Route::patch('/demo-requests/{demoRequest}/status', [App\Http\Controllers\DemoRequestController::class, 'updateStatus']);
    Route::delete('/demo-requests/{demoRequest}', [App\Http\Controllers\DemoRequestController::class, 'destroy']);
    
        // Reseller management routes
        Route::prefix('resellers')->group(function () {
            Route::get('/', [App\Http\Controllers\ResellerController::class, 'index']);
            Route::post('/', [App\Http\Controllers\ResellerController::class, 'store']);
            Route::get('/search', [App\Http\Controllers\ResellerController::class, 'search']);
            Route::get('/{reseller}', [App\Http\Controllers\ResellerController::class, 'show']);
            Route::put('/{reseller}', [App\Http\Controllers\ResellerController::class, 'update']);
            Route::put('/{reseller}/toggle-status', [App\Http\Controllers\ResellerController::class, 'toggleStatus']);
        });
    });

// Reseller Usage & Billing endpoints
Route::middleware(['auth:sanctum', 'reseller'])->prefix('reseller/usage')->group(function () {
    Route::get('/current', [App\Http\Controllers\ResellerUsageController::class, 'currentUsage']);
    Route::get('/history', [App\Http\Controllers\ResellerUsageController::class, 'usageHistory']);
    Route::get('/overages', [App\Http\Controllers\ResellerUsageController::class, 'overages']);
    Route::get('/alerts', [App\Http\Controllers\ResellerUsageController::class, 'alerts']);
});
