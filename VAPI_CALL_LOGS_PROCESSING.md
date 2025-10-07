# Vapi Call Logs Processing System

This document describes the comprehensive call logs processing system for handling Vapi.ai webhook data and creating detailed call analytics.

## Overview

The system processes Vapi.ai webhook events and creates detailed call logs with analytics, contact extraction, and reporting capabilities. It supports multiple webhook formats and provides comprehensive call insights.

## Components

### 1. VapiCallReportProcessor
Handles the specific `end-of-call-report` webhook format from Vapi.ai.

**Key Features:**
- Processes complex webhook data structure
- Extracts contact information from transcripts
- Maps call statuses and reasons
- Provides call quality metrics

### 2. CallLogsProcessor
Comprehensive processor for all webhook types with analytics and reporting.

**Key Features:**
- Handles multiple webhook formats
- Extracts and saves contact information
- Provides detailed analytics
- Generates call trends and reports

### 3. VapiWebhookController
Updated controller to handle `end-of-call-report` events.

## Webhook Data Structure

### End-of-Call-Report Format
```json
{
  "message": {
    "timestamp": 1753804472071,
    "type": "end-of-call-report",
    "analysis": {
      "summary": "Call summary...",
      "successEvaluation": "true"
    },
    "artifact": {
      "messages": [...],
      "transcript": "...",
      "recordingUrl": "..."
    },
    "call": {
      "id": "call-id",
      "assistantId": "assistant-id"
    },
    "startedAt": "2025-07-29T15:51:11.606Z",
    "endedAt": "2025-07-29T15:54:27.481Z",
    "endedReason": "customer-ended-call",
    "durationMs": 195875,
    "durationSeconds": 195.875,
    "cost": 0.6096,
    "phoneNumber": {
      "number": "+16825828396"
    },
    "customer": {
      "number": "+16176754444"
    }
  }
}
```

## Usage Examples

### 1. Process Sample Webhook Data

```bash
# Process the sample webhook file
php artisan vapi:process-sample sample-vapi-end-call-report-webhook.json
```

This command will:
- Parse the webhook JSON file
- Create a call log entry
- Extract contact information
- Display call quality metrics
- Show detailed call information

### 2. Process Webhook via API

```php
// In your webhook controller
$processor = new CallLogsProcessor();
$callLog = $processor->processWebhook($webhookData);

if ($callLog) {
    // Extract contact information
    $contact = $processor->extractAndSaveContact($callLog);
    
    // Get analytics
    $analytics = $processor->getCallAnalytics([
        'user_id' => $user->id,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31'
    ]);
}
```

### 3. Get Call Analytics

```php
$processor = new CallLogsProcessor();

// Get comprehensive analytics
$analytics = $processor->getCallAnalytics([
    'user_id' => 1,
    'assistant_id' => 2,
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31'
]);

// Get call trends
$trends = $processor->getCallTrends([
    'user_id' => 1
], 30); // Last 30 days

// Export call logs
$export = $processor->exportCallLogs([
    'status' => 'completed',
    'start_date' => '2024-01-01'
]);
```

## Contact Information Extraction

The system automatically extracts contact information from call transcripts:

### Extracted Fields:
- **Name**: Customer's first and last name
- **Email**: Email address
- **Phone**: Phone number
- **Company**: Company name (if mentioned)
- **Inquiry Type**: Type of inquiry or service requested

### Extraction Methods:
1. **Summary Analysis**: Uses AI-generated summary
2. **Transcript Parsing**: Searches through conversation
3. **Message Analysis**: Examines individual messages
4. **Pattern Matching**: Uses regex patterns for emails/phones

### Example Output:
```php
$contactInfo = [
    'name' => 'Hussein Atlag',
    'email' => 'hussain@gmail.com',
    'phone' => '682-582-8396',
    'company' => null,
    'inquiry_type' => 'social media marketing'
];
```

## Call Quality Metrics

The system provides comprehensive call quality metrics:

### Basic Metrics:
- **Success Rate**: Percentage of completed calls
- **Failure Rate**: Percentage of failed calls
- **Average Duration**: Average call length in minutes
- **Average Cost**: Average cost per call
- **Total Calls**: Total number of calls
- **Total Cost**: Total cost of all calls

### Advanced Metrics:
- **Status Breakdown**: Distribution by call status
- **Daily Volume**: Call volume over time
- **Assistant Performance**: Performance by assistant
- **Hourly Trends**: Call patterns by hour
- **Cost Analysis**: Detailed cost breakdown

## Database Schema

### Call Logs Table
```sql
CREATE TABLE call_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    call_id VARCHAR(255) UNIQUE NOT NULL,
    assistant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    phone_number VARCHAR(255) NULL,
    caller_number VARCHAR(255) NULL,
    duration INT NULL,
    status ENUM('initiated', 'ringing', 'in-progress', 'completed', 'failed', 'busy', 'no-answer', 'cancelled') DEFAULT 'initiated',
    direction ENUM('inbound', 'outbound') DEFAULT 'inbound',
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    transcript LONGTEXT NULL,
    summary TEXT NULL,
    metadata JSON NULL,
    webhook_data JSON NULL,
    cost DECIMAL(10,4) NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_assistant_created (assistant_id, created_at),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_status_created (status, created_at),
    INDEX idx_start_time (start_time),
    
    FOREIGN KEY (assistant_id) REFERENCES assistants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## API Endpoints

### Webhook Endpoint
```http
POST /api/vapi/webhook
Content-Type: application/json

