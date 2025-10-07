# Reseller Mail Configuration System

## 🎯 Overview

This document outlines the plan to implement a multi-tenant email configuration system where each reseller can provide their own SMTP settings. When sending emails to a reseller's clients, the system will use the reseller's mail configuration. If no configuration is provided, the system will fall back to the default mail settings.

## 🔍 Current System Analysis

### Reseller Settings Management
- `ResellerSettings.vue` provides an admin interface for reseller-specific settings
- `ResellerSettingController.php` handles saving and retrieving reseller settings
- `ResellerSetting` model stores key-value pairs for reseller configurations

### Email System
- Uses Laravel's mail system with a single global configuration
- Email templates are already reseller-aware (branding, colors, etc.)
- Notification classes have been updated to use reseller branding

## 📋 Implementation Plan

### 1. Database Schema Updates

#### Add Mail Configuration Fields to Reseller Settings:

```php
// Required fields for SMTP configuration
[
    'mail_mailer' => 'smtp',
    'mail_host' => 'smtp.example.com',
    'mail_port' => '587',
    'mail_username' => 'username',
    'mail_password' => 'password',
    'mail_encryption' => 'tls',
    'mail_from_address' => 'hello@example.com',
    'mail_from_name' => 'Example Company'
]
```

### 2. Update Frontend (ResellerSettings.vue)

Add a new "Email Configuration" section to the settings form:

```vue
<!-- Email Configuration Settings -->
<div class="px-6 py-4 border-b border-gray-200">
  <h3 class="text-lg font-medium text-gray-900">Email Configuration</h3>
  <p class="text-sm text-gray-500">Configure SMTP settings for sending emails to your clients</p>
</div>
<div class="px-6 py-4 space-y-6">
  <div class="flex items-center justify-between">
    <div class="flex items-center">
      <input
        id="enable_custom_mail"
        v-model="settings.mail_enabled"
        type="checkbox"
        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
      />
      <label for="enable_custom_mail" class="ml-2 block text-sm font-medium text-gray-700">
        Use custom mail configuration
      </label>
    </div>
    <button
      v-if="settings.mail_enabled"
      @click="testMailConfig"
      type="button"
      :disabled="testingMail"
      class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    >
      {{ testingMail ? 'Testing...' : 'Test Configuration' }}
    </button>
  </div>

  <div v-if="settings.mail_enabled" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Mail Driver</label>
        <select
          v-model="settings.mail_mailer"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="smtp">SMTP</option>
          <option value="sendmail">Sendmail</option>
          <option value="mailgun">Mailgun</option>
          <option value="ses">Amazon SES</option>
          <option value="postmark">Postmark</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
        <input
          v-model="settings.mail_host"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="smtp.example.com"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
        <input
          v-model="settings.mail_port"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="587"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
        <select
          v-model="settings.mail_encryption"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="tls">TLS</option>
          <option value="ssl">SSL</option>
          <option value="">None</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
        <input
          v-model="settings.mail_username"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="username@example.com"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
        <input
          v-model="settings.mail_password"
          type="password"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="••••••••"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">From Address</label>
        <input
          v-model="settings.mail_from_address"
          type="email"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="no-reply@example.com"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">From Name</label>
        <input
          v-model="settings.mail_from_name"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="Company Name"
        />
      </div>
    </div>
  </div>
</div>
```

### 3. Update ResellerSettingController.php

Add mail configuration fields to the controller:

