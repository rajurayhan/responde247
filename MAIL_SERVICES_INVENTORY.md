# Mail Services Inventory

This document provides a comprehensive list of all email services and notifications used throughout the AI Phone System application. Each service needs to be updated to use the new reseller-specific mail configuration system.

## 📧 Notification Classes

### 1. **PasswordResetEmail** (`app/Notifications/PasswordResetEmail.php`)
- **Purpose**: Sends password reset emails to users
- **Trigger**: When user requests password reset
- **Template**: `resources/views/emails/password-reset.blade.php`
- **Status**: ✅ **UPDATED** - Uses `ResellerEmailService` for branding
- **Usage**: 
  - `AppServiceProvider.php` - Laravel's password reset override
  - Test route: `/test-password-reset-email`

### 2. **WelcomeEmail** (`app/Notifications/WelcomeEmail.php`)
- **Purpose**: Sends welcome emails to new users
- **Trigger**: After user registration
- **Template**: `resources/views/emails/welcome.blade.php`
- **Status**: ✅ **UPDATED** - Uses `ResellerEmailService` for branding
- **Usage**:
  - `AuthController.php` - After user registration
  - Test routes: `/test-email-templates`

### 3. **ResellerAdminWelcomeEmail** (`app/Notifications/ResellerAdminWelcomeEmail.php`)
- **Purpose**: Sends welcome emails to new reseller admins
- **Trigger**: When super admin creates new reseller
- **Template**: Uses Laravel's built-in mail message format
- **Status**: ✅ **UPDATED** - Uses `ResellerEmailService` for branding
- **Usage**:
  - `ResellerController.php` - After creating reseller admin user

### 4. **SubscriptionInvoice** (`app/Notifications/SubscriptionInvoice.php`)
- **Purpose**: Sends subscription invoice emails to users
- **Trigger**: After successful subscription/payment
- **Template**: `resources/views/emails/subscription-invoice.blade.php`
- **Status**: ✅ **UPDATED** - Uses `ResellerEmailService` for branding
- **Usage**:
  - `StripeController.php` - After successful subscription creation
  - `StripeService.php` - After webhook processing
  - Test routes: `/test-invoice-email`, `/test-email-templates`

## 📨 Direct Mail Usage (Not Notifications)

### 5. **Contact Form Submission** (`app/Http/Controllers/ContactController.php`)
- **Purpose**: Sends contact form notifications to admin
- **Template**: `resources/views/emails/contact-submission.blade.php`
- **Status**: ❌ **NEEDS UPDATE** - Uses direct `Mail::send()`
- **Usage**: Line 58-67 in `ContactController.php`
- **Action Required**: Convert to notification or update to use reseller mail config

### 6. **Contact Confirmation** (`app/Http/Controllers/ContactController.php`)
- **Purpose**: Sends confirmation emails to users who submit contact form
- **Template**: `resources/views/emails/contact-confirmation.blade.php`
- **Status**: ❌ **NEEDS UPDATE** - Uses direct `Mail::send()`
- **Usage**: Line 75-82 in `ContactController.php`
- **Action Required**: Convert to notification or update to use reseller mail config

## 📧 Email Templates Available

### Base Layout
- **File**: `resources/views/emails/layouts/base.blade.php`
- **Status**: ✅ **UPDATED** - Uses reseller branding data
- **Features**: Dynamic logo, colors, company info, footer

### Individual Templates
1. **password-reset.blade.php** - ✅ Updated
2. **welcome.blade.php** - ✅ Updated  
3. **subscription-invoice.blade.php** - ✅ Updated
4. **contact-submission.blade.php** - ✅ Updated
5. **contact-confirmation.blade.php** - ❌ Needs update
6. **subscription-updated.blade.php** - ❌ Not used yet
7. **subscription-cancelled.blade.php** - ❌ Not used yet
8. **payment-failed.blade.php** - ❌ Not used yet

## 🔄 Laravel Built-in Mail Services

### 7. **Email Verification** (`app/Http/Controllers/Auth/EmailVerificationNotificationController.php`)
- **Purpose**: Sends email verification links
- **Trigger**: When user requests email verification
- **Status**: ❌ **NEEDS UPDATE** - Uses Laravel's default verification
- **Usage**: Line 21 - `$request->user()->sendEmailVerificationNotification()`
- **Action Required**: Override Laravel's default verification email

## 🚨 Services That Need Mail Configuration Updates

### High Priority (Active Usage)
1. **ContactController** - Contact form emails (Direct Mail::send)
2. **EmailVerificationNotificationController** - Email verification (Laravel default)

### Medium Priority (Templates Available, Not Used Yet)
3. **Subscription Updated** - When subscription changes
4. **Subscription Cancelled** - When user cancels subscription  
5. **Payment Failed** - When payment fails

### Low Priority (Future Features)
6. **Demo Request Confirmation** - For demo request submissions
7. **Call Log Notifications** - For important call events
8. **Assistant Status Changes** - When assistant settings change

## 📋 Implementation Status Summary

| Service | Status | Priority | Action Required |
|---------|--------|----------|-----------------|
| PasswordResetEmail | ✅ Updated | High | None |
| WelcomeEmail | ✅ Updated | High | None |
| ResellerAdminWelcomeEmail | ✅ Updated | High | None |
| SubscriptionInvoice | ✅ Updated | High | None |
| Contact Submission | ❌ Needs Update | High | Convert to notification or update Mail::send |
| Contact Confirmation | ❌ Needs Update | High | Convert to notification or update Mail::send |
| Email Verification | ❌ Needs Update | High | Override Laravel default |
| Subscription Updated | ❌ Not Implemented | Medium | Create notification class |
| Subscription Cancelled | ❌ Not Implemented | Medium | Create notification class |
| Payment Failed | ❌ Not Implemented | Medium | Create notification class |

## 🛠️ Next Steps for Implementation

### Phase 1: Fix Active Services
1. **Update ContactController emails** to use reseller mail configuration
2. **Override email verification** to use reseller branding
3. **Test all updated services** with different reseller configurations

### Phase 2: Implement Missing Services  
1. **Create SubscriptionUpdated notification**
2. **Create SubscriptionCancelled notification**
3. **Create PaymentFailed notification**
4. **Update respective templates** with reseller branding

### Phase 3: Future Enhancements
1. **Demo request email notifications**
2. **Call log email alerts**
3. **Assistant status change notifications**
4. **Email analytics per reseller**

## 🔧 Technical Implementation Notes

### For Direct Mail::send() Usage
```php
// Before (ContactController.php)
Mail::send('emails.contact-submission', [...], function($message) {
    $message->to('admin@example.com')->subject('Subject');
});

// After (with reseller mail config)
ResellerMailManager::setMailConfig($reseller);
Mail::send('emails.contact-submission', [...], function($message) {
    $message->to($branding['support_email'])->subject('Subject');
});
```

### For Laravel Default Services
```php
// Override in AppServiceProvider.php
use Illuminate\Auth\Notifications\VerifyEmail;

VerifyEmail::toMailUsing(function ($notifiable, $verificationUrl) {
    return (new CustomVerifyEmail($verificationUrl))->toMail($notifiable);
});
```

### For New Notification Classes
```php
// Follow the pattern established in existing notifications
public function toMail(object $notifiable): MailMessage
{
    $branding = ResellerEmailService::getResellerBranding($notifiable);
    
    return (new MailMessage)
        ->subject("Subject - {$branding['app_name']}")
        ->view('emails.template-name', [
            'branding' => $branding,
            // ... other data
        ]);
}
```

This inventory provides a complete roadmap for implementing reseller-specific mail configuration across all email services in the application.
