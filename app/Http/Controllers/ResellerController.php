<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerPackage;
use App\Models\ResellerSetting;
use App\Models\ResellerSubscription;
use App\Models\User;
use App\Services\StripeService;
use App\Notifications\ResellerAdminWelcomeEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResellerController extends Controller
{
    /**
     * Display a listing of resellers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Reseller::query();

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('org_name', 'LIKE', "%{$search}%")
                      ->orWhere('domain', 'LIKE', "%{$search}%")
                      ->orWhere('company_email', 'LIKE', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $resellers = $query->orderBy('created_at', 'desc')->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $resellers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching resellers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching resellers'
            ], 500);
        }
    }

    /**
     * Store a newly created reseller.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'org_name' => 'required|string|max:255',
                'company_email' => 'nullable|email|max:255',
                'domain' => 'required|string|max:255',
                'status' => 'nullable|in:active,inactive',
                // Logo file upload
                'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048|dimensions:max_width=800,max_height=400',
                'logo_address' => 'nullable|string|max:255',
                // Required admin user fields
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:users,email',
                'admin_phone' => 'required|string|max:20',
            ], [
                'logo_file.image' => 'Logo must be an image file.',
                'logo_file.mimes' => 'Logo must be a JPEG, PNG, JPG, GIF, or WebP file.',
                'logo_file.max' => 'Logo file size must not exceed 2MB.',
                'logo_file.dimensions' => 'Logo dimensions must not exceed 800x400 pixels.',
            ]);

            // Set default status if not provided
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }

            // Handle logo file upload
            $logoUrl = $validated['logo_address'] ?? null;
            if ($request->hasFile('logo_file')) {
                $logoFile = $request->file('logo_file');
                $filename = 'logo_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
                $path = $logoFile->storeAs('resellers/logo', $filename, 'public');
                $logoUrl = Storage::disk('public')->url($path);
            }

            // Use database transaction to ensure data consistency
            $result = DB::transaction(function () use ($validated, $logoUrl) {
                // Create the reseller
                $reseller = Reseller::create([
                    'org_name' => $validated['org_name'],
                    'logo_address' => $logoUrl,
                    'company_email' => $validated['company_email'],
                    'domain' => $validated['domain'],
                    'status' => $validated['status'],
                ]);

                // Create default admin user for the reseller
                $adminUser = $this->createDefaultAdminUser($reseller, $validated);

                // Initialize default reseller settings
                $this->initializeResellerSettings($reseller, $logoUrl);

                return [
                    'reseller' => $reseller->load('adminUser'),
                    'admin_user' => $adminUser
                ];
            });

            Log::info('Reseller and admin user created successfully', [
                'reseller_id' => $result['reseller']->id,
                'org_name' => $result['reseller']->org_name,
                'admin_user_id' => $result['admin_user']->id,
                'admin_email' => $result['admin_user']->email,
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reseller and admin user created successfully',
                'data' => [
                    'reseller' => $result['reseller'],
                    'admin_user' => $result['admin_user'],
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
            Log::error('Error creating reseller', [
                'error' => $e->getMessage(),
                'input' => $request->all(),
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating reseller'
            ], 500);
        }
    }

    /**
     * Display the specified reseller.
     */
    public function show(Reseller $reseller): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $reseller
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reseller'
            ], 500);
        }
    }

    /**
     * Update the specified reseller.
     */
    public function update(Request $request, Reseller $reseller): JsonResponse
    {
        try {
            // Debug: Log incoming request data
            Log::info('Reseller Update Request', [
                'reseller_id' => $reseller->id,
                'request_data' => $request->all(),
                'has_logo_file' => $request->hasFile('logo_file'),
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            $validated = $request->validate([
                'org_name' => 'required|string|max:255',
                'company_email' => 'nullable|email|max:255',
                'domain' => 'required|string|max:255',
                'status' => 'nullable|in:active,inactive',
                // Logo file upload
                'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048|dimensions:max_width=800,max_height=400',
                'logo_address' => 'nullable|string|max:255',
            ], [
                'logo_file.image' => 'Logo must be an image file.',
                'logo_file.mimes' => 'Logo must be a JPEG, PNG, JPG, GIF, or WebP file.',
                'logo_file.max' => 'Logo file size must not exceed 2MB.',
                'logo_file.dimensions' => 'Logo dimensions must not exceed 800x400 pixels.',
            ]);

            // Handle logo file upload
            $logoUrl = $reseller->logo_address; // Keep existing logo if no new file uploaded
            if ($request->hasFile('logo_file')) {
                // Delete old logo if exists
                if ($reseller->logo_address) {
                    try {
                        $oldLogoPath = str_replace(Storage::disk('public')->url(''), '', $reseller->logo_address);
                        Storage::disk('public')->delete($oldLogoPath);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old logo', [
                            'reseller_id' => $reseller->id,
                            'logo_url' => $reseller->logo_address,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $logoFile = $request->file('logo_file');
                $filename = 'logo_' . time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();
                $path = $logoFile->storeAs('resellers/logo', $filename, 'public');
                $logoUrl = Storage::disk('public')->url($path);
            }

            // Update with logo URL if provided or keep existing
            $updateData = array_merge($validated, ['logo_address' => $logoUrl]);
            $reseller->update($updateData);

            Log::info('Reseller updated successfully', [
                'reseller_id' => $reseller->id,
                'org_name' => $reseller->org_name,
                'logo_updated' => $request->hasFile('logo_file'),
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reseller updated successfully',
                'data' => $reseller
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating reseller', [
                'error' => $e->getMessage(),
                'input' => $request->all(),
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating reseller'
            ], 500);
        }
    }


    /**
     * Remove the specified reseller from storage.
     */
    public function destroy(Reseller $reseller): JsonResponse
    {
        try {
            // Check if reseller has any users or subscriptions
            $userCount = $reseller->users()->count();
            
            $subscriptionCount = $reseller->subscriptions()->count();
            
            if ($userCount > 0 || $subscriptionCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete reseller with existing users or subscriptions. Please remove all users and subscriptions first.',
                    'data' => [
                        'userCount' => $userCount,
                        'subscriptionCount' => $subscriptionCount
                    ]
                ], 422);
            }

            // Log the deletion
            Log::info('Reseller deleted', [
                'reseller_id' => $reseller->id,
                'org_name' => $reseller->org_name,
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            // Delete the reseller
            $reseller->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reseller deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting reseller', [
                'error' => $e->getMessage(),
                'reseller_id' => $reseller->id,
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting reseller'
            ], 500);
        }
    }

    /**
     * Toggle the status of the specified reseller.
     */
    public function toggleStatus(Reseller $reseller): JsonResponse
    {
        try {
            $newStatus = $reseller->status === 'active' ? 'inactive' : 'active';
            $reseller->update(['status' => $newStatus]);

            Log::info('Reseller status toggled successfully', [
                'reseller_id' => $reseller->id,
                'org_name' => $reseller->org_name,
                'old_status' => $reseller->status === 'active' ? 'inactive' : 'active',
                'new_status' => $newStatus,
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => "Reseller status changed to {$newStatus}",
                'data' => $reseller->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling reseller status', [
                'error' => $e->getMessage(),
                'reseller_id' => $reseller->id,
                'user_id' => Auth::id() ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating reseller status'
            ], 500);
        }
    }

    /**
     * Search resellers by organization name, domain, or email.
     * Note: This method is now handled by the index method with search parameter.
     * Kept for backward compatibility.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchQuery = $request->get('q');
            
            if (empty($searchQuery)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ], 400);
            }

            $query = Reseller::query();

            // Apply search filter
            $query->where(function ($q) use ($searchQuery) {
                $q->where('org_name', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('domain', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('company_email', 'LIKE', "%{$searchQuery}%");
            });

            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            $resellers = $query->orderBy('org_name')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $resellers
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching resellers', [
                'error' => $e->getMessage(),
                'query' => $request->get('q')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error searching resellers'
            ], 500);
        }
    }

    /**
     * Create a default admin user for the reseller.
     */
    private function createDefaultAdminUser(Reseller $reseller, array $validated): User
    {
        // Admin user data are now required from the form
        $adminName = $validated['admin_name'];
        $adminEmail = $validated['admin_email'];
        $adminPhone = $validated['admin_phone'];
        
        // Generate a temporary password
        $temporaryPassword = Str::random(12);
        
        // Create the admin user
        $adminUser = User::create([
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => Hash::make($temporaryPassword),
            'role' => 'reseller_admin',
            'reseller_id' => $reseller->id,
            'phone' => $adminPhone,
            'company' => $reseller->org_name,
            'status' => 'active',
        ]);

        // Store temporary password for response (not in database)
        $adminUser->temporary_password = $temporaryPassword;

        // Send welcome email with login credentials
        try {
            $adminUser->notify(new ResellerAdminWelcomeEmail($reseller, $temporaryPassword));
            
            Log::info('Welcome email sent to admin user', [
                'reseller_id' => $reseller->id,
                'admin_user_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send welcome email to admin user', [
                'reseller_id' => $reseller->id,
                'admin_user_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'error' => $e->getMessage()
            ]);
            // Don't fail the entire operation if email fails
        }

        Log::info('Default admin user created for reseller', [
            'reseller_id' => $reseller->id,
            'admin_user_id' => $adminUser->id,
            'admin_email' => $adminUser->email,
            'created_by' => Auth::id() ?? 'system'
        ]);

        return $adminUser;
    }

    /**
     * Generate a default email for the reseller admin.
     */
    private function generateDefaultEmail(Reseller $reseller): string
    {
        // Try to use company email domain if available
        if ($reseller->company_email) {
            $domain = substr(strrchr($reseller->company_email, "@"), 1);
            $username = 'admin';
            return $username . '@' . $domain;
        }

        // Try to use reseller domain if available
        if ($reseller->domain) {
            return 'admin@' . $reseller->domain;
        }

        // Fallback: generate based on organization name
        $orgSlug = Str::slug($reseller->org_name);
        return 'admin@' . $orgSlug . '.example.com';
    }

    /**
     * Initialize default reseller settings when a new reseller is created
     */
    private function initializeResellerSettings(Reseller $reseller, ?string $logoUrl): void
    {
        try {
            $resellerId = $reseller->id;

            // Copy logo to reseller-specific directory if provided
            $resellerLogoUrl = null;
            if ($logoUrl) {
                $resellerLogoUrl = $this->copyLogoToResellerDirectory($logoUrl, $resellerId);
            }

            // Default settings using reseller data
            $defaultSettings = [
                [
                    'key' => 'site_title',
                    'value' => $reseller->org_name,
                    'label' => 'Site Title',
                    'type' => 'text',
                    'group' => 'general',
                    'description' => 'The main title of your website'
                ],
                [
                    'key' => 'site_tagline',
                    'value' => 'Professional Voice AI Solutions',
                    'label' => 'Site Tagline',
                    'type' => 'text',
                    'group' => 'general',
                    'description' => 'A short description or tagline for your site'
                ],
                [
                    'key' => 'meta_description',
                    'value' => 'Transform your business with cutting-edge voice AI technology powered by ' . $reseller->org_name,
                    'label' => 'Meta Description',
                    'type' => 'textarea',
                    'group' => 'seo',
                    'description' => 'Description for search engines (SEO)'
                ],
                [
                    'key' => 'support_email',
                    'value' => $reseller->company_email ?: '',
                    'label' => 'Support Email',
                    'type' => 'email',
                    'group' => 'contact',
                    'description' => 'Email address for customer support'
                ],
                [
                    'key' => 'company_phone',
                    'value' => '',
                    'label' => 'Company Phone',
                    'type' => 'text',
                    'group' => 'contact',
                    'description' => 'Primary contact phone number for your company'
                ],
                [
                    'key' => 'company_address',
                    'value' => '',
                    'label' => 'Company Address',
                    'type' => 'textarea',
                    'group' => 'contact',
                    'description' => 'Physical address of your company'
                ],
                [
                    'key' => 'primary_color',
                    'value' => '#3B82F6',
                    'label' => 'Primary Color',
                    'type' => 'color',
                    'group' => 'branding',
                    'description' => 'Primary brand color (hex code)'
                ],
                [
                    'key' => 'secondary_color',
                    'value' => '#1E40AF',
                    'label' => 'Secondary Color',
                    'type' => 'color',
                    'group' => 'branding',
                    'description' => 'Secondary brand color (hex code)'
                ],
            ];

            // Add logo URL if available
            if ($resellerLogoUrl) {
                $defaultSettings[] = [
                    'key' => 'logo_url',
                    'value' => $resellerLogoUrl,
                    'label' => 'Logo URL',
                    'type' => 'url',
                    'group' => 'branding',
                    'description' => 'Company logo URL'
                ];
            }

            // Create settings
            foreach ($defaultSettings as $setting) {
                ResellerSetting::setValue(
                    $resellerId,
                    $setting['key'],
                    $setting['value'],
                    $setting['label'],
                    $setting['type'],
                    $setting['group'],
                    $setting['description']
                );
            }

            Log::info('Reseller settings initialized successfully', [
                'reseller_id' => $resellerId,
                'settings_count' => count($defaultSettings),
                'logo_copied' => !is_null($resellerLogoUrl)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize reseller settings', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the entire reseller creation if settings initialization fails
        }
    }

    /**
     * Copy logo from general reseller directory to reseller-specific settings directory
     */
    private function copyLogoToResellerDirectory(string $logoUrl, string $resellerId): ?string
    {
        try {
            // Extract file path from URL
            $parsedUrl = parse_url($logoUrl);
            $originalPath = ltrim($parsedUrl['path'] ?? '', '/');
            
            // Remove 'storage/' prefix if present
            if (strpos($originalPath, 'storage/') === 0) {
                $originalPath = substr($originalPath, 8);
            }

            if (!Storage::disk('public')->exists($originalPath)) {
                Log::warning('Original logo file not found for copying', [
                    'original_path' => $originalPath,
                    'reseller_id' => $resellerId
                ]);
                return null;
            }

            // Generate new filename for reseller-specific directory
            $fileExtension = pathinfo($originalPath, PATHINFO_EXTENSION);
            $newFilename = 'logo_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $newPath = "resellers/{$resellerId}/logos/{$newFilename}";

            // Copy the file
            $fileContents = Storage::disk('public')->get($originalPath);
            Storage::disk('public')->put($newPath, $fileContents);

            // Generate new URL
            $newUrl = Storage::disk('public')->url($newPath);

            Log::info('Logo copied to reseller directory', [
                'reseller_id' => $resellerId,
                'original_path' => $originalPath,
                'new_path' => $newPath,
                'new_url' => $newUrl
            ]);

            return $newUrl;

        } catch (\Exception $e) {
            Log::error('Failed to copy logo to reseller directory', [
                'reseller_id' => $resellerId,
                'original_url' => $logoUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get current reseller's subscription information
     */
    public function getSubscription(): JsonResponse
    {
        try {
            $user = Auth::user();
            $reseller = $user->reseller;

            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }

            $subscription = $reseller->activeSubscription()->with('package')->first();

            return response()->json([
                'success' => true,
                'data' => $subscription
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching subscription information'
            ], 500);
        }
    }

    /**
     * Retry payment for pending subscription
     */
    public function retryPayment(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $reseller = $user->reseller;

            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }

            // Get pending subscription
            $pendingSubscription = $reseller->subscriptions()
                ->where('status', 'pending')
                ->with('package')
                ->first();

            if (!$pendingSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending subscription found'
                ], 404);
            }

            // Create new Stripe checkout session for retry
            $stripeService = new StripeService();
            
            // Prepare checkout data
            $checkoutData = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $pendingSubscription->package->name,
                            'description' => $pendingSubscription->package->description,
                        ],
                        'unit_amount' => $pendingSubscription->billing_interval === 'yearly' 
                            ? $pendingSubscription->package->yearly_price * 100 
                            : $pendingSubscription->package->price * 100,
                        'recurring' => [
                            'interval' => $pendingSubscription->billing_interval === 'yearly' ? 'year' : 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/reseller-billing?success=true',
                'cancel_url' => config('app.frontend_url') . '/reseller-billing?canceled=true',
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'package_id' => $pendingSubscription->package->id,
                    'billing_interval' => $pendingSubscription->billing_interval,
                    'is_reseller_subscription' => 'true',
                    'retry_payment' => 'true',
                    'original_subscription_id' => $pendingSubscription->id,
                ],
                'customer_email' => $reseller->company_email,
            ];
            
            $checkoutSession = $stripeService->createResellerCheckoutSession($checkoutData, $reseller->id);

            if (!$checkoutSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create checkout session'
                ], 500);
            }

            // Update pending subscription with new checkout session ID
            $pendingSubscription->update([
                'stripe_checkout_session_id' => $checkoutSession['id']
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutSession['url'],
                'message' => 'Checkout session created successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating retry payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating retry payment'
            ], 500);
        }
    }

    /**
     * Get reseller's transaction history
     */
    public function getTransactions(): JsonResponse
    {
        try {
            $user = Auth::user();
            $reseller = $user->reseller;

            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }

            $transactions = $reseller->transactions()
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller transactions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching transaction history'
            ], 500);
        }
    }

    /**
     * Get reseller's usage statistics
     */
    public function getUsage(): JsonResponse
    {
        try {
            $user = Auth::user();
            $reseller = $user->reseller;

            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }

            // Get current month usage
            $currentMonth = now()->startOfMonth();
            $nextMonth = now()->addMonth()->startOfMonth();

            $usage = [
                'active_assistants' => $reseller->assistants()->count(),
                'total_assistants' => $reseller->assistants()->count(),
                'minutes_used' => $reseller->callLogs()
                    ->whereBetween('start_time', [$currentMonth, $nextMonth])
                    ->sum('duration') / 60, // Convert seconds to minutes
                'calls_made' => $reseller->callLogs()
                    ->whereBetween('start_time', [$currentMonth, $nextMonth])
                    ->count(),
                'total_calls' => $reseller->callLogs()->count(),
                'total_minutes' => $reseller->callLogs()->sum('duration') / 60,
                'total_cost' => $reseller->callLogs()->sum('cost'),
            ];

            return response()->json([
                'success' => true,
                'data' => $usage
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching reseller usage: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching usage statistics'
            ], 500);
        }
    }

    /**
     * Subscribe to a reseller package
     */
    public function subscribe(Request $request)
    {
        try {
            $user = Auth::user();
            $reseller = $user->reseller;

            if (!$reseller) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reseller not found'
                ], 404);
            }

            // Check if reseller already has an active subscription
            if ($reseller->hasActiveSubscription()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription'
                ], 400);
            }

            $validated = $request->validate([
                'reseller_package_id' => 'required|exists:reseller_packages,id',
                'billing_interval' => 'required|in:monthly,yearly'
            ]);

            $package = ResellerPackage::findOrFail($validated['reseller_package_id']);

            // Create pending reseller subscription
            $now = now();
            $periodStart = $now;
            $periodEnd = $validated['billing_interval'] === 'yearly' 
                ? $now->copy()->addYear() 
                : $now->copy()->addMonth();

            $pendingSubscription = ResellerSubscription::create([
                'reseller_id' => $reseller->id,
                'reseller_package_id' => $package->id,
                'status' => 'pending',
                'billing_interval' => $validated['billing_interval'],
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'custom_amount' => $validated['billing_interval'] === 'yearly' 
                    ? $package->yearly_price 
                    : $package->price,
            ]);

            // Create Stripe checkout session
            $stripeService = new StripeService();
            
            // Prepare checkout data
            $checkoutData = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package->name,
                            'description' => $package->description,
                        ],
                        'unit_amount' => $validated['billing_interval'] === 'yearly' 
                            ? $package->yearly_price * 100 
                            : $package->price * 100,
                        'recurring' => [
                            'interval' => $validated['billing_interval'] === 'yearly' ? 'year' : 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/reseller-billing?success=true',
                'cancel_url' => config('app.frontend_url') . '/reseller-billing?canceled=true',
                'metadata' => [
                    'reseller_id' => $reseller->id,
                    'package_id' => $package->id,
                    'billing_interval' => $validated['billing_interval'],
                    'is_reseller_subscription' => 'true',
                ],
                'customer_email' => $reseller->company_email,
            ];
            
            $checkoutSession = $stripeService->createResellerCheckoutSession($checkoutData, $reseller->id);

            if (!$checkoutSession) {
                // Delete the pending subscription if checkout session creation fails
                $pendingSubscription->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create checkout session'
                ], 500);
            }

            // Update pending subscription with checkout session ID
            $pendingSubscription->update([
                'stripe_checkout_session_id' => $checkoutSession['id']
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutSession['url'],
                'message' => 'Checkout session created successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating reseller subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating subscription'
            ], 500);
        }
    }
}
