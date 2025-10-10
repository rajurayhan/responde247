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
        $resellerId = config('reseller.id');
        
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

        // Set reseller mail configuration before sending welcome email
        $reseller = app('currentReseller');
        if ($reseller) {
            ResellerMailManager::setMailConfig($reseller);
        }

        // Send welcome email with verification link
        $user->notify(new WelcomeEmail());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful! Please check your email to verify your account.'
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $resellerId = config('reseller.id');
        \Log::info('Reseller ID from login: ' . $resellerId);

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
            Log::error("Login failed for the resellerId $resellerId,  email $request->email");
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->reseller()->firstOrFail();
        \Log::info('User: ' . $user);

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
