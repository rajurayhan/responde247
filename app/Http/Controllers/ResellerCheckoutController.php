<?php

namespace App\Http\Controllers;

use App\Models\ResellerSubscription;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ResellerCheckoutController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle successful checkout session completion
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        Log::info('Reseller checkout success page accessed', [
            'session_id' => $sessionId,
            'request_params' => $request->all()
        ]);

        if (!$sessionId) {
            Log::warning('No session_id provided in success URL');
            return $this->showErrorPage('No session ID provided');
        }

        try {
            // Retrieve the checkout session from Stripe
            $stripe = new StripeClient(config('stripe.secret_key'));
            $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId);

            Log::info('Checkout session retrieved', [
                'session_id' => $sessionId,
                'payment_status' => $checkoutSession->payment_status,
                'subscription_id' => $checkoutSession->subscription ?? null,
                'customer_id' => $checkoutSession->customer ?? null
            ]);

            // Find the reseller subscription
            $subscription = ResellerSubscription::where('stripe_checkout_session_id', $sessionId)->first();

            if (!$subscription) {
                Log::warning('No reseller subscription found for checkout session', [
                    'session_id' => $sessionId
                ]);
                return $this->showErrorPage('Subscription not found');
            }

            // Check if payment was successful
            if ($checkoutSession->payment_status === 'paid') {
                Log::info('Payment successful, showing success page', [
                    'subscription_id' => $subscription->id,
                    'reseller_id' => $subscription->reseller_id
                ]);

                return $this->showSuccessPage($subscription, $checkoutSession);
            } else {
                Log::warning('Payment not successful', [
                    'session_id' => $sessionId,
                    'payment_status' => $checkoutSession->payment_status
                ]);
                return $this->showErrorPage('Payment was not successful');
            }

        } catch (\Exception $e) {
            Log::error('Error processing checkout success', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return $this->showErrorPage('An error occurred while processing your payment');
        }
    }

    /**
     * Handle cancelled checkout session
     */
    public function cancel(Request $request)
    {
        Log::info('Reseller checkout cancel page accessed', [
            'request_params' => $request->all()
        ]);

        return $this->showCancelPage();
    }

    /**
     * Show success page
     */
    private function showSuccessPage(ResellerSubscription $subscription, $checkoutSession)
    {
        $reseller = $subscription->reseller;
        $package = $subscription->package;

        return view('reseller-checkout-success', [
            'subscription' => $subscription,
            'reseller' => $reseller,
            'package' => $package,
            'checkoutSession' => $checkoutSession,
            'success' => true
        ]);
    }

    /**
     * Show cancel page
     */
    private function showCancelPage()
    {
        return view('reseller-checkout-cancel', [
            'success' => false
        ]);
    }

    /**
     * Show error page
     */
    private function showErrorPage($message)
    {
        return view('reseller-checkout-error', [
            'error_message' => $message,
            'success' => false
        ]);
    }
}