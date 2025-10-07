<?php

use App\Traits\SafeMigrationTrait;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigrationTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->safeAddEnumColumn('resellers', 'status', ['active', 'inactive'], 'active', 'domain');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->safeDropColumn('resellers', 'status');
    }
};
