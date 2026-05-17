<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_transport_sbus', function (Blueprint $table) {
            $table->string('area_name')->nullable()->after('component_key');
            $table->string('row_code', 20)->nullable()->after('area_name');
            $table->string('origin_regency')->nullable()->after('row_code');
            $table->string('origin_label')->nullable()->after('origin_regency');
            $table->string('destination_regency')->nullable()->after('origin_label');
            $table->string('destination_label')->nullable()->after('destination_regency');
            $table->string('unit_label')->default('Orang/kali')->after('route_name');
        });
    }

    public function down(): void
    {
        Schema::table('local_transport_sbus', function (Blueprint $table) {
            $table->dropColumn([
                'area_name',
                'row_code',
                'origin_regency',
                'origin_label',
                'destination_regency',
                'destination_label',
                'unit_label',
            ]);
        });
    }
};
