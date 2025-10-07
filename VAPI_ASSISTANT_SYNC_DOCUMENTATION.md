# Vapi Assistant Synchronization System

## Overview

The Vapi Assistant Synchronization System provides comprehensive synchronization between the local database and Vapi.ai platform. **Vapi.ai is the source of truth** - the system pulls all assistants from Vapi first, then checks if they exist locally. If they exist, they are updated; if not, they are created. This ensures the local database always reflects the current state of assistants in Vapi.

## Features

### ✅ **Comprehensive Data Mapping**
- **73+ Configuration Fields**: Maps all Vapi.ai configuration fields to database columns
- **JSON Configuration Support**: Handles complex configuration objects (model, voice, transcriber, etc.)
- **Metadata Synchronization**: Syncs user assignments, phone numbers, webhook URLs, and type information
- **Vapi-First Synchronization**: Vapi.ai is the source of truth - pulls all assistants first, then updates/creates locally

### ✅ **Smart Update Detection**
- **Field-by-Field Comparison**: Compares all configuration fields to detect changes
- **JSON Value Comparison**: Properly compares complex JSON configuration objects
- **Change Detection**: Only updates when actual changes are detected
- **Force Update Option**: Allows forced updates when needed

### ✅ **Robust Error Handling**
- **Individual Assistant Processing**: Continues processing even if one assistant fails
- **Comprehensive Logging**: Detailed logs for debugging and monitoring
- **Graceful Fallbacks**: Handles missing data and API failures gracefully
- **Validation**: Ensures data integrity during synchronization

## Command Usage

### Basic Synchronization

```bash
# Sync all assistants
php artisan assistants:sync

# Sync with force update (ignores change detection)
php artisan assistants:sync --force

# Dry run (show what would be synced without making changes)
php artisan assistants:sync --dry-run
```

### Targeted Synchronization

```bash
# Sync assistants for a specific user
php artisan assistants:sync --user-id=1

# Sync a specific assistant by Vapi ID
php artisan assistants:sync --assistant-id=cbcedfa4-2b89-4923-ad7e-8ac55fd99282

# Note: --sync-missing option removed as Vapi is now the source of truth
```

### Combined Options

```bash
# Force sync all assistants from Vapi
php artisan assistants:sync --force

# Dry run for specific user
php artisan assistants:sync --user-id=1 --dry-run
```

## Data Mapping

### Core Fields
| Database Field | Vapi Field | Description |
|----------------|------------|-------------|
| `name` | `name` | Assistant name |
| `vapi_assistant_id` | `id` | Unique Vapi identifier |
| `type` | `metadata.type` | demo or production |
| `phone_number` | `metadata.assistant_phone_number` | Assigned phone number |
| `webhook_url` | `metadata.webhook_url` | Webhook endpoint URL |

### Configuration Fields
| Database Field | Vapi Field | Type | Description |
|----------------|------------|------|-------------|
| `transcriber` | `transcriber` | JSON | Speech-to-text configuration |
| `model` | `model` | JSON | AI model configuration |
| `voice` | `voice` | JSON | Voice synthesis configuration |
| `first_message` | `firstMessage` | TEXT | Initial greeting message |
| `first_message_interruptions_enabled` | `firstMessageInterruptionsEnabled` | BOOLEAN | Allow interruptions |
| `first_message_mode` | `firstMessageMode` | STRING | Message delivery mode |
| `voicemail_detection` | `voicemailDetection` | JSON | Voicemail detection settings |
| `client_messages` | `clientMessages` | JSON | Client-side message config |
| `server_messages` | `serverMessages` | JSON | Server-side message config |
| `max_duration_seconds` | `maxDurationSeconds` | INTEGER | Maximum call duration |
| `background_sound` | `backgroundSound` | STRING | Background audio settings |
| `model_output_in_messages_enabled` | `modelOutputInMessagesEnabled` | BOOLEAN | Include model output |

### Advanced Configuration Fields
| Database Field | Vapi Field | Type | Description |
|----------------|------------|------|-------------|
| `transport_configurations` | `transportConfigurations` | JSON | Transport layer settings |
| `observability_plan` | `observabilityPlan` | JSON | Monitoring configuration |
| `credential_ids` | `credentialIds` | JSON | External service credentials |
| `credentials` | `credentials` | JSON | Credential configurations |
| `hooks` | `hooks` | JSON | Webhook configurations |
| `voicemail_message` | `voicemailMessage` | TEXT | Voicemail greeting |
| `end_call_message` | `endCallMessage` | TEXT | Call ending message |
| `end_call_phrases` | `endCallPhrases` | JSON | Call termination phrases |
| `compliance_plan` | `compliancePlan` | JSON | Compliance settings |
| `background_speech_denoising_plan` | `backgroundSpeechDenoisingPlan` | JSON | Audio processing |
| `analysis_plan` | `analysisPlan` | JSON | Call analysis settings |
| `artifact_plan` | `artifactPlan` | JSON | Data collection settings |
| `start_speaking_plan` | `startSpeakingPlan` | JSON | Speech initiation |
| `stop_speaking_plan` | `stopSpeakingPlan` | JSON | Speech termination |
| `monitor_plan` | `monitorPlan` | JSON | Monitoring configuration |
| `keypad_input_plan` | `keypadInputPlan` | JSON | DTMF input handling |

## Synchronization Process

### 1. **Data Retrieval (Vapi-First)**
- **Source of Truth**: Fetches ALL assistants from Vapi.ai API first
- **Complete Dataset**: Retrieves the authoritative list from Vapi
- **Error Handling**: Handles API errors and timeouts gracefully
- **No Fallbacks**: If Vapi is unavailable, sync cannot proceed (by design)

### 2. **Local Existence Check**
- For each Vapi assistant, checks if it exists locally by `vapi_assistant_id`
- **If Exists**: Updates the local assistant with Vapi data
- **If Not Exists**: Creates a new local assistant with Vapi data
- **Complete Coverage**: Ensures all Vapi assistants are represented locally

### 3. **Change Detection**
- Compares local database values with Vapi data
- Performs field-by-field comparison
- Handles JSON object comparison intelligently
- Detects null vs empty value differences

### 4. **Data Mapping**
- Maps Vapi camelCase fields to database snake_case fields
- Handles nested metadata extraction
- Preserves user assignments and ownership
- Maintains data type consistency

### 5. **Database Updates**
- Updates existing assistants with new data from Vapi
- Creates new assistants for assistants that exist in Vapi but not locally
- Preserves foreign key relationships
- Maintains audit trails

### 6. **Logging & Monitoring**
- Logs all synchronization activities
- Records successful updates and errors
- Provides detailed progress information
- Enables troubleshooting and monitoring

## User Assignment Logic

### For Existing Assistants
- Preserves existing `user_id` and `created_by` values
- Only updates configuration data
- Maintains ownership relationships

### For New Assistants
1. **Primary Method**: Uses `metadata.user_email` to find user
2. **Fallback Method**: Assigns to first admin user if no email match
3. **Default Assignment**: Ensures all assistants have valid ownership

## Error Handling

### API Errors
- Continues processing other assistants when one fails
- Logs detailed error information
- Provides meaningful error messages
- Handles network timeouts gracefully

### Data Validation
- Validates required fields before database operations
- Handles malformed JSON data
- Ensures data type consistency
- Prevents database constraint violations

### Database Errors
- Uses database transactions for data integrity
- Handles foreign key constraint violations
- Provides rollback capabilities
- Logs database-specific errors

## Monitoring & Logging

### Log Levels
- **INFO**: Successful synchronization activities
- **WARNING**: Non-critical issues (missing assistants, etc.)
- **ERROR**: Failed operations and critical errors

