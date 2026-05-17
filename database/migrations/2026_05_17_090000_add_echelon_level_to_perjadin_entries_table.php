<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->string('echelon_level', 10)->nullable()->after('position_name');
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->dropColumn('echelon_level');
        });
    }
};
