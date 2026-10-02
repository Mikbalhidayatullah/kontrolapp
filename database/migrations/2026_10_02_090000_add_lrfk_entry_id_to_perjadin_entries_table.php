<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('perjadin_entries', 'lrfk_entry_id')) {
                $table->foreignId('lrfk_entry_id')
                    ->nullable()
                    ->after('funding_category')
                    ->constrained('lrfk_entries')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('perjadin_entries', 'lrfk_entry_id')) {
                $table->dropConstrainedForeignId('lrfk_entry_id');
            }
        });
    }
};
