<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\ResellerPackage;
use App\Models\ResellerSubscription;
use App\Models\User;
use App\Notifications\ResellerAdminWelcomeEmail;
use App\Services\ResellerMailManager;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResellerRegistrationController extends Controller
{
    /**
     * Handle reseller registration request
     */
    public function register(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                // Company Information
                'org_name' => 'required|string|max:255',
                'company_email' => 'nullable|email|max:255',
                
                // Admin User Details
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_phone' => 'required|string|max:20',
                // Password removed - temporary password will be generated and sent via email
                
                // Package Selection
                'reseller_package_id' => 'required|exists:reseller_packages,id',
                'billing_interval' => 'required|in:monthly,yearly',
                
                // Logo file upload
                'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048|dimensions:max_width=800,max_height=400',
                
                // Subdomain (always required)
                'subdomain_name' => 'required|string|max:63|regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/',
                
                // Terms
                'terms' => 'required|accepted',
            ], [
                'logo_file.image' => 'Logo must be an image file.',
                'logo_file.mimes' => 'Logo must be a JPEG, PNG, JPG, GIF, or WebP file.',
                'logo_file.max' => 'Logo file size must not exceed 2MB.',
                'logo_file.dimensions' => 'Logo dimensions must not exceed 800x400 pixels.',
                'subdomain_name.required' => 'Subdomain name is required.',
                'subdomain_name.regex' => 'Subdomain name can only contain letters, numbers, and hyphens.',
                'subdomain_name.max' => 'Subdomain name cannot exceed 63 characters.',
                'terms.accepted' => 'You must agree to the terms and conditions.',
            ]);

            // Get the selected package
            $package = ResellerPackage::findOrFail($validated['reseller_package_id']);
            
            if (!$package->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected package is not available'
                ], 422);
            }

            // Handle logo file upload
            $logoUrl = null;
            if ($request->hasFile('logo_file')) {
                $logoFile = $request->file('logo_file');
                $filename = 'logo_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
                $path = $logoFile->storeAs('resellers/logo', $filename, 'public');
                $logoUrl = asset('storage/' . $path);
            }

            // Use database transaction to ensure data consistency
            $result = DB::transaction(function () use ($validated, $logoUrl, $package) {
                // Always append .responde247.com to subdomain
                $finalDomain = $validated['subdomain_name'] . '.responde247.com';

                // Create the reseller
                $reseller = Reseller::create([
                    'org_name' => $validated['org_name'],
                    'logo_address' => $logoUrl,
                    'company_email' => $validated['company_email'],
                    'domain' => $finalDomain,
                    'status' => 'active', // Set as active by default for self-registration
                ]);

                // Create admin user for the reseller (without sending email yet)
                $adminUser = $this->createAdminUserWithoutEmail($reseller, $validated);

                // Create pending reseller subscription
                $subscription = $this->createResellerSubscription($reseller, $package, $validated['billing_interval']);

                // Initialize default reseller settings
                $this->initializeResellerSettings($reseller, $logoUrl);

                return [
                    'reseller' => $reseller->load('adminUser'),
                    'admin_user' => $adminUser,
                    'subscription' => $subscription
                ];
            });

            // Create checkout session for payment
            $checkoutSession = $this->createCheckoutSession($result['reseller'], $result['subscription'], $package, $validated['billing_interval']);

            if (!$checkoutSession) {
                // If checkout session creation fails, clean up and return error
                $result['subscription']->delete();
                $result['admin_user']->delete();
                $result['reseller']->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment session. Please try again.'
                ], 500);
            }

            // Update subscription with checkout session details
            $result['subscription']->update([
                'stripe_checkout_session_id' => $checkoutSession['id'],
                'checkout_session_url' => $checkoutSession['url'],
                'stripe_customer_id' => $checkoutSession['customer'] ?? null,
                'metadata' => array_merge($result['subscription']->metadata ?? [], [
                    'is_reseller_subscription' => 'true',
                    'created_by_self_registration' => 'true',
                    'stripe_checkout_session_id' => $checkoutSession['id'],
                ])
            ]);

            // Send email notifications after successful database transaction and checkout session creation
            $this->sendWelcomeEmail($result['admin_user'], $result['reseller']);

            Log::info('Reseller registration completed successfully', [
                'reseller_id' => $result['reseller']->id,
                'org_name' => $result['reseller']->org_name,
                'admin_user_id' => $result['admin_user']->id,
                'admin_email' => $result['admin_user']->email,
                'package_id' => $package->id,
                'package_name' => $package->name,
                'billing_interval' => $validated['billing_interval']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reseller account created successfully! Please complete payment to activate your subscription.',
                'checkout_url' => $checkoutSession['url'],
                'data' => [
                    'reseller' => $result['reseller'],
                    'admin_user' => $result['admin_user'],
                    'subscription' => $result['subscription'],
                    'temporary_password' => $result['admin_user']->temporary_password ?? null
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Reseller registration failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ], 500);
        }
    }

    /**
     * Create admin user for the reseller without sending email
     */
    private function createAdminUserWithoutEmail(Reseller $reseller, array $validated): User
    {
        // Generate a temporary password
        $temporaryPassword = Str::random(12);
        
        // Create the admin user
        $adminUser = User::create([
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($temporaryPassword),
            'role' => 'reseller_admin',
            'reseller_id' => $reseller->id,
            'phone' => $validated['admin_phone'],
            'company' => $reseller->org_name,
            'status' => 'active',
        ]);

        // Store temporary password for response (not in database)
        $adminUser->temporary_password = $temporaryPassword;

        Log::info('Admin user created for reseller', [
            'reseller_id' => $reseller->id,
            'admin_user_id' => $adminUser->id,
            'admin_email' => $adminUser->email,
            'created_by' => 'self_registration'
        ]);

        return $adminUser;
    }

    /**
     * Send welcome email to reseller admin
     */
    private function sendWelcomeEmail(User $adminUser, Reseller $reseller): void
    {
        try {
            $adminUser->notify(new ResellerAdminWelcomeEmail($reseller, $adminUser->temporary_password));
            
            Log::info('Welcome email sent to reseller admin', [
                'reseller_id' => $reseller->id,
                'admin_user_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send welcome email to reseller admin', [
                'reseller_id' => $reseller->id,
                'admin_user_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'error' => $e->getMessage()
            ]);
            // Don't fail the entire operation if email fails
        }
    }

    /**
     * Create reseller subscription
     */
    private function createResellerSubscription(Reseller $reseller, ResellerPackage $package, string $billingInterval): ResellerSubscription
    {
        $now = now();
        $periodStart = $now->copy();
        $periodEnd = $billingInterval === 'yearly' 
            ? $now->copy()->addYear() 
            : $now->copy()->addMonth();

        $subscription = ResellerSubscription::create([
            'reseller_id' => $reseller->id,
            'reseller_package_id' => $package->id,
            'status' => 'pending',
            'billing_interval' => $billingInterval,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'trial_ends_at' => null, // No trial for self-registration
            'stripe_subscription_id' => null,
            'stripe_customer_id' => null,
            'stripe_price_id' => null,
            'stripe_checkout_session_id' => null,
            'stripe_payment_link' => null,
            'current_period_usage_cost' => 0,
            'current_period_calls' => 0,
            'current_period_duration' => 0,
            'pending_overage_cost' => 0,
            'last_usage_calculated_at' => $now,
        ]);

        Log::info('Reseller subscription created', [
            'reseller_id' => $reseller->id,
            'subscription_id' => $subscription->id,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'billing_interval' => $billingInterval,
            'period_start' => $periodStart,
            'period_end' => $periodEnd
        ]);

        return $subscription;
    }

    /**
     * Initialize default reseller settings
     */
    private function initializeResellerSettings(Reseller $reseller, ?string $logoUrl): void
    {
        $defaultSettings = [
            // Branding settings
            'app_name' => $reseller->org_name,
            'logo_url' => $logoUrl,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            
            // Mail settings
            'mail_from_name' => $reseller->org_name,
            'mail_from_address' => $reseller->company_email ?? 'noreply@' . $reseller->domain,
            
            // Feature settings
            'enable_demo_requests' => true,
            'enable_contact_form' => true,
            'enable_pricing_page' => true,
            
            // System settings
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
        ];

        foreach ($defaultSettings as $key => $value) {
            if ($value !== null) {
                \App\Models\ResellerSetting::setValue(
                    $reseller->id,
                    $key,
                    $value,
                    ucfirst(str_replace('_', ' ', $key)),
                    'text',
                    'general'
                );
            }
        }

        Log::info('Default reseller settings initialized', [
            'reseller_id' => $reseller->id,
            'settings_count' => count($defaultSettings)
        ]);
    }

    /**
     * Create Stripe checkout session for reseller subscription
     */
    private function createCheckoutSession(Reseller $reseller, ResellerSubscription $subscription, ResellerPackage $package, string $billingInterval): ?array
    {
        try {
            $stripeService = new StripeService();
            
            // Calculate price based on billing interval
            $price = $billingInterval === 'yearly' ? $package->yearly_price : $package->price;
            
            // Create checkout session data
            $checkoutData = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package->name . ' - Reseller Package',
                            'description' => $package->description,
                        ],
                        'unit_amount' => $price * 100, // Convert to cents
                        'recurring' => [
                            'interval' => $billingInterval === 'yearly' ? 'year' : 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => url('/reseller-registration/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/register-reseller?cancelled=true'),
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'subscription_id' => $subscription->id,
                    'package_id' => $package->id,
                    'billing_interval' => $billingInterval,
                    'admin_email' => $reseller->adminUser->email ?? '',
                    'is_reseller_subscription' => 'true',
                    'created_by_self_registration' => 'true',
                ],
                'customer_email' => $reseller->adminUser->email ?? $reseller->company_email,
                'subscription_data' => [
                    'metadata' => [
                        'reseller_id' => $reseller->id,
                        'package_id' => $package->id,
                        'billing_interval' => $billingInterval,
                        'is_reseller_subscription' => 'true',
                        'created_by_self_registration' => 'true',
                    ],
                ],
            ];

            return $stripeService->createResellerCheckoutSession($checkoutData, $reseller->id);
            
        } catch (\Exception $e) {
            Log::error('Error creating checkout session for reseller registration', [
                'reseller_id' => $reseller->id,
                'package_id' => $package->id,
                'billing_interval' => $billingInterval,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get available reseller packages for registration
     */
    public function getPackages()
    {
        try {
            $packages = ResellerPackage::active()
                ->orderBy('price', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $packages
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller packages: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching packages'
            ], 500);
        }
    }

    /**
     * Check if subdomain is available for reseller registration
     */
    public function checkDomainAvailability(Request $request)
    {
        try {
            $request->validate([
                'subdomain' => 'required|string|max:63|regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/'
            ], [
                'subdomain.required' => 'Subdomain is required',
                'subdomain.regex' => 'Subdomain can only contain letters, numbers, and hyphens',
                'subdomain.max' => 'Subdomain cannot exceed 63 characters'
            ]);

            $subdomain = $request->input('subdomain');
            
            // Always append .responde247.com to check the full domain
            $fullDomain = $subdomain . '.responde247.com';
            
            // Check if domain is already taken
            $existingReseller = Reseller::where('domain', $fullDomain)->first();
            
            if ($existingReseller) {
                return response()->json([
                    'success' => true,
                    'available' => false,
                    'message' => 'This subdomain is already taken by another reseller'
                ]);
            }

            return response()->json([
                'success' => true,
                'available' => true,
                'message' => 'Subdomain is available'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subdomain format',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error checking subdomain availability', [
                'subdomain' => $request->input('subdomain'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check subdomain availability'
            ], 500);
        }
    }

    /**
     * Check if email is available for user registration
     */
    public function checkEmailAvailability(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|max:255'
            ]);

            $email = $request->input('email');
            
            // Check if email is already taken by any user
            $existingUser = User::where('email', $email)->first();
            
            if ($existingUser) {
                return response()->json([
                    'success' => true,
                    'available' => false,
                    'message' => 'This email is already registered by another user'
                ]);
            }

            return response()->json([
                'success' => true,
                'available' => true,
                'message' => 'Email is available'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email format',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error checking email availability', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check email availability'
            ], 500);
        }
    }

    /**
     * Get reseller domain by session ID for redirect after successful registration
     */
    public function getResellerDomainBySession(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|string|max:255'
            ]);

            $sessionId = $request->input('session_id');
            
            // Find the reseller subscription by checkout session ID
            $subscription = \App\Models\ResellerSubscription::where('stripe_checkout_session_id', $sessionId)->first();
            
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No reseller subscription found for this session'
                ], 404);
            }

            $reseller = $subscription->reseller;
            
            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found for this subscription'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'domain' => $reseller->domain,
                'org_name' => $reseller->org_name,
                'message' => 'Reseller domain retrieved successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error getting reseller domain by session', [
                'session_id' => $request->input('session_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reseller domain'
            ], 500);
        }
    }
}
