@extends('emails.layouts.base')

@section('content')
    <h2>Thank You for Contacting Us! 📧</h2>
    
    <p>Hello <strong>{{ $contact->first_name }}</strong>, thank you for reaching out to {{ $branding['company_name'] ?? 'AI Phone System' }}. We've received your message and will get back to you as soon as possible.</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">✅ Message Received</h3>
        <p>We've successfully received your inquiry and our team will review it within the next 24 hours during business hours.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📋 Your Message Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Reference:</strong><br>
                <span style="color: #4a5568; font-weight: bold;">#{{ $contact->id }}</span>
            </div>
            <div>
                <strong>Subject:</strong><br>
                <span style="color: #4a5568;">{{ ucfirst($contact->subject) }}</span>
            </div>
            <div>
                <strong>Submitted:</strong><br>
                <span style="color: #4a5568;">{{ $contact->created_at->format('M j, Y \a\t g:i A') }}</span>
            </div>
            <div>
                <strong>Status:</strong><br>
                <span style="color: #48bb78; font-weight: bold;">📥 Received</span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💬 Your Message</h3>
        <div style="background: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
            <p style="color: #2d3748; line-height: 1.6; margin: 0; white-space: pre-wrap;">{{ $contact->message }}</p>
        </div>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⏰ What Happens Next</h3>
        <ul class="feature-list">
            <li>Our team will review your inquiry within 24 hours</li>
            <li>You'll receive a personalized response via email</li>
            <li>For urgent matters, we may also call you</li>
            <li>We'll provide detailed information and next steps</li>
        </ul>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/support" class="cta-button">
            📚 Visit Support Center
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📞 Other Ways to Reach Us</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📧 Email</h4>
                <p style="color: #4a5568;">{{ $branding['support_email'] ?? 'support@example.com' }}</p>
            </div>
            <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 2px solid #ed8936;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">📞 Phone</h4>
                <p style="color: #4a5568;">{{ $branding['company_phone'] ?? '(555) 123-4567' }}</p>
            </div>
        </div>
        <p style="color: #6c757d; font-size: 14px; margin-top: 15px;">
            <strong>Business Hours:</strong> Monday - Friday: 9:00 AM - 6:00 PM EST
        </p>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ $branding['website_url'] ?? config('app.url') }}" class="secondary-button">
            🏠 Visit Website
        </a>
        <a href="{{ $branding['website_url'] ?? config('app.url') }}/pricing" class="secondary-button">
            💰 View Pricing
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        Thank you for choosing {{ $branding['company_name'] ?? 'AI Phone System' }}! We're excited to help you with your voice AI needs.
    </p>
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Reference Number:</strong> #{{ $contact->id }} - Please keep this for your records.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ $branding['website_url'] ?? config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ $branding['website_url'] ?? config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection 