<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\ResellerMailManager;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $resellerId = config('reseller.id');
            
            Log::info('User registration attempt', [
                'email' => $request->email,
                'reseller_id' => $resellerId,
                'timestamp' => now()->toISOString()
            ]);
            
            // Use the new validation rules that check email uniqueness per reseller
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,NULL,id,reseller_id,' . $resellerId,
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'status' => 'active',
                'reseller_id' => $resellerId,
            ]);

            // Debug: Log user creation details
            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reseller_id' => $user->reseller_id,
                'reseller_id_from_config' => $resellerId,
                'timestamp' => now()->toISOString()
            ]);

            // Set reseller mail configuration before sending welcome email
            $reseller = app('currentReseller');
            if ($reseller) {
                try {
                    ResellerMailManager::setMailConfig($reseller);
                    Log::info('Reseller mail config set successfully', [
                        'reseller_id' => $reseller->id,
                        'reseller_domain' => $reseller->domain,
                        'timestamp' => now()->toISOString()
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to set reseller mail config, using default', [
                        'reseller_id' => $reseller->id,
                        'reseller_domain' => $reseller->domain,
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString()
                    ]);
                }
            }

            // Send welcome email with verification link
            try {
                $user->notify(new WelcomeEmail());
                Log::info('Welcome email sent successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'reseller_id' => $resellerId,
                    'timestamp' => now()->toISOString()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'reseller_id' => $resellerId,
                    'error' => $e,
                    'timestamp' => now()->toISOString()
                ]);
                // Don't fail registration if email sending fails
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registration successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reseller_id' => $resellerId,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Registration successful! Please check your email to verify your account.'
            ], 201);
        } catch (ValidationException $e) {
            Log::warning('User registration validation failed', [
                'email' => $request->email ?? 'unknown',
                'errors' => $e->errors(),
                'reseller_id' => config('reseller.id'),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'reseller_id' => config('reseller.id'),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $resellerId = config('reseller.id');
            Log::info('User login attempt', [
                'email' => $request->email,
                'reseller_id' => $resellerId,
                'timestamp' => now()->toISOString()
            ]);

            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            // Attempt authentication with reseller condition
            if (!Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
                'reseller_id' => $resellerId
            ])) {
                Log::warning('Login failed - invalid credentials', [
                    'email' => $request->email,
                    'reseller_id' => $resellerId,
                    'timestamp' => now()->toISOString()
                ]);
                
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = User::where('email', $request->email)->reseller()->firstOrFail();

            if (!$user->isActive()) {
                Log::warning('Login failed - account suspended', [
                    'user_id' => $user->id,
                    'email' => $request->email,
                    'reseller_id' => $resellerId,
                    'timestamp' => now()->toISOString()
                ]);
                
                throw ValidationException::withMessages([
                    'email' => ['Your account has been suspended.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reseller_id' => $resellerId,
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            Log::warning('User login validation failed', [
                'email' => $request->email ?? 'unknown',
                'errors' => $e->errors(),
                'reseller_id' => config('reseller.id'),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User login failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'reseller_id' => config('reseller.id'),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('User logout attempt', [
                'user_id' => $user->id ?? 'unknown',
                'email' => $user->email ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);
            
            $request->user()->currentAccessToken()->delete();

            Log::info('User logout successful', [
                'user_id' => $user->id ?? 'unknown',
                'email' => $user->email ?? 'unknown',
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Logout failed. Please try again.'
            ], 500);
        }
    }
}
