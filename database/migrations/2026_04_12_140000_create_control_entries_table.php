<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('control_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('handover_time', 5);
            $table->unsignedBigInteger('amount_out')->default(0);
            $table->unsignedBigInteger('amount_in')->default(0);
            $table->string('third_party')->nullable();
            $table->string('receiving_officer');
            $table->string('appointed_official');
            $table->string('location');
            $table->text('purpose');
            $table->string('fund_source');
            $table->string('status');
            $table->unsignedBigInteger('partial_payment_amount')->default(0);
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_entries');
    }
};
