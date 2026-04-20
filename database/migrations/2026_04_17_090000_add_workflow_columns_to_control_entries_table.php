<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('control_entries', function (Blueprint $table) {
            $table->string('transaction_type')->default('operasional_langsung')->after('handover_time');
            $table->unsignedBigInteger('obligation_amount')->default(0)->after('amount_in');
            $table->string('financier_name')->nullable()->after('fund_source');
            $table->boolean('auto_settle_open_debts')->default(false)->after('partial_payment_amount');
        });

        DB::table('control_entries')->update([
            'transaction_type' => DB::raw("
                CASE
                    WHEN status IN ('HUTANG', 'BAYAR SEBAGIAN') THEN 'operasional_talangan'
                    ELSE 'operasional_langsung'
                END
            "),
            'obligation_amount' => DB::raw('amount_out'),
            'financier_name' => DB::raw("
                CASE
                    WHEN status IN ('HUTANG', 'BAYAR SEBAGIAN') THEN third_party
                    ELSE NULL
                END
            "),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('control_entries', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_type',
                'obligation_amount',
                'financier_name',
                'auto_settle_open_debts',
            ]);
        });
    }
};
