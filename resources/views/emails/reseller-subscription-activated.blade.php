@extends('emails.layouts.base')

@section('content')
    <h2>🎉 Your Reseller Subscription is Now Active, {{ $user->name }}!</h2>
    
    <p>Congratulations! Your reseller subscription for <strong>{{ $package->name }}</strong> has been successfully activated for <strong>{{ $reseller->org_name }}</strong>. You can now start offering voice AI solutions to your clients and managing your reseller account.</p>
    
    <!-- Subscription Details Card -->
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">✅ Subscription Activated</h3>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;">
            <div style="margin-bottom: 12px;">
                <strong>Organization:</strong> <span style="color: #667eea;">{{ $reseller->org_name }}</span>
            </div>
            <div style="margin-bottom: 12px;">
                <strong>Package:</strong> {{ $package->name }}
            </div>
            <div style="margin-bottom: 12px;">
                <strong>Billing Amount:</strong> 
                <span style="color: #2d3748; font-size: 18px; font-weight: bold;">${{ number_format($amount, 2) }}</span>
                <span style="color: #4a5568; font-size: 14px;">/{{ $billingText }}</span>
            </div>
            <div>
                <strong>Status:</strong> <span style="color: #48bb78; font-weight: bold;">✅ ACTIVE</span>
            </div>
        </div>
    </div>
    
    <!-- Package Features Card -->
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📦 Your Package Features</h3>
        <p><strong>{{ $package->name }}</strong> - {{ $package->description }}</p>
        
        <div style="margin-top: 15px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Voice Agents:</strong><br>
                    <span style="color: #4a5568;">{{ $package->formatted_voice_agents_limit ?? 'Unlimited' }}</span>
                </div>
                <div>
                    <strong>Monthly Minutes:</strong><br>
                    <span style="color: #4a5568;">{{ $package->formatted_minutes_limit ?? 'Unlimited' }}</span>
                </div>
                <div>
                    <strong>Support Level:</strong><br>
                    <span style="color: #4a5568;">{{ $package->support_level ?? 'Standard' }}</span>
                </div>
                <div>
                    <strong>Next Billing:</strong><br>
                    <span style="color: #4a5568;">{{ $nextBillingDate }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Login Button -->
    <div class="text-center mb-30">
        <a href="https://{{ $reseller->domain }}/login" class="cta-button">
            🚀 Sign In to Your Reseller Dashboard
        </a>
    </div>
    
    <!-- Reseller Capabilities -->
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🚀 What You Can Do With Your Reseller Account</h3>
    
    <ul class="feature-list">
        <li>Create and manage AI voice assistants for your clients</li>
        <li>Configure voice settings, personalities, and responses</li>
        <li>View comprehensive call logs and analytics</li>
        <li>Manage client accounts and subscriptions</li>
        <li>Set up phone numbers and integrations</li>
        <li>Monitor usage and billing information</li>
        <li>Access advanced AI capabilities and features</li>
        <li>Get priority reseller support and assistance</li>
    </ul>
    
    <!-- Quick Start Guide -->
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📋 Quick Start Guide</h3>
        <div style="margin: 15px 0;">
            <p><strong>Step 1:</strong> Click the login button above to access your reseller panel</p>
            <p><strong>Step 2:</strong> Complete your reseller profile and settings</p>
            <p><strong>Step 3:</strong> Create your first client account</p>
            <p><strong>Step 4:</strong> Set up voice assistants for your clients</p>
            <p><strong>Step 5:</strong> Start offering voice AI solutions to your clients</p>
        </div>
    </div>
    
    <!-- Support Information -->
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💡 Need Help Getting Started?</h3>
        <p>We're here to help you succeed with your reseller business:</p>
        <ul style="margin: 15px 0; padding-left: 20px;">
            <li>Check out our comprehensive reseller documentation and tutorials</li>
            <li>Contact our dedicated reseller support team for personalized assistance</li>
            <li>Join our reseller community forums to connect with other partners</li>
            <li>Schedule a demo call to see advanced reseller features in action</li>
        </ul>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center mt-30">
        <a href="https://{{ $reseller->domain }}/login" class="secondary-button">
            Access Reseller Dashboard
        </a>
        <a href="mailto:support@sulus.ai" class="secondary-button">
            Get Reseller Support
        </a>
        <a href="https://{{ $reseller->domain }}/reseller/docs" class="secondary-button">
            View Documentation
        </a>
    </div>
    
    <!-- Important Note -->
    <div style="background: #f0f4f8; border: 1px solid #cbd5e0; border-radius: 8px; padding: 20px; margin: 30px 0;">
        <p style="margin: 0; font-size: 14px; color: #4a5568;">
            <strong>🎯 Reseller Success:</strong> Your reseller account is now fully activated. Start by accessing your dashboard to begin creating voice AI solutions for your clients and growing your business.
        </p>
    </div>
    
    <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
        <strong>Note:</strong> If you have any questions about your reseller subscription, please contact our reseller support team at <a href="mailto:support@sulus.ai" style="color: #667eea;">support@sulus.ai</a>.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our reseller service, you agree to our <a href="{{ $branding['website_url'] }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ $branding['website_url'] }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection
