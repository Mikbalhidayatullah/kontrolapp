<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->string('origin_regency')->nullable()->after('grade');
            $table->string('origin_district')->nullable()->after('origin_regency');
            $table->string('destination_regency')->nullable()->after('origin_district');
            $table->string('destination_district')->nullable()->after('destination_regency');
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->dropColumn([
                'origin_regency',
                'origin_district',
                'destination_regency',
                'destination_district',
            ]);
        });
    }
};
