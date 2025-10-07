# Email Template Reseller Branding Migration Plan

## 🎯 **Overview**

This document outlines the complete plan for migrating all email templates from hardcoded "sulus.ai" branding to dynamic reseller-specific branding. This ensures that each domain's users receive emails with their reseller's branding, logos, and contact information.

## 📧 **Current Email System Analysis**

### **Notification Classes** (4 files)
1. **`PasswordResetEmail.php`** - Password reset notifications
2. **`WelcomeEmail.php`** - User registration welcome emails  
3. **`ResellerAdminWelcomeEmail.php`** - Reseller admin account creation
4. **`SubscriptionInvoice.php`** - Billing and subscription invoices

### **Email Templates** (8 files)
1. **`password-reset.blade.php`** - Password reset template
2. **`welcome.blade.php`** - User welcome template
3. **`contact-confirmation.blade.php`** - Contact form confirmation
4. **`contact-submission.blade.php`** - Contact form submission notification
5. **`subscription-invoice.blade.php`** - Invoice template
6. **`subscription-updated.blade.php`** - Subscription change notifications
7. **`subscription-cancelled.blade.php`** - Cancellation notifications
8. **`payment-failed.blade.php`** - Payment failure notifications

### **Base Layout**
- **`emails/layouts/base.blade.php`** - Master email layout template

## 🔍 **Current Hardcoded Branding Issues**

### **In Notification Classes:**
```php
// ❌ HARDCODED: PasswordResetEmail.php
->subject('Reset Your Password - sulus.ai 🔐')

// ❌ HARDCODED: WelcomeEmail.php  
->subject('Welcome to sulus.ai! 🎉')
'headerTitle' => 'Welcome to sulus.ai!',

// ❌ HARDCODED: ResellerAdminWelcomeEmail.php
->line('Welcome to ' . config('app.name') . '!')
->line('Thank you for choosing ' . config('app.name') . '!')
```

### **In Email Templates:**
```php
// ❌ HARDCODED: password-reset.blade.php
<p>Hello <strong>{{ $user->name }}</strong>, we received a request to reset your password for your sulus.ai account.</p>
<p>Once you've reset your password, you'll be able to access all your sulus.ai features including:</p>

// ❌ HARDCODED: welcome.blade.php
<h2>Welcome to sulus.ai, {{ $user->name }}! 🎉</h2>
<h3>🚀 What You Can Do With sulus.ai</h3>
<strong>Note:</strong> If you did not create an account with sulus.ai, please ignore this email.

// ❌ HARDCODED: contact-submission.blade.php
<p>A new contact form has been submitted on the sulus.ai website. Here are the details:</p>
```

### **In Base Layout:**
```php
// ❌ HARDCODED: emails/layouts/base.blade.php
<title>{{ $subject ?? 'sulus.ai' }}</title>
<div class="logo">sulus.ai</div>
<p><strong>sulus.ai Team</strong></p>
<p>&copy; {{ date('Y') }} {{ \App\Models\SystemSetting::getValue('company_name', 'sulus.ai') }}. All rights reserved.</p>
```

## 🚀 **Migration Strategy**

### **Phase 1: Create Reseller-Aware Email Service**

#### **1.1 Create ResellerEmailService**
Create `app/Services/ResellerEmailService.php`:

