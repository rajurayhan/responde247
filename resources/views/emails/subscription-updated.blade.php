@extends('emails.layouts.base')

@section('content')
    <h2>Subscription Updated 📈</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, your subscription has been successfully updated!</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">✅ Update Confirmed</h3>
        <p>Your subscription has been updated. The changes are now active on your account.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Subscription Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Current Plan:</strong><br>
                <span style="color: #4a5568; font-weight: bold;">{{ $package->name }}</span>
            </div>
            <div>
                <strong>Monthly Rate:</strong><br>
                <span style="color: #2d3748; font-size: 18px; font-weight: bold;">${{ number_format($package->price, 2) }}</span>
            </div>
            <div>
                <strong>Invoice Number:</strong><br>
                <span style="color: #4a5568;">{{ $invoiceNumber }}</span>
            </div>
            <div>
                <strong>Invoice Date:</strong><br>
                <span style="color: #4a5568;">{{ $invoiceDate }}</span>
            </div>
            <div>
                <strong>Billing Period:</strong><br>
                <span style="color: #4a5568;">{{ $periodStart }} - {{ $periodEnd }}</span>
            </div>
            <div>
                <strong>Update Type:</strong><br>
                <span style="color: #48bb78; font-weight: bold;">
                    @if($updateType === 'plan_changed')
                        📈 Plan Changed
                    @elseif($updateType === 'billing_updated')
                        💳 Billing Updated
                    @elseif($updateType === 'renewed')
                        🔄 Subscription Renewed
                    @else
                        📝 Subscription Updated
                    @endif
                </span>
            </div>
        </div>
    </div>
    
    @if($updateType === 'plan_changed' && isset($updateData['old_package']))
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Plan Comparison</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background: #f7fafc; padding: 15px; border-radius: 8px; border: 2px solid #e2e8f0;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">Previous Plan</h4>
                <p style="color: #4a5568; font-weight: bold;">{{ $updateData['old_package']->name }}</p>
                <p style="color: #6c757d; font-size: 14px;">${{ number_format($updateData['old_package']->price, 2) }}/month</p>
            </div>
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">New Plan</h4>
                <p style="color: #4a5568; font-weight: bold;">{{ $package->name }}</p>
                <p style="color: #6c757d; font-size: 14px;">${{ number_format($package->price, 2) }}/month</p>
            </div>
        </div>
    </div>
    @endif
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🚀 What's Included</h3>
        <ul class="feature-list">
            <li><strong>{{ $package->voice_agents_limit == -1 ? 'Unlimited' : $package->voice_agents_limit }}</strong> Voice AI Assistants</li>
            <li><strong>{{ $package->minutes_limit == -1 ? 'Unlimited' : number_format($package->minutes_limit) }}</strong> Minutes per month</li>
            <li>24/7 Customer Support</li>
            <li>Advanced Analytics Dashboard</li>
            <li>API Access</li>
            <li>Priority Feature Updates</li>
        </ul>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/dashboard" class="cta-button">
            🚀 Access Dashboard
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📞 Need Help?</h3>
        <p>If you have any questions about your subscription update or need assistance with your account, our support team is here to help.</p>
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
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/billing" class="secondary-button">
            💳 Manage Billing
        </a>
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/support" class="secondary-button">
            📚 Support Center
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        Thank you for being a valued customer of {{ $branding['company_name'] ?? 'AI Phone System' }}. We appreciate your business and look forward to helping you succeed!
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        This is an automated notification. For billing questions, please contact our support team at <strong>{{ $branding['support_email'] ?? 'support@example.com' }}</strong>
    </p>
@endsection