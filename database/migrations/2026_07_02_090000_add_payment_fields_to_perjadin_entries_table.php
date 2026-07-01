<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('perjadin_entries', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('grand_total');
            }

            if (! Schema::hasColumn('perjadin_entries', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            if (Schema::hasColumn('perjadin_entries', 'paid_by')) {
                $table->dropConstrainedForeignId('paid_by');
            }

            if (Schema::hasColumn('perjadin_entries', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });
    }
};
