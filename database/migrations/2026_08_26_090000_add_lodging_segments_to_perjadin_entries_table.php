<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('perjadin_entries', 'lodging_segments')) {
                $table->json('lodging_segments')->nullable()->after('lodging_hotel_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('perjadin_entries', 'lodging_segments')) {
                $table->dropColumn('lodging_segments');
            }
        });
    }
};
