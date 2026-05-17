<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            $table->json('local_transport_segment_ids')->nullable()->after('local_transport_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            $table->dropColumn('local_transport_segment_ids');
        });
    }
};
