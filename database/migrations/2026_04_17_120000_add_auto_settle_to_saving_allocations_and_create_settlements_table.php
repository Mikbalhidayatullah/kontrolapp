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
        Schema::table('saving_allocations', function (Blueprint $table) {
            $table->boolean('auto_settle_debts')->default(false)->after('is_active');
        });

        Schema::create('saving_allocation_debt_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_allocation_id')->constrained('saving_allocations')->cascadeOnDelete();
            $table->foreignId('debt_entry_id')->constrained('control_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_allocation_debt_settlements');

        Schema::table('saving_allocations', function (Blueprint $table) {
            $table->dropColumn('auto_settle_debts');
        });
    }
};
