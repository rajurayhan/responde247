<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Traits\SafeMigrationTrait;

return new class extends Migration
{
    use SafeMigrationTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->safeAddStringColumn('assistants', 'webhook_url', 500, true, 'phone_number');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->safeDropColumn('assistants', 'webhook_url');
    }
};