```php
// Add to initializeDefaults method
$defaultSettings[] = [
    'key' => 'mail_enabled',
    'value' => 'false',
    'label' => 'Use Custom Mail Configuration',
    'type' => 'boolean',
    'group' => 'mail',
    'description' => 'Enable custom mail server configuration'
];

$defaultSettings[] = [
    'key' => 'mail_mailer',
    'value' => 'smtp',
    'label' => 'Mail Driver',
    'type' => 'select',
    'group' => 'mail',
    'description' => 'Mail driver (smtp, sendmail, etc.)'
];

$defaultSettings[] = [
    'key' => 'mail_host',
    'value' => '',
    'label' => 'SMTP Host',
    'type' => 'text',
    'group' => 'mail',
    'description' => 'SMTP server hostname'
];

$defaultSettings[] = [
    'key' => 'mail_port',
    'value' => '587',
    'label' => 'SMTP Port',
    'type' => 'text',
    'group' => 'mail',
    'description' => 'SMTP server port'
];

$defaultSettings[] = [
    'key' => 'mail_username',
    'value' => '',
    'label' => 'SMTP Username',
    'type' => 'text',
    'group' => 'mail',
    'description' => 'SMTP authentication username'
];

$defaultSettings[] = [
    'key' => 'mail_password',
    'value' => '',
    'label' => 'SMTP Password',
    'type' => 'password',
    'group' => 'mail',
    'description' => 'SMTP authentication password'
];

$defaultSettings[] = [
    'key' => 'mail_encryption',
    'value' => 'tls',
    'label' => 'Encryption',
    'type' => 'select',
    'group' => 'mail',
    'description' => 'Type of encryption (tls, ssl)'
];

$defaultSettings[] = [
    'key' => 'mail_from_address',
    'value' => '',
    'label' => 'From Address',
    'type' => 'email',
    'group' => 'mail',
    'description' => 'Default email address to send from'
];

$defaultSettings[] = [
    'key' => 'mail_from_name',
    'value' => $reseller->org_name,
    'label' => 'From Name',
    'type' => 'text',
    'group' => 'mail',
    'description' => 'Default sender name'
];
```

### 4. Create ResellerMailManager Service

Create a new service class to handle reseller-specific mail configurations:

```php
<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\ResellerSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ResellerMailManager
{
    /**
     * Set mail configuration for a specific reseller
     */
    public static function setMailConfig(?Reseller $reseller = null): void
    {
        // If no reseller provided, try to get from current request
        if (!$reseller) {
            $reseller = app('currentReseller');
        }

        // If still no reseller, use default mail config
        if (!$reseller) {
            return;
        }

        // Check if reseller has custom mail config enabled
        $mailEnabled = ResellerSetting::getValue($reseller->id, 'mail_enabled');
        if (!$mailEnabled || $mailEnabled === 'false') {
            return;
        }

        // Get mail settings
        $mailMailer = ResellerSetting::getValue($reseller->id, 'mail_mailer');
        $mailHost = ResellerSetting::getValue($reseller->id, 'mail_host');
        $mailPort = ResellerSetting::getValue($reseller->id, 'mail_port');
        $mailUsername = ResellerSetting::getValue($reseller->id, 'mail_username');
        $mailPassword = ResellerSetting::getValue($reseller->id, 'mail_password');
        $mailEncryption = ResellerSetting::getValue($reseller->id, 'mail_encryption');
        $mailFromAddress = ResellerSetting::getValue($reseller->id, 'mail_from_address');
        $mailFromName = ResellerSetting::getValue($reseller->id, 'mail_from_name');

        // Validate required settings
        if (empty($mailHost) || empty($mailUsername) || empty($mailPassword)) {
            Log::warning('Incomplete mail configuration for reseller', [
                'reseller_id' => $reseller->id,
                'domain' => $reseller->domain
            ]);
            return;
        }

        // Set mail configuration at runtime
        Config::set('mail.default', $mailMailer);
        Config::set('mail.mailers.smtp.host', $mailHost);
        Config::set('mail.mailers.smtp.port', $mailPort);
        Config::set('mail.mailers.smtp.username', $mailUsername);
        Config::set('mail.mailers.smtp.password', $mailPassword);
        Config::set('mail.mailers.smtp.encryption', $mailEncryption);
        Config::set('mail.from.address', $mailFromAddress ?: $reseller->company_email);
        Config::set('mail.from.name', $mailFromName ?: $reseller->org_name);

        Log::info('Using custom mail configuration for reseller', [
            'reseller_id' => $reseller->id,
            'domain' => $reseller->domain,
            'mail_host' => $mailHost
        ]);
    }

    /**
     * Test mail configuration for a reseller
     */
    public static function testMailConfig(Reseller $reseller): array
    {
        try {
            // Backup current configuration
            $defaultMailer = Config::get('mail.default');
            $defaultHost = Config::get('mail.mailers.smtp.host');
            $defaultPort = Config::get('mail.mailers.smtp.port');
            $defaultUsername = Config::get('mail.mailers.smtp.username');
            $defaultPassword = Config::get('mail.mailers.smtp.password');
            $defaultEncryption = Config::get('mail.mailers.smtp.encryption');
            $defaultFromAddress = Config::get('mail.from.address');
            $defaultFromName = Config::get('mail.from.name');

            // Set reseller configuration
            self::setMailConfig($reseller);

            // Create a test mailer and check connection
            $transport = new \Swift_SmtpTransport(
                Config::get('mail.mailers.smtp.host'),
                Config::get('mail.mailers.smtp.port'),
                Config::get('mail.mailers.smtp.encryption')
            );
            
            $transport->setUsername(Config::get('mail.mailers.smtp.username'));
            $transport->setPassword(Config::get('mail.mailers.smtp.password'));
            
            // Try to connect
            $transport->start();
            
            // Restore original configuration
            Config::set('mail.default', $defaultMailer);
            Config::set('mail.mailers.smtp.host', $defaultHost);
            Config::set('mail.mailers.smtp.port', $defaultPort);
            Config::set('mail.mailers.smtp.username', $defaultUsername);
            Config::set('mail.mailers.smtp.password', $defaultPassword);
            Config::set('mail.mailers.smtp.encryption', $defaultEncryption);
            Config::set('mail.from.address', $defaultFromAddress);
            Config::set('mail.from.name', $defaultFromName);
            
            return [
                'success' => true,
                'message' => 'Mail configuration is valid'
            ];
        } catch (\Exception $e) {
            // Restore original configuration
            Config::set('mail.default', $defaultMailer ?? 'smtp');
            Config::set('mail.mailers.smtp.host', $defaultHost ?? '');
            Config::set('mail.mailers.smtp.port', $defaultPort ?? '587');
            Config::set('mail.mailers.smtp.username', $defaultUsername ?? '');
            Config::set('mail.mailers.smtp.password', $defaultPassword ?? '');
            Config::set('mail.mailers.smtp.encryption', $defaultEncryption ?? 'tls');
            Config::set('mail.from.address', $defaultFromAddress ?? '');
            Config::set('mail.from.name', $defaultFromName ?? '');
            
            Log::error('Mail configuration test failed', [
                'reseller_id' => $reseller->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Mail configuration test failed: ' . $e->getMessage()
            ];
        }
    }
}
```

### 5. Add Test Mail Configuration Endpoint

Add a new endpoint to test mail configuration:

```php
// In routes/api.php
Route::post('/reseller/settings/test-mail', [ResellerSettingController::class, 'testMailConfig'])
    ->middleware(['auth:sanctum', 'reseller']);

// In ResellerSettingController.php
public function testMailConfig(Request $request): JsonResponse
{
    $user = Auth::user();
    $resellerId = config('reseller.id');

    // Check if user is reseller admin
    if (!$user->isContentAdmin()) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Only reseller admins can test mail configuration.'
        ], 403);
    }

    $reseller = \App\Models\Reseller::find($resellerId);
    if (!$reseller) {
        return response()->json([
            'success' => false,
            'message' => 'Reseller not found'
        ], 404);
    }

    // Test mail configuration
    $result = ResellerMailManager::testMailConfig($reseller);

    return response()->json($result);
}
```

### 6. Update Notification System

Modify the base notification sending logic to use reseller-specific mail configuration:

```php
<?php

namespace App\Traits;

use App\Services\ResellerMailManager;
use Illuminate\Notifications\Notification;

trait ResellerMailTrait
{
    /**
     * Send the given notification.
     */
    public function sendNotification($instance)
    {
        // Set mail configuration based on user's reseller
        if (method_exists($this, 'getResellerId')) {
            $reseller = \App\Models\Reseller::find($this->getResellerId());
            if ($reseller) {
                ResellerMailManager::setMailConfig($reseller);
            }
        }

        // Call the original notification method
        parent::sendNotification($instance);
    }
    
    /**
     * Get the reseller ID for the notifiable entity
     */
    public function getResellerId()
    {
        return $this->reseller_id ?? null;
    }
}
```

### 7. Update User Model

Add the trait to the User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\ResellerMailTrait;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, ResellerMailTrait;
    
    /**
     * Get the reseller ID for the user
     */
    public function getResellerId()
    {
        return $this->reseller_id;
    }
    
    // Rest of the User model...
}
```

### 8. Update ResellerSettings.vue JavaScript

Add mail configuration fields to the settings component:

```javascript
// Add to settings ref
const settings = ref({
  // Existing fields...
  mail_enabled: 'false',
  mail_mailer: 'smtp',
  mail_host: '',
  mail_port: '587',
  mail_username: '',
  mail_password: '',
  mail_encryption: 'tls',
  mail_from_address: '',
  mail_from_name: ''
})

// Add test mail configuration method
const testingMail = ref(false)

const testMailConfig = async () => {
  try {
    testingMail.value = true
    const response = await axios.post('/api/reseller/settings/test-mail')
    
    if (response.data.success) {
      showSuccess('Mail Configuration Valid', 'Your mail server configuration is working correctly.')
    } else {
      showError('Mail Configuration Error', response.data.message)
    }
  } catch (error) {
    console.error('Error testing mail config:', error)
    if (error.response?.data?.message) {
      showError('Mail Configuration Error', error.response.data.message)
    } else {
      showError('Mail Configuration Error', 'Failed to test mail configuration')
    }
  } finally {
    testingMail.value = false
  }
}

// Add to return statement
return {
  // Existing returns...
  testingMail,
  testMailConfig
}
```

### 9. Update getFieldGroup and getFieldLabel

Update the helper functions in ResellerSettings.vue:

```javascript
const getFieldGroup = (key) => {
  const groupMap = {
    // Existing mappings...
    'mail_enabled': 'mail',
    'mail_mailer': 'mail',
    'mail_host': 'mail',
    'mail_port': 'mail',
    'mail_username': 'mail',
    'mail_password': 'mail',
    'mail_encryption': 'mail',
    'mail_from_address': 'mail',
    'mail_from_name': 'mail'
  }
  return groupMap[key] || 'general'
}

const getFieldLabel = (key) => {
  const labelMap = {
    // Existing mappings...
    'mail_enabled': 'Use Custom Mail Configuration',
    'mail_mailer': 'Mail Driver',
    'mail_host': 'SMTP Host',
    'mail_port': 'SMTP Port',
    'mail_username': 'SMTP Username',
    'mail_password': 'SMTP Password',
    'mail_encryption': 'Encryption',
    'mail_from_address': 'From Address',
    'mail_from_name': 'From Name'
  }
  return labelMap[key] || key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}
```

### 10. Implement Mail Service Provider

Create a new service provider to integrate with Laravel's mail system:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;
use App\Services\ResellerMailManager;

class ResellerMailServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listen for mail sending events
        Event::listen(MessageSending::class, function (MessageSending $event) {
            // Check if we need to set reseller-specific mail configuration
            if (app()->has('currentReseller')) {
                $reseller = app('currentReseller');
                if ($reseller) {
                    ResellerMailManager::setMailConfig($reseller);
                }
            }
        });
    }
}
```

Register the service provider in `config/app.php`:

```php
'providers' => [
    // Other providers...
    App\Providers\ResellerMailServiceProvider::class,
],
```

## 📊 Implementation Workflow

1. **Database Setup**
   - Add mail configuration fields to ResellerSetting model
   - Update ResellerSettingController with default mail settings

2. **Backend Services**
   - Create ResellerMailManager service
   - Create ResellerMailTrait for models
   - Implement ResellerMailServiceProvider

3. **Frontend Updates**
   - Add Email Configuration section to ResellerSettings.vue
   - Add test mail configuration functionality
   - Update field mappings and labels

4. **Testing**
   - Test mail configuration validation
   - Test sending emails with reseller-specific configuration
   - Test fallback to default mail configuration

## 🔒 Security Considerations

1. **Password Storage**
   - Store SMTP passwords securely (encrypted in database)
   - Consider using Laravel's encryption for sensitive fields

2. **Access Control**
   - Only allow reseller admins to view/edit mail configuration
   - Validate all inputs thoroughly

3. **Rate Limiting**
   - Implement rate limiting for mail configuration testing
   - Prevent abuse of mail testing functionality

## 📈 Expected Benefits

1. **White-Label Email Experience**
   - Emails sent from reseller's own mail servers
   - Consistent branding across all touchpoints

2. **Improved Deliverability**
   - Resellers can use their own trusted mail servers
   - Better domain reputation management

3. **Flexibility**
   - Each reseller can choose their preferred mail provider
   - Support for various mail drivers (SMTP, Mailgun, etc.)

4. **Fallback Mechanism**
   - System continues to function even if reseller mail config is invalid
   - Default mail configuration as safety net

## 🚀 Future Enhancements

1. **Email Templates Customization**
   - Allow resellers to customize email templates
   - Template version control and preview

2. **Advanced Mail Features**
   - Support for additional mail drivers (SES, Postmark, etc.)
   - Email analytics per reseller

3. **DKIM/SPF Configuration**
   - Guide for setting up proper email authentication
   - Verification of proper DNS records

This implementation will provide a complete multi-tenant email system where each reseller can use their own mail servers while maintaining a consistent user experience.
