# Vapi Call Sync Documentation

## Overview

This document describes the Vapi call synchronization system that fetches call data from the [Vapi API](https://docs.vapi.ai/api-reference/calls/list) and syncs it with our local call logs database.

## Features

### **1. Comprehensive Call Sync**
- **API Integration**: Fetches calls from Vapi API using assistant filtering
- **Data Mapping**: Maps Vapi call data to our database schema
- **Duplicate Prevention**: Checks for existing calls before creating new entries
- **Full Data Storage**: Stores complete API response in `webhook_data` field

### **2. Flexible Sync Options**
- **All Assistants**: Sync calls for all assistants with `vapi_assistant_id`
- **Specific Assistant**: Sync calls for a specific assistant
- **Configurable Limits**: Set number of calls to fetch per assistant
- **Dry Run Mode**: Test sync without making database changes

### **3. Data Extraction**
- **Transcript**: Extracts call transcript from `artifact.transcript`
- **Summary**: Extracts call summary from `analysis.summary`
- **Metadata**: Stores assistant metadata, cost breakdown, and end reasons
- **Cost Information**: Maps cost data with proper currency handling

## Commands

### **1. Sync Calls Command**
```bash
# Sync all assistants
php artisan vapi:sync-calls

# Sync specific assistant
php artisan vapi:sync-calls --assistant-id=1

# Limit calls per assistant
php artisan vapi:sync-calls --limit=50

# Dry run (no database changes)
php artisan vapi:sync-calls --dry-run

# Combine options
php artisan vapi:sync-calls --assistant-id=1 --limit=25 --dry-run
```

### **2. Test Sync Command**
```bash
# Test API connection for all assistants
php artisan vapi:test-sync

# Test specific assistant
php artisan vapi:test-sync --assistant-id=1

# Test with limited calls
php artisan vapi:test-sync --limit=3
```

## Configuration

### **Environment Variables**
Add these to your `.env` file:
```env
VAPI_TOKEN=your_vapi_api_token_here
VAPI_BASE_URL=https://api.vapi.ai
```

### **Services Configuration**
The sync system uses the `config/services.php` configuration:
```php
'vapi' => [
    'token' => env('VAPI_TOKEN'),
    'base_url' => env('VAPI_BASE_URL', 'https://api.vapi.ai'),
],
```

## Data Mapping

### **Vapi API Response Structure**
Based on the [Vapi API documentation](https://docs.vapi.ai/api-reference/calls/list), the sync system maps the following fields:

```json
{
  "id": "call_id",
  "status": "completed",
  "type": "inboundPhoneCall",
  "startedAt": "2024-01-01T10:00:00Z",
  "endedAt": "2024-01-01T10:05:00Z",
  "cost": 0.50,
  "phoneNumber": {
    "number": "+1234567890"
  },
  "customer": {
    "number": "+0987654321"
  },
  "artifact": {
    "transcript": "Call transcript here..."
  },
  "analysis": {
    "summary": "Call summary here..."
  },
  "assistant": {
    "metadata": {...}
  },
  "costBreakdown": {...},
  "endedReason": "customer-ended-call"
}
```

### **Database Mapping**
```php
// Basic Information
$callLog->call_id = $call['id'];
$callLog->assistant_id = $assistant->id;
$callLog->user_id = $assistant->user_id;

// Phone Numbers
$callLog->phone_number = $call['phoneNumber']['number'] ?? null;
$callLog->caller_number = $call['customer']['number'] ?? null;

// Timing
$callLog->start_time = Carbon::parse($call['startedAt']);
$callLog->end_time = Carbon::parse($call['endedAt']);
$callLog->duration = $end_time->diffInSeconds($start_time);

// Status & Direction
$callLog->status = mapVapiStatus($call['status']);
$callLog->direction = mapVapiDirection($call['type']);

// Cost
$callLog->cost = $call['cost'];
$callLog->currency = 'USD';

// Full Data
$callLog->webhook_data = $call; // Complete API response

// Extracted Data
$callLog->transcript = $call['artifact']['transcript'] ?? null;
$callLog->summary = $call['analysis']['summary'] ?? null;

// Metadata
$callLog->metadata = [
    'assistant_metadata' => $call['assistant']['metadata'] ?? null,
    'ended_reason' => $call['endedReason'] ?? null,
    'cost_breakdown' => $call['costBreakdown'] ?? null,
];
```

## Status Mapping

### **Vapi Status → Database Status**
```php
$statusMap = [
    'scheduled' => 'initiated',
    'ringing' => 'ringing',
    'in-progress' => 'in-progress',
    'completed' => 'completed',
    'failed' => 'failed',
    'busy' => 'busy',
    'no-answer' => 'no-answer',
    'cancelled' => 'cancelled',
];
```

### **Vapi Type → Database Direction**
```php
// Inbound calls
'inboundPhoneCall' => 'inbound'
'webCall' => 'inbound'

// Outbound calls
'outboundPhoneCall' => 'outbound'
```

## Usage Examples

### **1. Initial Sync (All Assistants)**
```bash
# First, test the connection
php artisan vapi:test-sync

# Then run a dry run to see what would be synced
php artisan vapi:sync-calls --dry-run

# Finally, run the actual sync
php artisan vapi:sync-calls
```

### **2. Sync Specific Assistant**
```bash
# Test specific assistant
php artisan vapi:test-sync --assistant-id=1

# Sync specific assistant
php artisan vapi:sync-calls --assistant-id=1
```

### **3. Limited Sync for Testing**
```bash
# Test with only 5 calls per assistant
php artisan vapi:test-sync --limit=5

# Sync with only 10 calls per assistant
php artisan vapi:sync-calls --limit=10
```

## Error Handling

### **Common Issues**

#### **1. Missing VAPI_TOKEN**
```
Error: VAPI_TOKEN not found in environment variables
Solution: Add VAPI_TOKEN to your .env file
```

#### **2. Invalid Assistant IDs**
```
Error: No assistants found with vapi_assistant_id
Solution: Ensure assistants have vapi_assistant_id set in database
```

#### **3. API Authentication Errors**
```
Error: Vapi API error: 401 - Unauthorized
Solution: Check VAPI_TOKEN is correct and has proper permissions
```

#### **4. Rate Limiting**
```
Error: Vapi API error: 429 - Too Many Requests
Solution: Reduce sync frequency or contact Vapi support
```

### **Logging**
The sync system logs all operations:
```php
Log::info('Created call log from Vapi sync', [
    'call_id' => $call['id'],
    'assistant_id' => $assistant->id,
    'user_id' => $assistant->user_id
]);
```

## Database Requirements

### **Assistant Model**
Must have `vapi_assistant_id` field:
```php
protected $fillable = [
    'name',
    'user_id',
    'vapi_assistant_id', // Required for sync
    // ... other fields
];
```

### **CallLog Model**
Must support all mapped fields:
```php
protected $fillable = [
    'call_id',
    'assistant_id',
    'user_id',
    'phone_number',
    'caller_number',
    'duration',
    'status',
    'direction',
    'start_time',
    'end_time',
    'transcript',
    'summary',
    'metadata',
    'webhook_data',
    'cost',
    'currency',
    'call_record_file_name',
];
```

## API Reference

### **Vapi API Endpoint**
- **URL**: `https://api.vapi.ai/call`
- **Method**: `GET`
- **Authentication**: Bearer token
- **Parameters**:
  - `assistantId`: Filter by assistant ID
  - `limit`: Maximum number of calls to return (default: 100, max: 1000)

### **Response Format**
The API returns an array of call objects with the structure shown in the data mapping section above.

## Best Practices

### **1. Regular Sync Schedule**
```bash
# Add to crontab for daily sync
0 2 * * * cd /path/to/app && php artisan vapi:sync-calls
```

### **2. Test Before Production**
```bash
# Always test first
php artisan vapi:test-sync
php artisan vapi:sync-calls --dry-run
```

### **3. Monitor Logs**
```bash
# Check sync logs
tail -f storage/logs/laravel.log | grep "Vapi sync"
```

### **4. Backup Before Large Syncs**
```bash
# Backup call logs before major sync
php artisan tinker
>>> DB::table('call_logs')->count(); // Check current count
```

## Troubleshooting

### **1. API Connection Issues**
```bash
# Test API connection
php artisan vapi:test-sync --limit=1
```

### **2. Data Mapping Issues**
```bash
# Check sample data structure
php artisan vapi:test-sync --limit=1
```

### **3. Database Issues**
```bash
# Check database structure
php artisan migrate:status
```

### **4. Memory Issues**
```bash
# Use smaller limits for large datasets
php artisan vapi:sync-calls --limit=50
```

The Vapi sync system provides a robust way to synchronize call data from the Vapi API with your local database, ensuring all call information is properly stored and accessible for analytics and reporting. 