```php
<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use App\Models\User;

class ResellerEmailService
{
    /**
     * Get reseller branding data for emails
     */
    public static function getResellerBranding(?User $user = null, ?Reseller $reseller = null): array
    {
        // Get reseller from user relationship or provided reseller
        if (!$reseller && $user) {
            $reseller = $user->reseller;
        }
        
        // Fallback to default if no reseller found
        if (!$reseller) {
            return self::getDefaultBranding();
        }
        
        return [
            'app_name' => ResellerSetting::getValue($reseller->id, 'app_name', $reseller->org_name),
            'company_name' => $reseller->org_name,
            'company_email' => $reseller->company_email,
            'support_email' => ResellerSetting::getValue($reseller->id, 'support_email', $reseller->company_email),
            'logo_url' => self::getLogoUrl($reseller),
            'primary_color' => ResellerSetting::getValue($reseller->id, 'primary_color', '#667eea'),
            'website_url' => 'https://' . $reseller->domain,
            'domain' => $reseller->domain,
            'footer_text' => ResellerSetting::getValue($reseller->id, 'footer_text', '© ' . date('Y') . ' ' . $reseller->org_name . '. All rights reserved.'),
        ];
    }
    
    private static function getLogoUrl(Reseller $reseller): string
    {
        $logoUrl = ResellerSetting::getValue($reseller->id, 'logo_url', $reseller->logo_address);
        
        if ($logoUrl && !str_starts_with($logoUrl, 'http')) {
            return 'https://' . $reseller->domain . '/api/saas-public/logo.png';
        }
        
        return $logoUrl ?: 'https://' . $reseller->domain . '/api/saas-public/logo.png';
    }
    
    private static function getDefaultBranding(): array
    {
        return [
            'app_name' => 'AI Phone System',
            'company_name' => 'AI Phone System',
            'company_email' => 'support@example.com',
            'support_email' => 'support@example.com',
            'logo_url' => '/api/saas-public/logo.png',
            'primary_color' => '#667eea',
            'website_url' => config('app.url'),
            'domain' => parse_url(config('app.url'), PHP_URL_HOST),
            'footer_text' => '© ' . date('Y') . ' AI Phone System. All rights reserved.',
        ];
    }
}
```

### **Phase 2: Update Notification Classes**

#### **2.1 PasswordResetEmail.php**
```php
public function toMail(object $notifiable): MailMessage
{
    $branding = ResellerEmailService::getResellerBranding($notifiable);
    $resetUrl = $branding['website_url'] . "/password-reset/{$this->token}?email=" . urlencode($notifiable->getEmailForPasswordReset());

    return (new MailMessage)
        ->subject("Reset Your Password - {$branding['app_name']} 🔐")
        ->view('emails.password-reset', [
            'user' => $notifiable,
            'resetUrl' => $resetUrl,
            'branding' => $branding,
            'headerTitle' => 'Reset Your Password',
            'headerSubtitle' => 'Secure Your Account',
        ]);
}
```

#### **2.2 WelcomeEmail.php**
```php
public function toMail(object $notifiable): MailMessage
{
    $branding = ResellerEmailService::getResellerBranding($notifiable);
    $verificationUrl = $branding['website_url'] . '/api/verify-email/' . $hash . '?t=' . $timestamp;

    return (new MailMessage)
        ->subject("Welcome to {$branding['app_name']}! 🎉")
        ->view('emails.welcome', [
            'user' => $notifiable,
            'verificationUrl' => $verificationUrl,
            'branding' => $branding,
            'headerTitle' => "Welcome to {$branding['app_name']}!",
            'headerSubtitle' => 'Your Voice AI Platform',
        ]);
}
```

#### **2.3 ResellerAdminWelcomeEmail.php**
```php
public function toMail(object $notifiable): MailMessage
{
    $branding = ResellerEmailService::getResellerBranding($notifiable, $this->reseller);
    
    return (new MailMessage)
        ->subject("Welcome to {$branding['app_name']} - Your Admin Account")
        ->view('emails.reseller-admin-welcome', [
            'user' => $notifiable,
            'reseller' => $this->reseller,
            'temporaryPassword' => $this->temporaryPassword,
            'loginUrl' => $branding['website_url'] . '/login',
            'branding' => $branding,
        ]);
}
```

#### **2.4 SubscriptionInvoice.php**
```php
public function toMail(object $notifiable): MailMessage
{
    $branding = ResellerEmailService::getResellerBranding($notifiable);
    
    return (new MailMessage)
        ->subject("Invoice for {$package->name} Subscription - {$invoiceNumber}")
        ->view('emails.subscription-invoice', [
            'user' => $notifiable,
            'package' => $package,
            'amount' => $amount,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate,
            'dueDate' => $dueDate,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'branding' => $branding,
        ]);
}
```