### Log Data
```php
// Successful update
Log::info('Assistant updated from Vapi sync', [
    'assistant_id' => $assistant->id,
    'vapi_assistant_id' => $vapiAssistant['id'],
    'name' => $vapiAssistant['name'],
    'updated_fields' => array_keys($updateData)
]);

// Successful creation
Log::info('Assistant created from Vapi sync', [
    'assistant_id' => $assistant->id,
    'vapi_assistant_id' => $vapiAssistant['id'],
    'name' => $vapiAssistant['name'],
    'user_id' => $userId,
    'created_fields' => array_keys($createData)
]);
```

## Best Practices

### 1. **Regular Synchronization**
- Run synchronization daily or after major Vapi changes
- Use `--dry-run` first to preview changes
- Monitor logs for any synchronization issues

### 2. **Data Backup**
- Backup database before major synchronization runs
- Test synchronization in development environment first
- Keep audit trails of all synchronization activities

### 3. **Performance Optimization**
- Use targeted synchronization for specific users/assistants
- Run during low-traffic periods
- Monitor API rate limits and usage

### 4. **Troubleshooting**
- Check Vapi API connectivity and authentication
- Verify database permissions and constraints
- Review logs for specific error patterns
- Test with individual assistants first

## Example Usage Scenarios

### Daily Synchronization
```bash
# Daily sync from Vapi (source of truth)
php artisan assistants:sync
```

### After Vapi Configuration Changes
```bash
# Force sync after manual Vapi changes
php artisan assistants:sync --force
```

### User-Specific Updates
```bash
# Sync specific user's assistants
php artisan assistants:sync --user-id=5 --force
```

### Testing and Validation
```bash
# Test sync without making changes
php artisan assistants:sync --dry-run
```

## Integration with Cron Jobs

### Recommended Cron Schedule
```bash
# Daily synchronization at 2 AM
0 2 * * * cd /path/to/project && php artisan assistants:sync >> /var/log/assistant-sync.log 2>&1

# Weekly force sync on Sundays at 3 AM
0 3 * * 0 cd /path/to/project && php artisan assistants:sync --force >> /var/log/assistant-sync.log 2>&1
```

## Troubleshooting

### Common Issues

1. **API Authentication Errors**
   - Verify VAPI_API_KEY in .env file
   - Check API key permissions in Vapi dashboard
   - Ensure API key is not expired

2. **Database Connection Issues**
   - Verify database credentials
   - Check database server availability
   - Ensure proper database permissions

3. **Synchronization Failures**
   - Check individual assistant Vapi IDs
   - Verify assistant exists in Vapi platform
   - Review detailed error logs

4. **Data Inconsistencies**
   - Run force synchronization
   - Check for manual Vapi changes
   - Verify metadata structure

### Debug Commands
```bash
# Check specific assistant in Vapi
php artisan assistants:sync --assistant-id=ASSISTANT_ID --dry-run

# Test user-specific sync
php artisan assistants:sync --user-id=USER_ID --dry-run

# Check all assistants from Vapi
php artisan assistants:sync --dry-run
```

## Security Considerations

- **API Key Protection**: Store Vapi API key securely in environment variables
- **Database Security**: Use proper database permissions and connection security
- **Log Security**: Ensure logs don't contain sensitive information
- **Access Control**: Restrict command execution to authorized users

## Performance Metrics

### Typical Performance
- **Small Dataset** (< 10 assistants): 5-10 seconds
- **Medium Dataset** (10-50 assistants): 30-60 seconds
- **Large Dataset** (50+ assistants): 2-5 minutes

### Optimization Tips
- Use targeted synchronization when possible
- Run during off-peak hours
- Monitor API rate limits
- Consider batch processing for large datasets

---

*This documentation covers the comprehensive Vapi Assistant Synchronization System. For additional support or feature requests, please refer to the development team.*
