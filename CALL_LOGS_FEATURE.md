# Call Logs Feature Implementation

This document describes the comprehensive call logs feature that has been implemented in the voice agent application.

## Overview

The call logs feature provides a complete system for tracking, storing, and analyzing voice assistant call data. It includes:

- **Call Data Storage**: Complete call information including duration, status, transcripts, and metadata
- **Webhook Integration**: Automatic call logging from Vapi.ai webhook events
- **Analytics Dashboard**: Statistics and insights about call performance
- **Search & Filtering**: Advanced filtering by status, direction, date range, and more
- **Transcript Viewing**: Detailed call transcripts with conversation flow
- **Export Capabilities**: Data export for further analysis

## Features

### 1. Call Data Storage

The system stores comprehensive call information:

- **Call ID**: Unique identifier for each call
- **Assistant Information**: Which assistant handled the call
- **Phone Numbers**: Both assistant and caller numbers
- **Duration**: Call length in seconds
- **Status**: Call status (initiated, ringing, in-progress, completed, failed, etc.)
- **Direction**: Inbound or outbound calls
- **Timing**: Start and end times
- **Transcript**: Full conversation transcript
- **Summary**: AI-generated call summary
- **Metadata**: Additional call data and context
- **Cost**: Call cost and currency
- **Webhook Data**: Raw webhook payload from Vapi

### 2. Webhook Integration

The system automatically receives and processes call events from Vapi.ai:

#### Supported Events:
- **call-start**: When a call begins
- **call-end**: When a call ends
- **call-update**: Real-time call updates

#### Webhook Endpoint:
```
POST /api/vapi/webhook
```

#### Event Processing:
- Creates call log entries automatically
- Updates existing call logs with new information
- Handles missing start events gracefully
- Maps Vapi statuses to internal status constants

### 3. Analytics Dashboard

Comprehensive analytics including:

- **Summary Statistics**: Total calls, completed calls, failed calls, average duration
- **Direction Breakdown**: Inbound vs outbound calls
- **Cost Analysis**: Total and average call costs
- **Status Distribution**: Breakdown by call status
- **Daily Volume**: Call volume over time
- **Assistant Performance**: Top performing assistants

### 4. Search & Filtering

Advanced filtering capabilities:

- **Search**: Search by call ID, transcript content, or summary
- **Status Filter**: Filter by call status
- **Direction Filter**: Filter by inbound/outbound
- **Date Range**: Filter by start date range
- **Phone Number**: Search by phone numbers
- **Assistant**: Filter by specific assistant

### 5. Transcript Viewing

Detailed transcript display:

- **Conversation Flow**: User and assistant messages
- **Role Indicators**: Clear distinction between user and assistant
- **Timestamps**: When each message occurred
- **Modal View**: Popup transcript viewer
- **JSON/Text Support**: Handles both structured and plain text transcripts

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
    INDEX idx_direction_created (direction, created_at),
    INDEX idx_start_time (start_time),
    
    FOREIGN KEY (assistant_id) REFERENCES assistants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## API Endpoints

### Call Logs (Protected Routes)

```http
# Get call logs with filtering
GET /api/call-logs?page=1&status=completed&direction=inbound&start_date=2024-01-01&end_date=2024-01-31&search=keyword

# Get call log statistics
GET /api/call-logs/stats?start_date=2024-01-01&end_date=2024-01-31&assistant_id=1

# Get specific call log details
GET /api/call-logs/{callId}

# Get calls for specific assistant
GET /api/call-logs/assistant/{assistantId}/calls?page=1&status=completed
```

### Admin Routes

```http
# Admin: Get all call logs (admin only)
GET /api/admin/call-logs?page=1&status=completed

# Admin: Get call statistics (admin only)
GET /api/admin/call-logs/stats

# Admin: Export call logs (admin only)
GET /api/admin/call-logs/export?start_date=2024-01-01&end_date=2024-01-31

# Admin: Delete call log (admin only)
DELETE /api/admin/call-logs/{callId}
```

### Webhook Route

```http
# Vapi webhook endpoint (no auth required)
POST /api/vapi/webhook
```

## Frontend Components

### 1. CallLogsPage.vue
Main page component with tabs for:
- Call History (list view)
- Analytics (statistics view)

### 2. CallLogsList.vue
List component with:
- Advanced filtering
- Search functionality
- Pagination
- Call log table
- Action buttons

### 3. CallLogsStats.vue
Analytics component with:
- Summary cards
- Status breakdown
- Cost analysis
- Assistant performance

### 4. CallLogDetails.vue
Detailed view component with:
- Call information
- Phone numbers
- Timing details
- Transcript display
- Metadata view

## Usage Examples

### 1. Viewing Call Logs

```javascript
// Load call logs with filters
const response = await axios.get('/api/call-logs', {
  params: {
    page: 1,
    status: 'completed',
    direction: 'inbound',
    start_date: '2024-01-01',
    end_date: '2024-01-31',
    search: 'customer inquiry'
  }
});
```

### 2. Getting Statistics

```javascript
// Load call statistics
const response = await axios.get('/api/call-logs/stats', {
  params: {
    start_date: '2024-01-01',
    end_date: '2024-01-31',
    assistant_id: 1
  }
});
```

### 3. Viewing Call Details

```javascript
// Get specific call log
const response = await axios.get(`/api/call-logs/${callId}`);
const callLog = response.data.data;
```

### 4. Webhook Processing

The system automatically processes webhook events:

```json
{
  "type": "call-end",
  "callId": "call_123456",
  "assistantId": "assistant_789",
  "status": "completed",
  "duration": 180,
  "transcript": "User: Hello\nAssistant: Hi, how can I help you?",
  "summary": "Customer inquired about product pricing"
}
```

## Configuration

### Environment Variables

No additional environment variables are required. The feature uses existing:
- Database configuration
- Vapi API key
- Authentication system

### Webhook Configuration

To enable call logging, ensure assistants have webhook URLs configured:

1. **In Assistant Form**: Add webhook URL in "Messaging Configuration" section
2. **Vapi Integration**: Webhook URL is automatically sent to Vapi.ai
3. **Event Types**: Configure for "end-of-call-report" events

## Testing

### Running Tests

```bash
# Run call logs tests
php artisan test tests/Feature/CallLogTest.php

# Seed test data
php artisan db:seed --class=CallLogSeeder
```

### Sample Data

The seeder creates:
- 50 random call logs
- 20 completed calls with transcripts
- 10 failed calls
- Various statuses and directions

## Security

### Access Control

- **User Access**: Users can only view call logs for their own assistants
- **Admin Access**: Admins can view all call logs and export data
- **Webhook Security**: Webhook endpoint is public but validates data integrity

### Data Protection

- **Sensitive Data**: Phone numbers and transcripts are stored securely
- **Audit Trail**: All webhook events are logged for debugging
- **Error Handling**: Graceful handling of malformed webhook data

## Performance

### Database Optimization

- **Indexes**: Optimized indexes for common queries
- **Pagination**: Efficient pagination for large datasets
- **Filtering**: Database-level filtering for performance

### Caching

- **Statistics**: Consider caching frequently accessed statistics
- **Assistants**: Cache assistant data for webhook processing

## Monitoring

### Logging

The system logs:
- Webhook events received
- Call log creation/updates
- Error conditions
- Performance metrics

### Alerts

Consider monitoring:
- Webhook delivery failures
- High call failure rates
- Unusual call patterns
- Database performance

## Future Enhancements

### Potential Improvements

1. **Real-time Updates**: WebSocket integration for live call updates
2. **Advanced Analytics**: Machine learning insights
3. **Call Recording**: Audio file storage and playback
4. **Integration**: CRM and ticketing system integration
5. **Reporting**: Automated report generation
6. **Notifications**: Email/SMS alerts for important calls

### Advanced Features

1. **Call Quality Metrics**: Sentiment analysis, satisfaction scores
2. **Automated Actions**: Trigger workflows based on call outcomes
3. **Multi-language Support**: Internationalization for transcripts
4. **API Rate Limiting**: Protect against abuse
5. **Data Retention**: Configurable data retention policies

## Troubleshooting

### Common Issues

1. **Webhook Not Receiving Events**:
   - Verify webhook URL is correct
   - Check Vapi.ai webhook configuration
   - Review server logs for errors

2. **Call Logs Not Appearing**:
   - Check webhook endpoint is accessible
   - Verify assistant exists in database
   - Review webhook processing logs

3. **Performance Issues**:
   - Check database indexes
   - Monitor query performance
   - Consider pagination limits

### Debug Steps

1. **Check Webhook Logs**:
   ```bash
   tail -f storage/logs/laravel.log | grep -i webhook
   ```

2. **Verify Database**:
   ```bash
   php artisan tinker
   >>> App\Models\CallLog::count()
   ```

3. **Test Webhook Endpoint**:
   ```bash
   curl -X POST http://localhost/api/vapi/webhook \
     -H "Content-Type: application/json" \
     -d '{"type":"call-start","callId":"test","assistantId":"test"}'
   ```

## Conclusion

The call logs feature provides a comprehensive solution for tracking and analyzing voice assistant calls. It integrates seamlessly with the existing Vapi.ai infrastructure and provides powerful analytics and management capabilities.

The implementation is production-ready with proper security, performance optimizations, and comprehensive error handling. 