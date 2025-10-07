<?php

namespace App\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait SafeMigrationTrait
{
    /**
     * Safely add a column if it doesn't exist
     */
    protected function safeAddColumn(string $table, string $column, callable $callback): void
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, $callback);
        }
    }

    /**
     * Safely drop a column if it exists
     */
    protected function safeDropColumn(string $table, string $column): void
    {
        if (Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($column) {
                $tableBlueprint->dropColumn($column);
            });
        }
    }

    /**
     * Safely add an enum column if it doesn't exist
     */
    protected function safeAddEnumColumn(string $table, string $column, array $values, string $default = null, string $after = null): void
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($column, $values, $default, $after) {
                $enumColumn = $tableBlueprint->enum($column, $values);
                
                if ($default) {
                    $enumColumn->default($default);
                }
                
                if ($after) {
                    $enumColumn->after($after);
                }
            });
        }
    }

    /**
     * Safely add a string column if it doesn't exist
     */
    protected function safeAddStringColumn(string $table, string $column, int $length = null, bool $nullable = false, string $after = null): void
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($column, $length, $nullable, $after) {
                $stringColumn = $tableBlueprint->string($column, $length);
                
                if ($nullable) {
                    $stringColumn->nullable();
                }
                
                if ($after) {
                    $stringColumn->after($after);
                }
            });
        }
    }
} 