@extends('emails.layouts.base')

@section('content')
    <h2>Payment Failed - Action Required ⚠️</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, we were unable to process your payment for your {{ $branding['company_name'] ?? 'AI Phone System' }} subscription.</p>
    
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⚠️ Payment Issue Detected</h3>
        <p>Your payment for <strong>{{ $package->name }}</strong> failed on {{ $failureDate }}. Please update your payment method to avoid service interruption.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💳 Payment Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Invoice Number:</strong><br>
                <span style="color: #4a5568; font-weight: bold;">{{ $invoiceNumber }}</span>
            </div>
            <div>
                <strong>Amount Due:</strong><br>
                <span style="color: #2d3748; font-size: 18px; font-weight: bold;">${{ number_format($amount, 2) }}</span>
            </div>
            <div>
                <strong>Subscription Plan:</strong><br>
                <span style="color: #4a5568;">{{ $package->name }}</span>
            </div>
            <div>
                <strong>Failure Date:</strong><br>
                <span style="color: #ed8936; font-weight: bold;">{{ $failureDate }}</span>
            </div>
            <div>
                <strong>Failure Reason:</strong><br>
                <span style="color: #ed8936;">
                    @if($failureReason === 'card_declined')
                        Card Declined
                    @elseif($failureReason === 'insufficient_funds')
                        Insufficient Funds
                    @elseif($failureReason === 'expired_card')
                        Expired Card
                    @elseif($failureReason === 'invalid_card')
                        Invalid Card
                    @else
                        {{ ucfirst(str_replace('_', ' ', $failureReason)) }}
                    @endif
                </span>
            </div>
            <div>
                <strong>Attempt #{{ $attemptCount }}</strong><br>
                <span style="color: #4a5568;">Next retry: {{ $nextRetryDate }}</span>
            </div>
        </div>
    </div>
    
    <div class="card danger-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🚨 Service Impact</h3>
        <ul class="feature-list">
            <li><strong>Grace Period:</strong> Your service will continue for 3 days</li>
            <li><strong>After Grace Period:</strong> Service will be suspended if payment isn't updated</li>
            <li><strong>Data Safety:</strong> Your data will be preserved during suspension</li>
            <li><strong>Reactivate:</strong> Service will resume immediately after successful payment</li>
        </ul>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/billing" class="cta-button">
            💳 Update Payment Method
        </a>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🔧 Common Solutions</h3>
        <ul class="feature-list">
            <li><strong>Check Card Details:</strong> Ensure card number, expiry date, and CVV are correct</li>
            <li><strong>Verify Funds:</strong> Make sure your account has sufficient balance</li>
            <li><strong>Contact Bank:</strong> Your bank may have blocked the transaction</li>
            <li><strong>Try Different Card:</strong> Use an alternative payment method</li>
            <li><strong>Update Expired Card:</strong> Replace any expired credit/debit cards</li>
        </ul>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📞 Need Immediate Help?</h3>
        <p>Our support team is available to help you resolve payment issues quickly and get your service back on track.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📧 Email Support</h4>
                <p style="color: #4a5568;">{{ $branding['support_email'] ?? 'support@example.com' }}</p>
                <p style="color: #6c757d; font-size: 12px;">Response within 2 hours</p>
            </div>
            <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 2px solid #ed8936;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📞 Phone Support</h4>
                <p style="color: #4a5568;">{{ $branding['company_phone'] ?? '(555) 123-4567' }}</p>
                <p style="color: #6c757d; font-size: 12px;">Mon-Fri 9AM-6PM EST</p>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/billing/history" class="secondary-button">
            📋 View Billing History
        </a>
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/support" class="secondary-button">
            📚 Help Center
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💡 Pro Tips</h3>
        <ul class="feature-list">
            <li><strong>Set Up Auto-Pay:</strong> Avoid future payment issues with automatic billing</li>
            <li><strong>Update Expiry Dates:</strong> Keep your card information current</li>
            <li><strong>Monitor Notifications:</strong> Stay informed about payment status</li>
            <li><strong>Backup Payment Method:</strong> Add a secondary card for reliability</li>
        </ul>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        We value your business and want to help you resolve this payment issue quickly. Please don't hesitate to contact our support team if you need assistance.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        This is an automated notification. For immediate assistance with payment issues, please contact our support team at <strong>{{ $branding['support_email'] ?? 'support@example.com' }}</strong>
    </p>
@endsection