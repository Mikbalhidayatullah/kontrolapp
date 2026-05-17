<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->string('report_file_path')->nullable()->after('receipt_file_original_name');
            $table->string('report_file_original_name')->nullable()->after('report_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('perjadin_entries', function (Blueprint $table) {
            $table->dropColumn(['report_file_path', 'report_file_original_name']);
        });
    }
};
