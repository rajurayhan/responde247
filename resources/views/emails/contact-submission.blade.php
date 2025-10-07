@extends('emails.layouts.base')

@section('content')
    <h2>New Contact Form Submission 📧</h2>
    
    <p>A new contact form has been submitted on the {{ $branding['app_name'] ?? 'AI Phone System' }} website. Here are the details:</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📋 Contact Information</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Name:</strong><br>
                <span style="color: #4a5568; font-weight: bold;">{{ $contact->full_name }}</span>
            </div>
            <div>
                <strong>Email:</strong><br>
                <span style="color: #4a5568;">{{ $contact->email }}</span>
            </div>
            <div>
                <strong>Phone:</strong><br>
                <span style="color: #4a5568;">{{ $contact->phone ?: 'Not provided' }}</span>
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
                <span style="color: #48bb78; font-weight: bold;">🆕 New</span>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💬 Message</h3>
        <div style="background: #f7fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
            <p style="color: #2d3748; line-height: 1.6; margin: 0; white-space: pre-wrap;">{{ $contact->message }}</p>
        </div>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Quick Actions</h3>
        <p>You can manage this contact submission through the admin panel:</p>
        <ul class="feature-list">
            <li>Mark as read or replied</li>
            <li>Add internal notes</li>
            <li>Update contact status</li>
            <li>Export contact data</li>
        </ul>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ config('app.url') }}/admin/contacts" class="cta-button">
            📋 View in Admin Panel
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📈 Contact Statistics</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">Total Contacts</h4>
                <p style="color: #4a5568; font-weight: bold; font-size: 18px;">{{ $totalContacts }}</p>
            </div>
            <div style="background: #fffaf0; padding: 15px; border-radius: 8px; border: 2px solid #ed8936;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">New Today</h4>
                <p style="color: #4a5568; font-weight: bold; font-size: 18px;">{{ $newToday }}</p>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/admin/contacts" class="secondary-button">
            📊 All Contacts
        </a>
        <a href="mailto:{{ $contact->email }}" class="secondary-button">
            📧 Reply Directly
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        <strong>Note:</strong> This is an automated notification. Please respond to the customer directly or update the contact status in the admin panel.
    </p>
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Response Time:</strong> We aim to respond to all inquiries within 24 hours during business hours.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection 