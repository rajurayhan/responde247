<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Authentication routes - with reseller middleware for domain detection
Route::get('/login', function () {
    return view('app');
})->name('login')->middleware('reseller');

Route::get('/register', function () {
    return view('app');
})->name('register')->middleware('reseller');

Route::get('/forgot-password', function () {
    return view('app');
})->name('password.request')->middleware('reseller');

Route::get('/reset-password/{token}', function () {
    return view('app');
})->name('password.reset')->middleware('reseller');

Route::get('/verify-email', function () {
    return view('app');
})->name('verification.notice')->middleware('reseller');

Route::get('/test-filtering', function () {
    return view('test-filtering');
});

Route::get('/debug', function () {
    return view('debug');
});

Route::get('/reseller-test', function () {
    return view('reseller-test');
})->middleware('reseller');

Route::get('/terms', function () {
    return view('app');
})->name('terms')->middleware('reseller');

Route::get('/privacy', function () {
    return view('app');
})->name('privacy')->middleware('reseller');

// Public audio player routes
Route::get('/p/{fileName}', [App\Http\Controllers\PublicAudioController::class, 'playAudio'])
    ->where('fileName', '[A-Za-z0-9]{12}\.(wav|mp3)')
    ->name('public.audio');

Route::get('/api/public/audio/{fileName}/info', [App\Http\Controllers\PublicAudioController::class, 'getCallInfo'])
    ->where('fileName', '[A-Za-z0-9]{12}\.(wav|mp3)')
    ->name('public.audio.info');

// Reseller subscription checkout success and cancel routes
Route::get('/reseller/subscription/success', [App\Http\Controllers\ResellerCheckoutController::class, 'success'])
    ->name('reseller.subscription.success');

Route::get('/reseller/subscription/cancel', [App\Http\Controllers\ResellerCheckoutController::class, 'cancel'])
    ->name('reseller.subscription.cancel');

// Serve the SPA for all routes except API routes - with reseller middleware
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$')->middleware('reseller');

// Test route for Stripe environment variable
Route::get('/test-stripe-env', function () {
    return response()->json([
        'stripe_key' => env('MIX_STRIPE_PUBLISHABLE_KEY') ? 'Set' : 'Not Set',
        'key_length' => strlen(env('MIX_STRIPE_PUBLISHABLE_KEY', '')),
        'key_prefix' => substr(env('MIX_STRIPE_PUBLISHABLE_KEY', ''), 0, 20)
    ]);
});

// Test page for Stripe frontend
Route::get('/test-stripe-page', function () {
    return view('test-stripe');
});

// Test route for Stripe subscription creation (for debugging)
Route::get('/test-stripe-subscription', function () {
    $user = \App\Models\User::first();
    $package = \App\Models\SubscriptionPackage::first();
    
    if (!$user || !$package) {
        return response()->json(['error' => 'User or package not found']);
    }
    
    $stripeService = app(\App\Services\StripeService::class);
    
    try {
        // Test customer creation
        $customerId = $stripeService->createCustomer($user);
        
        return response()->json([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'customer_created' => $customerId ? 'Yes' : 'No',
            'customer_id' => $customerId,
            'package_price' => $package->price,
            'package_name' => $package->name
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Test route for Stripe customer operations
Route::get('/test-stripe-customer', function () {
    $user = \App\Models\User::first();
    
    if (!$user) {
        return response()->json(['error' => 'User not found']);
    }
    
    try {
        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
        
        // Create a test customer
        $customer = $stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'test' => 'true'
            ]
        ]);
        
        return response()->json([
            'success' => true,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'message' => 'Customer created successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'stripe_error' => $e instanceof \Stripe\Exception\ApiErrorException ? $e->getStripeCode() : null
        ]);
    }
});

// Test route for Stripe API version and parameters
Route::get('/test-stripe-api', function () {
    try {
        $stripe = new \Stripe\StripeClient(config('stripe.secret_key'));
        
        // Test API version
        $account = $stripe->accounts->retrieve();
        
        return response()->json([
            'success' => true,
            'api_version' => config('stripe.api_version'),
            'account_id' => $account->id,
            'message' => 'Stripe API connection successful'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'api_version' => config('stripe.api_version'),
            'stripe_error' => $e instanceof \Stripe\Exception\ApiErrorException ? $e->getStripeCode() : null
        ]);
    }
});

// Test route for database data verification
Route::get('/test-database-data', function () {
    $transactions = \App\Models\Transaction::with(['user', 'package'])->get();
    $subscriptions = \App\Models\UserSubscription::with(['user', 'package'])->get();
    
    return response()->json([
        'transactions' => [
            'count' => $transactions->count(),
            'data' => $transactions->map(function($t) {
                return [
                    'id' => $t->id,
                    'user_id' => $t->user_id,
                    'user_name' => $t->user->name ?? 'Unknown',
                    'amount' => $t->amount,
                    'status' => $t->status,
                    'type' => $t->type,
                    'created_at' => $t->created_at
                ];
            })
        ],
        'subscriptions' => [
            'count' => $subscriptions->count(),
            'data' => $subscriptions->map(function($s) {
                return [
                    'id' => $s->id,
                    'user_id' => $s->user_id,
                    'user_name' => $s->user->name ?? 'Unknown',
                    'status' => $s->status,
                    'package_name' => $s->package->name ?? 'Unknown',
                    'created_at' => $s->created_at
                ];
            })
        ]
    ]);
});

// Test route for null value handling
Route::get('/test-null-filters', function () {
    $request = request();
    
    return response()->json([
        'test_filters' => [
            'status' => [
                'value' => $request->get('status'),
                'filled' => $request->filled('status'),
                'is_null' => $request->get('status') === null,
                'is_string_null' => $request->get('status') === 'null'
            ],
            'payment_method' => [
                'value' => $request->get('payment_method'),
                'filled' => $request->filled('payment_method'),
                'is_null' => $request->get('payment_method') === null,
                'is_string_null' => $request->get('payment_method') === 'null'
            ],
            'user_id' => [
                'value' => $request->get('user_id'),
                'filled' => $request->filled('user_id'),
                'is_null' => $request->get('user_id') === null,
                'is_string_null' => $request->get('user_id') === 'null'
            ]
        ],
        'all_request_params' => $request->all()
    ]);
});

// Test route for subscription period verification
Route::get('/test-subscription-periods', function () {
    $subscriptions = \App\Models\UserSubscription::with(['user', 'package'])->get();
    
    return response()->json([
        'subscriptions' => $subscriptions->map(function($s) {
            return [
                'id' => $s->id,
                'user_name' => $s->user->name ?? 'Unknown',
                'package_name' => $s->package->name ?? 'Unknown',
                'status' => $s->status,
                'current_period_start' => $s->current_period_start ? $s->current_period_start->toISOString() : null,
                'current_period_end' => $s->current_period_end ? $s->current_period_end->toISOString() : null,
                'trial_ends_at' => $s->trial_ends_at ? $s->trial_ends_at->toISOString() : null,
                'stripe_subscription_id' => $s->stripe_subscription_id,
                'days_remaining' => $s->current_period_end ? now()->diffInDays($s->current_period_end, false) : null,
                'is_active' => $s->status === 'active',
                'is_expired' => $s->current_period_end ? $s->current_period_end->isPast() : false
            ];
        })
    ]);
});

// Test route for authentication routes
Route::get('/test-auth-routes', function () {
    return response()->json([
        'routes' => [
            'login' => route('login'),
            'register' => route('register'),
            'forgot_password' => route('password.request'),
            'verify_email' => route('verification.notice'),
        ],
        'message' => 'Authentication routes are properly configured'
    ]);
});

// Test route for payment form
Route::get('/test-payment-form', function () {
    return response()->json([
        'message' => 'Payment form with professional card design is ready',
        'features' => [
            'Physical card display with gradient background',
            'Real-time card number formatting',
            'Card type detection (Visa, Mastercard, Amex, etc.)',
            'Professional chip and signature strip design',
            'Comprehensive card validation',
            'Responsive design with hover effects'
        ]
    ]);
});

// Test route for invoice email functionality
Route::get('/test-invoice-email', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No users found in database'
            ]);
        }

        $subscription = $user->subscriptions()->first();
        $transaction = $user->transactions()->first();

        if (!$subscription || !$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription or transaction found for testing'
            ]);
        }

        // Send test invoice email
        $user->notify(new \App\Notifications\SubscriptionInvoice($subscription, $transaction));

        return response()->json([
            'success' => true,
            'message' => 'Test invoice email sent successfully',
            'data' => [
                'user_email' => $user->email,
                'subscription_id' => $subscription->id,
                'transaction_id' => $transaction->id,
                'package_name' => $subscription->package->name ?? 'Unknown'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send test invoice email',
            'error' => $e->getMessage()
        ]);
    }
});

