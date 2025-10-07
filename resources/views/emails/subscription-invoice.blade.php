@extends('emails.layouts.base')

@section('content')
    <h2>Invoice for {{ $package->name }} Subscription 📄</h2>
    
    <p>Thank you for subscribing to <strong>{{ $package->name }}</strong>! Your payment has been processed successfully and your subscription is now active.</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">✅ Payment Confirmed</h3>
        <p>Your subscription is now active and you can start creating voice assistants immediately.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📋 Invoice Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Invoice Number:</strong><br>
                <span style="color: #4a5568;">{{ $invoiceNumber }}</span>
            </div>
            <div>
                <strong>Invoice Date:</strong><br>
                <span style="color: #4a5568;">{{ $invoiceDate }}</span>
            </div>
            <div>
                <strong>Due Date:</strong><br>
                <span style="color: #4a5568;">{{ $dueDate }}</span>
            </div>
            <div>
                <strong>Payment Status:</strong><br>
                <span style="color: #48bb78; font-weight: bold;">✅ PAID</span>
            </div>
            <div>
                <strong>Payment Method:</strong><br>
                <span style="color: #4a5568;">💳 Credit Card</span>
            </div>
            <div>
                <strong>Amount:</strong><br>
                <span style="color: #2d3748; font-size: 18px; font-weight: bold;">${{ number_format($amount, 2) }}</span>
            </div>
        </div>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📦 Package Details</h3>
        <p><strong>{{ $package->name }}</strong></p>
        <p style="color: #4a5568;">{{ $package->description }}</p>
        <p><strong>Billing Period:</strong> {{ $periodStart }} to {{ $periodEnd }}</p>
    </div>
    
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🎁 What's Included in Your Plan</h3>
    
    <ul class="feature-list">
        <li>{{ $package->assistant_limit }} Voice Assistants</li>
        <li>Advanced AI capabilities and natural language processing</li>
        <li>24/7 Customer Support</li>
        <li>Regular updates and new features</li>
        <li>Secure and reliable platform</li>
        <li>Easy-to-use interface</li>
        <li>Custom AI personalities</li>
        <li>Integration with popular platforms</li>
    </ul>
    
    <div class="text-center mb-30">
        <a href="{{ config('app.url') }}/dashboard" class="cta-button">
            🚀 Access Your Dashboard
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💡 Getting Started</h3>
        <p><strong>Step 1:</strong> Access your dashboard</p>
        <p><strong>Step 2:</strong> Create your first voice assistant</p>
        <p><strong>Step 3:</strong> Customize AI personality and responses</p>
        <p><strong>Step 4:</strong> Test and deploy your assistant</p>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/support" class="secondary-button">
            📞 Get Support
        </a>
        <a href="{{ config('app.url') }}/docs" class="secondary-button">
            📚 Documentation
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        If you have any questions about your invoice or subscription, please don't hesitate to contact our support team. We're here to help you succeed with your voice AI projects!
    </p>
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Thank you for choosing sulus.ai!</strong> We're excited to see what amazing voice assistants you'll create.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection 