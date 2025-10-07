# Safe Migration Pattern

This document explains the safe migration pattern used in this project to prevent errors when running migrations multiple times or in different environments.

## Problem

When running migrations multiple times or in different environments, you might encounter errors like:

```
SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'users' already exists
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'email'
```

## Solution

We use a `SafeMigrationTrait` that provides helper methods to safely add or remove columns and tables.

## Usage

### 1. Using the SafeMigrationTrait

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\SafeMigrationTrait;

return new class extends Migration
{
    use SafeMigrationTrait;

    public function up(): void
    {
        // Safely add an enum column
        $this->safeAddEnumColumn('assistants', 'type', ['demo', 'production'], 'demo', 'vapi_assistant_id');
        
        // Safely add a string column
        $this->safeAddStringColumn('assistants', 'phone_number', null, true, 'vapi_assistant_id');
    }

    public function down(): void
    {
        // Safely drop columns
        $this->safeDropColumn('assistants', 'type');
        $this->safeDropColumn('assistants', 'phone_number');
    }
};
```

### 2. Available Methods

#### `safeAddColumn(string $table, string $column, callable $callback)`
Safely add a column if it doesn't exist.

```php
$this->safeAddColumn('users', 'phone', function (Blueprint $table) {
    $table->string('phone')->nullable();
});
```

#### `safeDropColumn(string $table, string $column)`
Safely drop a column if it exists.

```php
$this->safeDropColumn('users', 'phone');
```

#### `safeAddEnumColumn(string $table, string $column, array $values, string $default = null, string $after = null)`
Safely add an enum column.

```php
$this->safeAddEnumColumn('assistants', 'type', ['demo', 'production'], 'demo', 'vapi_assistant_id');
```

#### `safeAddStringColumn(string $table, string $column, int $length = null, bool $nullable = false, string $after = null)`
Safely add a string column.

```php
$this->safeAddStringColumn('assistants', 'phone_number', null, true, 'vapi_assistant_id');
```

### 3. Manual Safety Checks

If you prefer manual checks, you can use the `Schema::hasColumn()` method:

```php
public function up(): void
{
    Schema::table('assistants', function (Blueprint $table) {
        if (!Schema::hasColumn('assistants', 'type')) {
            $table->enum('type', ['demo', 'production'])->default('demo');
        }
    });
}

public function down(): void
{
    Schema::table('assistants', function (Blueprint $table) {
        if (Schema::hasColumn('assistants', 'type')) {
            $table->dropColumn('type');
        }
    });
}
```

## Benefits

1. **Idempotent**: Can be run multiple times without errors
2. **Environment Safe**: Works across different environments (dev, staging, production)
3. **Team Friendly**: Multiple developers can run migrations without conflicts
4. **CI/CD Safe**: Automated deployments won't fail due to migration conflicts

## Best Practices

### 1. Always Check Before Adding
```php
// ✅ Good
if (!Schema::hasColumn('users', 'phone')) {
    $table->string('phone');
}

// ❌ Bad
$table->string('phone'); // May fail if column exists
```

### 2. Always Check Before Dropping
```php
// ✅ Good
if (Schema::hasColumn('users', 'phone')) {
    $table->dropColumn('phone');
}

// ❌ Bad
$table->dropColumn('phone'); // May fail if column doesn't exist
```

### 3. Use the Trait for Common Operations
```php
// ✅ Good - Uses the trait
$this->safeAddStringColumn('users', 'phone', null, true);

// ✅ Also Good - Manual check
if (!Schema::hasColumn('users', 'phone')) {
    $table->string('phone')->nullable();
}
```

## Testing Safe Migrations

You can test that your migrations are safe by running them multiple times:

```bash
# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback --step=1

# Run again (should not fail)
php artisan migrate

# Run again (should not fail)
php artisan migrate
```

## Migration Examples

### Adding a New Column
```php
<?php

use Illuminate\Database\Migrations\Migration;
use App\Traits\SafeMigrationTrait;

return new class extends Migration
{
    use SafeMigrationTrait;

    public function up(): void
    {
        $this->safeAddStringColumn('users', 'phone', null, true, 'email');
    }

    public function down(): void
    {
        $this->safeDropColumn('users', 'phone');
    }
};
```

### Adding Multiple Columns
```php
<?php

use Illuminate\Database\Migrations\Migration;
use App\Traits\SafeMigrationTrait;

return new class extends Migration
{
    use SafeMigrationTrait;

    public function up(): void
    {
        $this->safeAddStringColumn('users', 'phone', null, true, 'email');
        $this->safeAddEnumColumn('users', 'status', ['active', 'inactive'], 'active', 'phone');
        $this->safeAddColumn('users', 'settings', function (Blueprint $table) {
            $table->json('settings')->nullable();
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('users', 'phone');
        $this->safeDropColumn('users', 'status');
        $this->safeDropColumn('users', 'settings');
    }
};
```

## Troubleshooting

### Migration Still Fails
If a migration still fails despite using safe methods:

1. Check if the column name is correct
2. Verify the table name exists
3. Check for typos in the migration
4. Ensure the trait is properly imported

### Column Not Added
If a column is not added when expected:

1. Check if the column already exists: `Schema::hasColumn('table', 'column')`
2. Verify the migration ran successfully
3. Check the database directly to confirm the column exists

### Rollback Issues
If rollback fails:

1. Ensure the `down()` method uses safe checks
2. Verify the column exists before trying to drop it
3. Check for foreign key constraints that might prevent dropping 