### **Phase 3: Update Base Email Layout**

#### **3.1 emails/layouts/base.blade.php**
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? $branding['app_name'] }}</title>
    <style>
        /* Existing styles... */
        
        /* Dynamic branding colors */
        :root {
            --primary-color: {{ $branding['primary_color'] ?? '#667eea' }};
            --primary-gradient: linear-gradient(135deg, {{ $branding['primary_color'] ?? '#667eea' }} 0%, #764ba2 100%);
        }
        
        .header {
            background: var(--primary-gradient);
        }
        
        .cta-button {
            background: var(--primary-gradient);
        }
        
        /* ... rest of styles using CSS variables ... */
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">
                @if($branding['logo_url'])
                    <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] }}" style="height: 40px; margin-right: 10px;">
                @else
                    <span class="logo-icon"></span>
                @endif
                {{ $branding['app_name'] }}
            </div>
            <h1>{{ $headerTitle ?? 'Welcome' }}</h1>
            <p>{{ $headerSubtitle ?? 'Your Voice AI Platform' }}</p>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <div class="footer-links">
                <a href="{{ $branding['website_url'] }}">Home</a>
                <a href="{{ $branding['website_url'] }}/dashboard">Dashboard</a>
                <a href="{{ $branding['website_url'] }}/support">Support</a>
                <a href="{{ $branding['website_url'] }}/terms">Terms of Service</a>
                <a href="{{ $branding['website_url'] }}/privacy">Privacy Policy</a>
            </div>
            
            <p><strong>{{ $branding['company_name'] }} Team</strong></p>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>For support, contact us at <a href="mailto:{{ $branding['support_email'] }}">{{ $branding['support_email'] }}</a></p>
            <p>{{ $branding['footer_text'] }}</p>
        </div>
    </div>
</body>
</html>
```

### **Phase 4: Update Individual Email Templates**

#### **4.1 password-reset.blade.php**
```php
@extends('emails.layouts.base')

@section('content')
    <h2>Reset Your Password 🔐</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, we received a request to reset your password for your {{ $branding['app_name'] }} account.</p>
    
    <!-- ... existing content with branding variables ... -->
    
    <p>Once you've reset your password, you'll be able to access all your {{ $branding['app_name'] }} features including:</p>
    
    <!-- ... rest of template using $branding variables ... -->
@endsection
```

#### **4.2 welcome.blade.php**
```php
@extends('emails.layouts.base')

@section('content')
    <h2>Welcome to {{ $branding['app_name'] }}, {{ $user->name }}! 🎉</h2>
    
    <p>We're thrilled to have you join our community of voice AI enthusiasts. You're now part of a platform that's revolutionizing how people interact with artificial intelligence through voice.</p>
    
    <!-- ... existing content with branding variables ... -->
    
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🚀 What You Can Do With {{ $branding['app_name'] }}</h3>
    
    <!-- ... rest of template using $branding variables ... -->
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Note:</strong> If you did not create an account with {{ $branding['app_name'] }}, please ignore this email.
    </p>
@endsection
```

#### **4.3 contact-submission.blade.php**
```php
@extends('emails.layouts.base')

@section('content')
    <h2>New Contact Form Submission 📧</h2>
    
    <p>A new contact form has been submitted on the {{ $branding['app_name'] }} website. Here are the details:</p>
    
    <!-- ... existing content ... -->
