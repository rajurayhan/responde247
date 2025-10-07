<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, string $hash): RedirectResponse
    {
        $timestamp = $request->query('t');
        
        // Check if timestamp is provided and not expired (60 minutes)
        if (!$timestamp || (time() - $timestamp) > 3600) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=0&error=expired'
            );
        }

        // Find user by email hash with timestamp
        $user = User::whereRaw('SHA1(CONCAT(email, ?)) = ?', [$timestamp, $hash])->first();
        
        if (!$user) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=0&error=invalid'
            );
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url').'/dashboard?verified=1'
            );
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(
            config('app.frontend_url').'/dashboard?verified=1'
        );
    }
}
