<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class DropAllTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:drop-all-tables {--force : Skip confirmation prompt} {--connection= : Database connection to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables from the database (DANGEROUS - use with caution)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->option('connection') ?: config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        $this->info("Database Driver: {$driver}");
        $this->info("Connection: {$connection}");
        
        // Get all table names
        $tables = $this->getAllTables($connection, $driver);
        
        if (empty($tables)) {
            $this->info('No tables found in the database.');
            return 0;
        }
        
        $this->newLine();
        $this->warn('WARNING: This will permanently delete ALL data in the following tables:');
        $this->table(['Table Name'], array_map(fn($table) => [$table], $tables));
        
        $this->newLine();
        $this->error('THIS ACTION CANNOT BE UNDONE!');
        
        // Confirmation prompt
        if (!$this->option('force')) {
            if (!$this->confirm('Are you absolutely sure you want to drop all tables?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            // Double confirmation for safety
            if (!$this->confirm('This will DELETE ALL DATA. Type "yes" to confirm')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        $this->newLine();
        $this->info('Dropping all tables...');
        
        $droppedCount = 0;
        $errors = [];
        
        try {
            // Disable foreign key checks for MySQL/MariaDB
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0;');
            }
            
            foreach ($tables as $table) {
                try {
                    $this->line("  Dropping table: {$table}");
                    Schema::connection($connection)->dropIfExists($table);
                    $droppedCount++;
                } catch (\Exception $e) {
                    $error = "Failed to drop table {$table}: " . $e->getMessage();
                    $errors[] = $error;
                    $this->error("  Error: {$error}");
                }
            }
            
            // Re-enable foreign key checks for MySQL/MariaDB
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            
        } catch (\Exception $e) {
            $this->error('Critical error during table dropping: ' . $e->getMessage());
            return 1;
        }
        
        $this->newLine();
        
        if ($droppedCount > 0) {
            $this->info("Successfully dropped {$droppedCount} table(s).");
        }
        
        if (!empty($errors)) {
            $this->error('Errors encountered:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        // Ask if user wants to run migrations
        if ($droppedCount > 0 && !$this->option('force')) {
            $this->newLine();
            if ($this->confirm('Would you like to run migrations to recreate the tables?')) {
                $this->info('Running migrations...');
                Artisan::call('migrate', ['--force' => true]);
                $this->info('Migrations completed.');
            }
        }
        
        $this->newLine();
        $this->info('Operation completed.');
        
        return 0;
    }
    
    /**
     * Get all table names from the database
     */
    private function getAllTables(string $connection, string $driver): array
    {
        try {
            switch ($driver) {
                case 'sqlite':
                    $tables = DB::connection($connection)
                        ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    return array_map(fn($table) => $table->name, $tables);
                    
                case 'mysql':
                case 'mariadb':
                    $tables = DB::connection($connection)
                        ->select("SHOW TABLES");
                    $key = 'Tables_in_' . config("database.connections.{$connection}.database");
                    return array_map(fn($table) => $table->$key, $tables);
                    
                case 'pgsql':
                    $tables = DB::connection($connection)
                        ->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                    return array_map(fn($table) => $table->tablename, $tables);
                    
                case 'sqlsrv':
                    $tables = DB::connection($connection)
                        ->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
                    return array_map(fn($table) => $table->TABLE_NAME, $tables);
                    
                default:
                    $this->error("Unsupported database driver: {$driver}");
                    return [];
            }
        } catch (\Exception $e) {
            $this->error("Failed to get table list: " . $e->getMessage());
            return [];
        }
    }
}