@endsection
```

### **Phase 5: Handle Contact Form and System Emails**

#### **5.1 Update Contact Form Controllers**
Modify contact form submission controllers to detect the current reseller and pass branding data:

```php
// In ContactController or similar
public function submitContactForm(Request $request)
{
    // ... validation and processing ...
    
    // Get current reseller from middleware
    $reseller = app('currentReseller');
    $branding = ResellerEmailService::getResellerBranding(null, $reseller);
    
    // Send confirmation email to user
    Mail::send('emails.contact-confirmation', [
        'contact' => $contact,
        'branding' => $branding,
    ], function($message) use ($contact, $branding) {
        $message->to($contact->email)
                ->subject("Thank you for contacting {$branding['app_name']}");
    });
    
    // Send notification email to admin
    Mail::send('emails.contact-submission', [
        'contact' => $contact,
        'branding' => $branding,
    ], function($message) use ($branding) {
        $message->to($branding['support_email'])
                ->subject("New Contact Form Submission - {$branding['app_name']}");
    });
}
```

## 📋 **Implementation Checklist**

### **Phase 1: Infrastructure** ✅
- [ ] Create `ResellerEmailService.php`
- [ ] Add reseller relationship to User model (if not exists)
- [ ] Test service with different reseller scenarios
- [ ] Create helper methods for logo URL generation

### **Phase 2: Notification Classes** 📧
- [ ] Update `PasswordResetEmail.php`
- [ ] Update `WelcomeEmail.php` 
- [ ] Update `ResellerAdminWelcomeEmail.php`
- [ ] Update `SubscriptionInvoice.php`
- [ ] Test all notification classes with reseller data

### **Phase 3: Base Layout** 🎨
- [ ] Update `emails/layouts/base.blade.php`
- [ ] Add dynamic CSS variables for branding colors
- [ ] Add conditional logo display logic
- [ ] Update footer with dynamic company information
- [ ] Test responsive design with different logo sizes

### **Phase 4: Email Templates** 📄
- [ ] Update `password-reset.blade.php`
- [ ] Update `welcome.blade.php`
- [ ] Update `contact-confirmation.blade.php`
- [ ] Update `contact-submission.blade.php`
- [ ] Update `subscription-invoice.blade.php`
- [ ] Update `subscription-updated.blade.php`
- [ ] Update `subscription-cancelled.blade.php`
- [ ] Update `payment-failed.blade.php`

### **Phase 5: Controller Integration** 🔧
- [ ] Update contact form controllers
- [ ] Update authentication controllers
- [ ] Update subscription/billing controllers
- [ ] Ensure all email sends include branding data

### **Phase 6: Testing & Validation** ✅
- [ ] Test emails with different resellers
- [ ] Verify logo display in email clients
- [ ] Test fallback behavior for missing data
- [ ] Check email rendering in major email clients
- [ ] Validate all links use correct domain
- [ ] Test color customization

## 🔧 **Technical Considerations**

### **Email Client Compatibility**
- Use inline CSS for critical styling
- Test logo display across email clients
- Ensure fallback fonts are specified
- Use table-based layouts for better compatibility

### **Performance**
- Cache reseller branding data where possible
- Optimize image sizes for email logos
- Use CDN for logo delivery if needed

### **Security**
- Validate all reseller data before use
- Sanitize user input in email content
- Use secure logo URLs (HTTPS)

### **Fallback Strategy**
- Always provide default values
- Handle missing reseller gracefully
- Log errors for debugging
- Maintain email functionality even if branding fails

## 🎯 **Expected Outcomes**

### **User Experience**
- ✅ Users receive emails with their reseller's branding
- ✅ Consistent branding across web app and emails
- ✅ Professional appearance for all resellers
- ✅ Proper logo display in email clients

### **Business Benefits**
- ✅ White-label email experience
- ✅ Improved brand consistency
- ✅ Enhanced reseller satisfaction
- ✅ Professional multi-tenant email system

### **Technical Benefits**
- ✅ Centralized branding management
- ✅ Easy to add new email templates
- ✅ Maintainable and scalable architecture
- ✅ Consistent with existing reseller system

## 🚀 **Future Enhancements**

### **Advanced Features**
- Email template customization per reseller
- Custom email signatures
- Reseller-specific email domains
- A/B testing for email templates
- Email analytics per reseller

### **Integration Possibilities**
- Integration with reseller CRM systems
- Custom SMTP settings per reseller
- Email template builder interface
- Automated email campaigns per reseller

This migration will complete the reseller branding system by ensuring all user communications maintain consistent branding across web and email touchpoints!
