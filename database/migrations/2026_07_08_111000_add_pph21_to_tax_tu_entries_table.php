<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_tu_entries', function (Blueprint $table): void {
            $table->unsignedBigInteger('pph21_amount')->default(0)->after('ppn_ntpn');
            $table->string('pph21_billing_id')->nullable()->after('pph21_amount');
            $table->string('pph21_ntpn')->nullable()->after('pph21_billing_id');
        });
    }

    public function down(): void
    {
        Schema::table('tax_tu_entries', function (Blueprint $table): void {
            $table->dropColumn([
                'pph21_amount',
                'pph21_billing_id',
                'pph21_ntpn',
            ]);
        });
    }
};
