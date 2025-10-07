# Assistant Synchronization Command

The `assistants:sync` command allows you to synchronize assistant data between Vapi.ai and your local database.

## Basic Usage

```bash
# Sync all assistants in the system
php artisan assistants:sync

# Sync with detailed output
php artisan assistants:sync -v
```

## Command Options

### `--user-id=USER_ID`
Sync assistants for a specific user ID.

```bash
# Sync assistants for user ID 1
php artisan assistants:sync --user-id=1
```

### `--assistant-id=ASSISTANT_ID`
Sync a specific assistant by its Vapi ID.

```bash
# Sync a specific assistant
php artisan assistants:sync --assistant-id=7af394ba-09aa-4b08-82d6-d9aa24252b8d
```

### `--force`
Force update existing assistants even if they appear to be up to date.

```bash
# Force update all assistants
php artisan assistants:sync --force
```

### `--dry-run`
Show what would be synced without making any changes to the database.

```bash
# See what would be synced without making changes
php artisan assistants:sync --dry-run
```

### `--sync-missing`
Also sync assistants that exist in Vapi but not in your local database.

```bash
# Sync all assistants including missing ones
php artisan assistants:sync --sync-missing
```

## Advanced Usage Examples

### Preview Changes
```bash
# See what would be synced for all assistants
php artisan assistants:sync --dry-run

# See what would be synced for a specific user
php artisan assistants:sync --user-id=1 --dry-run
```

### Force Update Everything
```bash
# Force update all assistants
php artisan assistants:sync --force

# Force update and sync missing assistants
php artisan assistants:sync --force --sync-missing
```

### Sync Missing Assistants Only
```bash
# Only sync assistants that exist in Vapi but not locally
php artisan assistants:sync --sync-missing --dry-run
```

## What the Command Does

1. **Existing Assistants**: Updates assistant data from Vapi.ai for assistants that already exist in your local database
2. **Missing Assistants**: When using `--sync-missing`, creates new local records for assistants that exist in Vapi but not locally
3. **User Assignment**: Automatically assigns assistants to users based on metadata or falls back to admin users
4. **Data Preservation**: Preserves existing user assignments and creation records when updating

## Output Examples

### Successful Sync
```
Starting assistant synchronization...
Syncing all assistants...
Found 13 assistants in local database
Updated assistant: The Lunch Mob (ID: 7af394ba-09aa-4b08-82d6-d9aa24252b8d)
Assistant Demo Agent 5 - Sandia (ID: 9712f531-bf48-43f4-bf72-591e1e812868) is up to date
Sync completed: 13 synced (2 created, 8 updated), 0 errors
Assistant synchronization completed successfully!
```

### Dry Run
```
Starting assistant synchronization...
Syncing all assistants...
Found 13 assistants in local database
Would update assistant: The Lunch Mob (ID: 7af394ba-09aa-4b08-82d6-d9aa24252b8d)
Would create assistant: New Assistant (ID: abc123-def456)
Sync completed: 13 synced (1 created, 12 updated), 0 errors
```

## Error Handling

The command includes comprehensive error handling:

- **Network Errors**: Logs and continues with other assistants
- **Missing Assistants**: Warns about assistants not found in Vapi
- **User Assignment**: Falls back to admin users if original user not found
- **Data Validation**: Validates assistant data before saving

## Scheduling

You can schedule this command to run automatically using Laravel's task scheduler:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Sync assistants every hour
    $schedule->command('assistants:sync')->hourly();
    
    // Sync with missing assistants daily
    $schedule->command('assistants:sync --sync-missing')->daily();
}
```

## Troubleshooting

### Common Issues

1. **Vapi API Key Not Configured**
   ```
   Error: Vapi API key not configured
   ```
   Solution: Add `VAPI_API_KEY` to your `.env` file

2. **Network Timeout**
   ```
   Error: Failed to sync assistant: cURL error 28
   ```
   Solution: Check your internet connection and Vapi.ai service status

3. **User Not Found**
   ```
   Warning: User assignment failed for assistant
   ```
   Solution: The assistant will be assigned to an admin user as fallback

### Verbose Output
Use `-v`, `-vv`, or `-vvv` for more detailed output:
```bash
php artisan assistants:sync -vv
``` 