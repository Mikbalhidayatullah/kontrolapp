<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('perjadin_entries', 'payment_printed_at')) {
                $table->timestamp('payment_printed_at')->nullable()->after('paid_by');
            }

            if (! Schema::hasColumn('perjadin_entries', 'payment_printed_by')) {
                $table->foreignId('payment_printed_by')->nullable()->after('payment_printed_at')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('perjadin_entries', 'payment_completed_at') && Schema::hasColumn('perjadin_entries', 'payment_printed_at')) {
            DB::table('perjadin_entries')
                ->whereNull('payment_printed_at')
                ->whereNotNull('payment_completed_at')
                ->update([
                    'payment_printed_at' => DB::raw('payment_completed_at'),
                    'payment_printed_by' => DB::raw('payment_completed_by'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('perjadin_entries', 'payment_printed_by')) {
                $table->dropConstrainedForeignId('payment_printed_by');
            }

            if (Schema::hasColumn('perjadin_entries', 'payment_printed_at')) {
                $table->dropColumn('payment_printed_at');
            }
        });
    }
};
