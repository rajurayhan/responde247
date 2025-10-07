<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            // Use UUID as primary key with default generation
            $table->uuid('id')->primary()->default(DB::raw('(UUID())'));
            $table->string('org_name');
            $table->string('logo_address')->nullable();
            $table->string('company_email')->nullable();
            $table->string('domain')->nullable();
            $table->timestamps();

            $table->index('org_name');
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
