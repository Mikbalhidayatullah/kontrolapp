<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('perjadin_entries', 'funding_category')) {
                $table->string('funding_category')->nullable()->after('category');
                $table->index('funding_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('perjadin_entries', 'funding_category')) {
                $table->dropIndex(['funding_category']);
                $table->dropColumn('funding_category');
            }
        });
    }
};
