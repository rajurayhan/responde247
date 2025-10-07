<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Services\ResellerMailManager;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $resellerId = config('reseller.id');
        
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check if user exists within the current reseller
        $user = User::where('email', $request->email)
                   ->where('reseller_id', $resellerId)
                   ->first();

        if (!$user) {
            // Return success message even if user doesn't exist (security best practice)
            // But log the attempt for monitoring
            \Log::warning('Password reset attempt for non-existent user', [
                'email' => $request->email,
                'reseller_id' => $resellerId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'status' => 'If your email address exists in our system, you will receive a password reset link.'
            ]);
        }

        // Set reseller mail configuration before sending password reset email
        $reseller = app('currentReseller');
        if ($reseller) {
            ResellerMailManager::setMailConfig($reseller);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['status' => __($status)]);
    }
}
