<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('perjadin_entries', 'daily_allowance_mode')) {
                $table->string('daily_allowance_mode', 20)->default('sbu')->after('daily_allowance_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('perjadin_entries', 'daily_allowance_mode')) {
                $table->dropColumn('daily_allowance_mode');
            }
        });
    }
};
