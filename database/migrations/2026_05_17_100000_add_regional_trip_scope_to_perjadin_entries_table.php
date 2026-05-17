<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            $table->string('regional_trip_scope')->nullable()->after('destination_city');
            $table->boolean('sofifi_over_8_hours')->default(false)->after('regional_trip_scope');
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            $table->dropColumn(['regional_trip_scope', 'sofifi_over_8_hours']);
        });
    }
};
