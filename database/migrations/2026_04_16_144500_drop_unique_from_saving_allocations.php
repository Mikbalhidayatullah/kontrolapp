<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saving_allocations', function (Blueprint $table) {
            $table->dropUnique('saving_allocations_period_month_period_year_source_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_allocations', function (Blueprint $table) {
            $table->unique(['period_month', 'period_year', 'source_name']);
        });
    }
};