{
  "message": {
    "type": "end-of-call-report",
    // ... webhook data
  }
}
```

### Call Logs API
```http
# Get call logs with filtering
GET /api/call-logs?page=1&status=completed&start_date=2024-01-01&end_date=2024-12-31

# Get call statistics
GET /api/call-logs/stats?user_id=1&assistant_id=2

# Get specific call log
GET /api/call-logs/{callId}
```

## Configuration

### Environment Variables
No additional environment variables required. Uses existing:
- Database configuration
- Vapi API key
- Authentication system

### Webhook Configuration
Ensure assistants have webhook URLs configured for `end-of-call-report` events.

## Testing

### Process Sample Data
```bash
# Test with sample webhook
php artisan vapi:process-sample sample-vapi-end-call-report-webhook.json
```

### Expected Output:
```
Processing webhook data...
✅ Call log created successfully!

┌─────────────────┬─────────────────────────────────────┐
│ Field           │ Value                               │
├─────────────────┼─────────────────────────────────────┤
│ Call ID         │ dfa9d7e4-c651-4dbf-913a-cce916a31b7e │
│ Assistant ID    │ 1                                   │
│ User ID         │ 1                                   │
│ Status          │ completed                           │
│ Duration        │ 195 seconds                         │
│ Cost            │ 0.6096 USD                          │
│ Phone Number    │ +16825828396                        │
│ Caller Number   │ +16176754444                        │
│ Has Transcript  │ Yes                                 │
│ Has Summary     │ Yes                                 │
└─────────────────┴─────────────────────────────────────┘

📞 Contact Information Extracted:
┌──────────────┬────────────────────┐
│ Field        │ Value              │
├──────────────┼────────────────────┤
│ name         │ Hussein Atlag      │
│ email        │ hussain@gmail.com  │
│ phone        │ 682-582-8396       │
│ inquiry_type │ social media marketing │
└──────────────┴────────────────────┘

📊 Call Quality Metrics:
┌─────────────────────┬─────────────┐
│ Metric              │ Value       │
├─────────────────────┼─────────────┤
│ Success             │ Yes         │
│ Duration (seconds)  │ 195         │
│ Messages Count      │ 20          │
│ Cost (USD)          │ 0.6096      │
│ Has Transcript      │ Yes         │
│ Has Summary         │ Yes         │
│ Has Recording       │ Yes         │
└─────────────────────┴─────────────┘
```

## Error Handling

### Common Issues:

1. **Assistant Not Found**
   - Ensure assistant exists in database
   - Check `vapi_assistant_id` mapping

2. **Invalid Webhook Format**
   - Verify webhook structure
   - Check required fields

3. **Database Errors**
   - Check database connection
   - Verify table structure

### Logging:
All processing is logged with detailed information:
- Webhook events received
- Call log creation/updates
- Contact extraction results
- Error conditions

## Performance Considerations

### Database Optimization:
- Indexes on frequently queried fields
- Efficient pagination for large datasets
- Database-level filtering

### Caching:
- Consider caching analytics results
- Cache assistant data for webhook processing

### Monitoring:
- Monitor webhook processing time
- Track database query performance
- Alert on high error rates

## Security

### Data Protection:
- Sensitive data stored securely
- Webhook validation
- User access controls

### Access Control:
- Users can only access their own call logs
- Admin access for all data
- Webhook endpoint is public but validated

## Future Enhancements

### Planned Features:
1. **Real-time Analytics**: Live dashboard updates
2. **Advanced AI Analysis**: Sentiment analysis, intent detection
3. **Integration**: CRM and ticketing system integration
4. **Automated Actions**: Trigger workflows based on call outcomes
5. **Multi-language Support**: Internationalization for transcripts

### Advanced Analytics:
1. **Call Quality Scoring**: AI-powered quality assessment
2. **Trend Analysis**: Predictive analytics
3. **Performance Optimization**: Automated optimization suggestions
4. **Custom Reports**: User-defined report generation

## Troubleshooting

### Debug Steps:

1. **Check Webhook Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i webhook
   ```

2. **Verify Database**:
   ```bash
   php artisan tinker
   >>> App\Models\CallLog::count()
   ```

3. **Test Webhook Processing**:
   ```bash
   php artisan vapi:process-sample sample-vapi-end-call-report-webhook.json
   ```

4. **Check Assistant Mapping**:
   ```bash
   php artisan tinker
   >>> App\Models\Assistant::pluck('vapi_assistant_id', 'id')
   ```

## Conclusion

The Vapi Call Logs Processing System provides a comprehensive solution for handling webhook data, extracting insights, and generating detailed analytics. It's designed to be scalable, secure, and easy to integrate with existing systems.

The system successfully processes the sample webhook data and provides valuable insights into call performance, contact information, and call quality metrics. 