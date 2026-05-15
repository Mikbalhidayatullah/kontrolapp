<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('perjadin_entries', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            if (Schema::hasColumn('perjadin_entries', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }
        });
    }
};
