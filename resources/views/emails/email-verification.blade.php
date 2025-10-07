@extends('emails.layouts.base')

@section('content')
    <h2>Verify Your Email Address 🔐</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, thank you for registering with {{ $branding['company_name'] ?? 'AI Phone System' }}. To complete your registration and start using our services, please verify your email address.</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">✅ Almost There!</h3>
        <p>We've sent this verification email to <strong>{{ $user->email }}</strong>. Click the button below to verify your email address and activate your account.</p>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $verificationUrl }}" class="cta-button">
            🔐 Verify Email Address
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📋 Account Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Name:</strong><br>
                <span style="color: #4a5568;">{{ $user->name }}</span>
            </div>
            <div>
                <strong>Email:</strong><br>
                <span style="color: #4a5568;">{{ $user->email }}</span>
            </div>
            <div>
                <strong>Registration Date:</strong><br>
                <span style="color: #4a5568;">{{ $user->created_at->format('M j, Y \a\t g:i A') }}</span>
            </div>
            <div>
                <strong>Status:</strong><br>
                <span style="color: #ed8936; font-weight: bold;">⏳ Pending Verification</span>
            </div>
        </div>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🚀 What Happens After Verification</h3>
        <ul class="feature-list">
            <li>Access to your personalized dashboard</li>
            <li>Create and manage AI voice assistants</li>
            <li>View call logs and analytics</li>
            <li>Manage your subscription and billing</li>
            <li>24/7 customer support access</li>
        </ul>
    </div>
    
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⚠️ Important Security Information</h3>
        <ul class="feature-list">
            <li>This verification link expires in 60 minutes</li>
            <li>Never share your verification link with anyone</li>
            <li>If you didn't create this account, please ignore this email</li>
            <li>For security, always log out when using shared devices</li>
        </ul>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}" class="secondary-button">
            🏠 Visit Website
        </a>
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/login" class="secondary-button">
            🔑 Login to Account
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">❓ Need Help?</h3>
        <p>If you're having trouble verifying your email address or if the button above doesn't work, you can copy and paste this link into your browser:</p>
        <div style="background: #f7fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; word-break: break-all; font-family: monospace; font-size: 12px; color: #4a5568;">
            {{ $verificationUrl }}
        </div>
        <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
            If you continue to have issues, please contact our support team at <strong>{{ $branding['support_email'] ?? 'support@example.com' }}</strong>
        </p>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        Welcome to {{ $branding['company_name'] ?? 'AI Phone System' }}! We're excited to have you on board and can't wait to see what you'll build with our platform.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By verifying your email, you agree to our <a href="{{ $branding['website_url'] ?? config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ $branding['website_url'] ?? config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection
