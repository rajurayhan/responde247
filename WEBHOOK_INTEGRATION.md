# Webhook URL Integration

This document explains the webhook URL feature that allows assistants to send end-of-call report events to external services.

## Overview

The webhook URL feature enables assistants to send call completion data to external services (like n8n) for further processing, analytics, or integrations.

## Features

### 1. Webhook URL Field
- **Location**: Assistant Form → Messaging Configuration section
- **Field Type**: URL input with validation
- **Default Value**: Empty (optional)
- **Validation**: Must be a valid URL, maximum 500 characters
- **Storage**: Stored in both database and Vapi metadata

### 2. End-of-Call Report Events
When a call ends, Vapi will send a POST request to the configured webhook URL with call data including:
- Call duration
- Call status (completed, failed, etc.)
- Call metadata
- Assistant information
- User interaction data

## Usage

### Adding Webhook URL to Assistant

1. **Navigate to Assistant Form**
   - Go to Create Assistant or Edit Assistant page
   - Scroll to "Messaging Configuration" section

2. **Enter Webhook URL**
   - Find the "Webhook URL (End-of-Call Report)" field
   - Enter a valid URL (e.g., `https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends`)
   - The field is optional - leave empty if not needed

3. **Save Assistant**
   - The webhook URL will be saved with the assistant configuration
   - It will be sent to Vapi.ai for call event handling

### Example Webhook URL
```
https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents
```

## Database Schema

### Assistants Table
```sql
ALTER TABLE assistants ADD COLUMN webhook_url VARCHAR(500) NULL AFTER phone_number;
```

### Assistant Model
```php
protected $fillable = [
    'name',
    'user_id', 
    'vapi_assistant_id',
    'created_by',
    'type',
    'phone_number',
    'webhook_url', // New field
];
```

## API Endpoints

### Create Assistant
```http
POST /api/assistants
```

**Request Body:**
```json
{
  "name": "My Assistant",
  "type": "demo",
  "model": { ... },
  "voice": { ... },
  "firstMessage": "...",
  "endCallMessage": "...",
  "metadata": {
    "company_name": "My Company",
    "industry": "Technology",
    "services_products": "Software Development",
    "webhook_url": "https://n8n.cloud.lhgdev.com/webhook/lhg-nora-vapi-call-ends"
  }
}
```

**Vapi API Structure (automatically generated):**
```json
{
  "name": "My Assistant",
  "model": { ... },
  "voice": { ... },
  "firstMessage": "...",
  "endCallMessage": "...",
  "server": {
    "url": "https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
  },
  "serverMessages": [
    "end-of-call-report"
  ],
  "metadata": {
    "company_name": "My Company",
    "industry": "Technology",
    "services_products": "Software Development",
    "webhook_url": "https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
  }
}
```

### Update Assistant
```http
PUT /api/assistants/{assistantId}
```

**Request Body:** Same as create, with optional webhook_url field.

## Validation Rules

### Webhook URL Validation
- **Type**: URL
- **Required**: No (nullable)
- **Max Length**: 500 characters
- **Format**: Must be a valid URL

### Example Validation Errors
```json
{
  "errors": {
    "metadata.webhook_url": [
      "The metadata.webhook url must be a valid URL."
    ]
  }
}
```

## Frontend Implementation

### Vue Component
The webhook URL field is implemented in `AssistantForm.vue`:

```vue
<!-- Webhook Configuration -->
<div class="mt-6">
  <label class="block text-sm font-medium text-gray-700 mb-2">
    Webhook URL (End-of-Call Report)
  </label>
  <input
    v-model="form.metadata.webhook_url"
    type="url"
    placeholder="https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
    :class="[
      'w-full px-3 py-2 border rounded-md text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-green-500',
      fieldErrors.webhook_url 
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500 bg-red-50' 
        : 'border-gray-300 focus:border-green-500 bg-white'
    ]"
  />
  <p v-if="fieldErrors.webhook_url" class="text-xs text-red-600 mt-1">
    {{ fieldErrors.webhook_url }}
  </p>
  <p v-else class="text-xs text-gray-500 mt-1">
    URL to receive end-of-call report events. Leave empty if not needed.
  </p>
</div>
```

### Form Data Structure
```javascript
const form = ref({
  // ... other fields
  metadata: {
    company_name: '',
    industry: '',
    services_products: '',
    sms_phone_number: '',
    assistant_phone_number: '',
    webhook_url: '' // New field
  }
})
```

## Backend Implementation

### Controller Validation
```php
// AssistantController.php
$request->validate([
    // ... other validation rules
    'metadata.webhook_url' => 'nullable|url|max:500',
]);
```

### Webhook URL Synchronization
The system automatically synchronizes webhook URLs between Vapi.ai and the local database:

1. **When Loading Assistant (Show Method)**:
   - If Vapi has webhook URL but local database doesn't → Use Vapi's value
   - If both have values → Keep local database value
   - If neither has value → Use default value

2. **When Updating Assistant (Update Method)**:
   - If Vapi response has webhook URL but local database doesn't → Sync from Vapi
   - If request provides webhook URL → Use request value
   - If neither has value → Keep existing value

### Database Operations
```php
// Create assistant
$assistant = Assistant::create([
    'name' => $data['name'],
    'user_id' => $assistantUserId,
    'vapi_assistant_id' => $vapiAssistant['id'],
    'created_by' => $user->id,
    'type' => $data['type'] ?? 'demo',
    'phone_number' => $data['metadata']['assistant_phone_number'] ?? null,
    'webhook_url' => $data['metadata']['webhook_url'] ?? null, // New field
]);

// Update assistant
if ($request->has('metadata.webhook_url')) {
    $updateData['webhook_url'] = $request->input('metadata.webhook_url');
}
```

### Vapi Service Integration
```php
// VapiService.php - createAssistant method
$createData = [
    'name' => $data['name'],
    'model' => $data['model'],
    'voice' => $data['voice'],
    'firstMessage' => $data['firstMessage'] ?? '',
    'endCallMessage' => $data['endCallMessage'] ?? '',
    'metadata' => array_merge($data['metadata'] ?? [], [
        'created_at' => now()->toISOString(),
    ])
];

// Add server configuration for webhook URL
if (!empty($data['metadata']['webhook_url'])) {
    $createData['server'] = [
        'url' => $data['metadata']['webhook_url']
    ];
}

// Add serverMessages for end-of-call report
$createData['serverMessages'] = [
    'end-of-call-report'
];
```

## Testing

### Test Coverage
The webhook URL feature includes comprehensive tests:

1. **Create Assistant with Webhook URL**
   - Tests successful creation with webhook URL
   - Verifies database storage

2. **Update Assistant Webhook URL**
   - Tests updating existing webhook URL
   - Verifies database update

3. **Webhook URL Validation**
   - Tests invalid URL format
   - Verifies validation errors

4. **Null Webhook URL**
   - Tests optional webhook URL field
   - Verifies null values are accepted

### Running Tests
```bash
php artisan test tests/Feature/WebhookUrlTest.php
```

## Migration

### Safe Migration Pattern
The webhook URL column was added using the safe migration pattern:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use App\Traits\SafeMigrationTrait;

return new class extends Migration
{
    use SafeMigrationTrait;

    public function up(): void
    {
        $this->safeAddStringColumn('assistants', 'webhook_url', 500, true, 'phone_number');
    }

    public function down(): void
    {
        $this->safeDropColumn('assistants', 'webhook_url');
    }
};
```

### Migration Commands
```bash
# Run migration
php artisan migrate

# Rollback migration
php artisan migrate:rollback --step=1

# Check migration status
php artisan migrate:status
```

## Integration with Vapi.ai

### Vapi API Structure
The webhook URL is configured in the Vapi API using the following structure:

```json
{
  "name": "My Assistant",
  "model": { ... },
  "voice": { ... },
  "firstMessage": "...",
  "endCallMessage": "...",
  "server": {
    "url": "https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
  },
  "serverMessages": [
    "end-of-call-report"
  ],
  "metadata": {
    "company_name": "My Company",
    "industry": "Technology",
    "webhook_url": "https://n8n.cloud.lhgdev.com/webhook/lhg-live-demo-agents"
  }
}
```

### Webhook Event Format
When a call ends, Vapi will send a POST request to the configured server URL with end-of-call report data including:
- Call duration and status
- Assistant information
- Call metadata
- Transcript and summary data
- User interaction details

### Vapi Configuration
The webhook URL is configured in Vapi.ai using:
- **`server.url`**: The webhook endpoint URL
- **`serverMessages`**: Array containing `["end-of-call-report"]` to enable end-of-call reports

## Best Practices

### 1. URL Validation
- Always validate webhook URLs before saving
- Use HTTPS URLs for security
- Consider URL length limits

### 2. Error Handling
- Handle webhook delivery failures gracefully
- Log webhook events for debugging
- Provide fallback mechanisms

### 3. Security
- Use HTTPS webhook URLs
- Validate webhook signatures if supported
- Implement rate limiting for webhook endpoints

### 4. Testing
- Test webhook URLs with mock endpoints
- Verify webhook payload format
- Test error scenarios

## Troubleshooting

### Common Issues

1. **Invalid URL Format**
   - Ensure URL includes protocol (https://)
   - Check for typos in URL
   - Verify URL is accessible

2. **Webhook Not Receiving Events**
   - Verify webhook URL is correct
   - Check webhook endpoint is accessible
   - Review Vapi.ai webhook configuration

3. **Database Errors**
   - Run migrations: `php artisan migrate`
   - Check database schema
   - Verify model fillable attributes

### Debug Steps

1. **Check Assistant Configuration**
   ```php
   $assistant = Assistant::find($id);
   echo $assistant->webhook_url;
   ```

2. **Verify Vapi Data**
   ```php
   $vapiData = $vapiService->getAssistant($assistantId);
   echo $vapiData['server']['url'] ?? 'Not set';
   ```

3. **Check Synchronization**
   ```php
   // Check if webhook URL was synced from Vapi
   $assistant = Assistant::find($id);
   $vapiData = $vapiService->getAssistant($assistant->vapi_assistant_id);
   $vapiWebhook = $vapiData['server']['url'] ?? null;
   
   if ($vapiWebhook && !$assistant->webhook_url) {
       echo "Webhook URL should be synced from Vapi: " . $vapiWebhook;
   }
   ```

3. **Test Webhook Endpoint**
   ```bash
   curl -X POST https://your-webhook-url.com/test \
     -H "Content-Type: application/json" \
     -d '{"test": "data"}'
   ```

## Future Enhancements

### Potential Improvements

1. **Webhook Testing**
   - Add "Test Webhook" button in UI
   - Send test payload to verify connectivity

2. **Webhook Templates**
   - Pre-configured webhook URLs for common services
   - Template variables for dynamic URLs

3. **Webhook Analytics**
   - Track webhook delivery success/failure rates
   - Monitor webhook response times

4. **Multiple Webhooks**
   - Support multiple webhook URLs per assistant
   - Different webhooks for different event types

5. **Webhook Security**
   - Add webhook signature verification
   - Implement webhook authentication 