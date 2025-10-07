@extends('emails.layouts.base')

@section('content')
    <h2>Welcome to {{ $branding['app_name'] }}, {{ $user->name }}! 🎉</h2>
    
    <p>A new reseller account has been created for <strong>{{ $reseller->org_name }}</strong> and you have been assigned as the administrator. You now have full access to manage your organization's AI voice assistant platform.</p>
    
    <!-- Login Credentials Card -->
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🔑 Your Login Credentials</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <div style="margin-bottom: 12px;">
                <strong>Email:</strong> <span style="color: #667eea;">{{ $user->email }}</span>
            </div>
            <div style="margin-bottom: 12px;">
                <strong>Temporary Password:</strong> 
                <code style="background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-family: monospace; color: #2d3748;">{{ $temporaryPassword }}</code>
            </div>
            <div>
                <strong>Organization:</strong> {{ $reseller->org_name }}
            </div>
        </div>
    </div>
    
    <!-- Security Notice -->
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⚠️ Important Security Notice</h3>
        <ul style="margin: 0; padding-left: 20px;">
            <li>This is a <strong>temporary password</strong> that should be changed immediately after your first login</li>
            <li>Please keep this password secure and do not share it with anyone</li>
            <li>For security reasons, you will be prompted to change your password on first login</li>
            <li>Use a strong, unique password for your account</li>
        </ul>
    </div>
    
    <!-- Login Button -->
    <div class="text-center mb-30">
        <a href="{{ $loginUrl }}" class="cta-button">
            Login to Your Admin Account
        </a>
    </div>
    
    <!-- Admin Capabilities -->
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🚀 What You Can Do With Your Admin Account</h3>
    
    <ul class="feature-list">
        <li>Create and manage AI voice assistants for your organization</li>
        <li>Configure voice settings, personalities, and responses</li>
        <li>View comprehensive call logs and analytics</li>
        <li>Manage user accounts and permissions</li>
        <li>Set up phone numbers and integrations</li>
        <li>Monitor usage and billing information</li>
        <li>Access advanced AI capabilities and features</li>
        <li>Get priority support and assistance</li>
    </ul>
    
    <!-- Quick Start Guide -->
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📋 Quick Start Guide</h3>
        <div style="margin: 15px 0;">
            <p><strong>Step 1:</strong> Click the login button above to access your admin dashboard</p>
            <p><strong>Step 2:</strong> Change your temporary password to something secure</p>
            <p><strong>Step 3:</strong> Complete your organization profile setup</p>
            <p><strong>Step 4:</strong> Create your first AI voice assistant</p>
            <p><strong>Step 5:</strong> Invite team members to your organization</p>
        </div>
    </div>
    
    <!-- Support Information -->
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💡 Need Help Getting Started?</h3>
        <p>We're here to help you succeed with your AI voice assistant platform:</p>
        <ul style="margin: 15px 0; padding-left: 20px;">
            <li>Check out our comprehensive documentation and tutorials</li>
            <li>Contact our dedicated support team for personalized assistance</li>
            <li>Join our community forums to connect with other administrators</li>
            <li>Schedule a demo call to see advanced features in action</li>
        </ul>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center mt-30">
        <a href="{{ $loginUrl }}" class="secondary-button">
            Access Admin Dashboard
        </a>
        <a href="{{ $branding['website_url'] }}/support" class="secondary-button">
            Get Support
        </a>
        <a href="{{ $branding['website_url'] }}/docs" class="secondary-button">
            View Documentation
        </a>
    </div>
    
    <!-- Important Note -->
    <div style="background: #f0f4f8; border: 1px solid #cbd5e0; border-radius: 8px; padding: 20px; margin: 30px 0;">
        <p style="margin: 0; font-size: 14px; color: #4a5568;">
            <strong>🔒 Security Reminder:</strong> This email contains sensitive login information. Please keep it secure and delete it after you've successfully logged in and changed your password.
        </p>
    </div>
    
    <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
        <strong>Note:</strong> If you did not expect to receive this email, please contact our support team immediately at <a href="mailto:{{ $branding['support_email'] }}" style="color: #667eea;">{{ $branding['support_email'] }}</a>.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ $branding['website_url'] }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ $branding['website_url'] }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection
