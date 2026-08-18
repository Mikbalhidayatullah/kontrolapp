<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('perjadin_entries', 'representation_mode')) {
                $table->string('representation_mode', 20)->default('sbu')->after('representation_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('perjadin_entries', 'representation_mode')) {
                $table->dropColumn('representation_mode');
            }
        });
    }
};