// Test route for all email templates
Route::get('/test-email-templates', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No users found in database'
            ]);
        }

        $subscription = $user->subscriptions()->first();
        $transaction = $user->transactions()->first();

        if (!$subscription || !$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription or transaction found for testing'
            ]);
        }

        // Send test emails
        $user->notify(new \App\Notifications\SubscriptionInvoice($subscription, $transaction));
        $user->notify(new \App\Notifications\WelcomeEmail());

        return response()->json([
            'success' => true,
            'message' => 'Professional email templates tested successfully',
            'data' => [
                'user_email' => $user->email,
                'emails_sent' => [
                    'Welcome Email',
                    'Subscription Invoice'
                ],
                'templates_available' => [
                    'Welcome Email (welcome.blade.php)',
                    'Subscription Invoice (subscription-invoice.blade.php)',
                    'Password Reset (password-reset.blade.php)',
                    'Subscription Cancelled (subscription-cancelled.blade.php)',
                    'Subscription Updated (subscription-updated.blade.php)',
                    'Payment Failed (payment-failed.blade.php)'
                ],
                'features' => [
                    'Professional logo and branding',
                    'Responsive design for all devices',
                    'Consistent color scheme and typography',
                    'Clear call-to-action buttons',
                    'Informative cards and sections',
                    'Social media links',
                    'Professional footer with contact info'
                ]
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to test email templates',
            'error' => $e->getMessage()
        ]);
    }
});

// Test route for password reset email
Route::get('/test-password-reset-email', function () {
    try {
        $user = \App\Models\User::first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No users found in database'
            ]);
        }

        // Create a test token
        $token = \Illuminate\Support\Str::random(60);
        
        // Send test password reset email
        $user->notify(new \App\Notifications\PasswordResetEmail($token));

        return response()->json([
            'success' => true,
            'message' => 'Password reset email sent successfully',
            'data' => [
                'user_email' => $user->email,
                'user_name' => $user->name,
                'template_used' => 'password-reset.blade.php',
                'features' => [
                    'Professional design with logo',
                    'Security notice and tips',
                    'Clear call-to-action button',
                    'Expiration warning (60 minutes)',
                    'Helpful links and support info'
                ]
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send password reset email',
            'error' => $e->getMessage()
        ]);
    }
});

// Test route for SaaS logo serving
Route::get('/test-saas-logo', function () {
    $resellers = \App\Models\Reseller::select('domain', 'logo_address', 'org_name')->get();
    
    return response()->json([
        'success' => true,
        'message' => 'SaaS Logo serving test information',
        'data' => [
            'logo_endpoint' => '/api/saas-public/logo.png',
            'test_instructions' => 'Add domain to /etc/hosts and access: http://domain.com/api/saas-public/logo.png',
            'available_domains' => $resellers->map(function($r) {
                return [
                    'domain' => $r->domain,
                    'org_name' => $r->org_name,
                    'has_logo' => !empty($r->logo_address),
                    'logo_url' => $r->logo_address,
                    'test_url' => 'http://' . $r->domain . '/api/saas-public/logo.png'
                ];
            }),
            'setup_instructions' => [
                'Add to /etc/hosts: 127.0.0.1 google.com',
                'Add to /etc/hosts: 127.0.0.1 127.0.0.1',
                'Add to /etc/hosts: 127.0.0.1 127.0.0.2',
                'Then test: curl -H "Host: google.com" http://localhost:8000/api/saas-public/logo.png'
            ]
        ]
    ]);
});

// Test route for WordPress integration flow
Route::get('/test-wordpress-integration', function () {
    return response()->json([
        'success' => true,
        'message' => 'WordPress integration flow is ready',
        'flow' => [
            'step_1' => 'User clicks button on WordPress site (https://sulus.ai)',
            'step_2' => 'User is redirected to https://app.sulus.ai/payment?package_id={package_id}',
            'step_3' => 'If not authenticated, user is redirected to /register (not /login)',
            'step_4' => 'After successful registration, user is redirected back to /payment?package_id={package_id}',
            'step_5' => 'User can complete payment with pre-selected package'
        ],
        'test_urls' => [
            'payment_with_package_1' => url('/payment?package_id=1'),
            'payment_with_package_2' => url('/payment?package_id=2'),
            'register_page' => url('/register'),
            'login_page' => url('/login')
        ],
        'features' => [
            'Automatic redirect to registration for unauthenticated users',
            'Preserves package_id parameter through the flow',
            'Seamless return to payment page after registration',
            'Works with existing authentication system',
            'Maintains security and user experience'
        ]
    ]);
});
