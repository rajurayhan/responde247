@extends('emails.layouts.base')

@section('content')
    <h2>Subscription Cancelled 😢</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, we've received your request to cancel your subscription with {{ $branding['company_name'] ?? 'AI Phone System' }}.</p>
    
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⚠️ Cancellation Confirmed</h3>
        <p>Your subscription has been cancelled as requested. Your service will remain active until <strong>{{ $effectiveDate }}</strong>.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Cancellation Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Cancelled Plan:</strong><br>
                <span style="color: #4a5568; font-weight: bold;">{{ $package->name }}</span>
            </div>
            <div>
                <strong>Monthly Rate:</strong><br>
                <span style="color: #4a5568;">${{ number_format($package->price, 2) }}</span>
            </div>
            <div>
                <strong>Cancellation Date:</strong><br>
                <span style="color: #4a5568;">{{ $cancellationDate->format('F j, Y \a\t g:i A') }}</span>
            </div>
            <div>
                <strong>Service Ends:</strong><br>
                <span style="color: #ed8936; font-weight: bold;">{{ $effectiveDate }}</span>
            </div>
            <div>
                <strong>Cancellation Reason:</strong><br>
                <span style="color: #4a5568;">
                    @if($cancellationReason === 'user_request')
                        User Requested
                    @elseif($cancellationReason === 'payment_failed')
                        Payment Failed
                    @elseif($cancellationReason === 'admin_action')
                        Admin Action
                    @else
                        {{ ucfirst(str_replace('_', ' ', $cancellationReason)) }}
                    @endif
                </span>
            </div>
            <div>
                <strong>Account Status:</strong><br>
                <span style="color: #ed8936; font-weight: bold;">⏳ Pending Deactivation</span>
            </div>
        </div>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⏰ What Happens Next</h3>
        <ul class="feature-list">
            <li><strong>Until {{ $effectiveDate }}:</strong> Your services remain fully active</li>
            <li><strong>After {{ $effectiveDate }}:</strong> Access to premium features will be limited</li>
            <li><strong>Data Retention:</strong> Your data will be kept for 30 days after cancellation</li>
            <li><strong>Reactivation:</strong> You can reactivate anytime before data deletion</li>
        </ul>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💾 Data & Export Options</h3>
        <p>Before your service ends, you can export your data to ensure you don't lose any important information.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📊 Analytics Data</h4>
                <p style="color: #4a5568; font-size: 14px;">Call logs, performance metrics, and usage statistics</p>
            </div>
            <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 2px solid #ed8936;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">🤖 Assistant Configs</h4>
                <p style="color: #4a5568; font-size: 14px;">Voice assistant settings, prompts, and configurations</p>
            </div>
        </div>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/dashboard/export" class="cta-button">
            📥 Export My Data
        </a>
    </div>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🔄 Want to Reactivate?</h3>
        <p>If you change your mind, you can reactivate your subscription anytime before your data is permanently deleted (30 days after service ends).</p>
        <div style="margin-top: 20px;">
            <a href="{{ $branding['website_url'] ?? config('app.url') }}/pricing" class="secondary-button" style="margin-right: 10px;">
                💰 View Plans
            </a>
            <a href="{{ $branding['website_url'] ?? config('app.url') }}/contact" class="secondary-button">
                💬 Contact Sales
            </a>
        </div>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📞 We're Here to Help</h3>
        <p>If you have any questions about your cancellation or need assistance with data export, our support team is available to help.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📧 Email Support</h4>
                <p style="color: #4a5568;">{{ $branding['support_email'] ?? 'support@example.com' }}</p>
            </div>
            <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 2px solid #ed8936;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📞 Phone Support</h4>
                <p style="color: #4a5568;">{{ $branding['company_phone'] ?? '(555) 123-4567' }}</p>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/feedback" class="secondary-button">
            📝 Share Feedback
        </a>
        <a href="{{ $branding['website_url'] ?? config('app.url') }}" class="secondary-button">
            🏠 Visit Website
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        Thank you for being a valued customer of {{ $branding['company_name'] ?? 'AI Phone System' }}. We're sorry to see you go and hope you'll consider us again in the future.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        This is an automated notification. For questions about your cancellation, please contact our support team at <strong>{{ $branding['support_email'] ?? 'support@example.com' }}</strong>
    </p>
@endsection