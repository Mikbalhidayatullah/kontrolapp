<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->boolean('lodging_has_receipt')->default(true)->after('lodging_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->dropColumn('lodging_has_receipt');
        });
    }
};
