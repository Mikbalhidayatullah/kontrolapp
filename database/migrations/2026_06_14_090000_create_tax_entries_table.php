<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_entries', function (Blueprint $table): void {
            $table->id();
            $table->date('entry_date');
            $table->string('category');
            $table->string('proof_number');
            $table->text('description');
            $table->string('account_code')->nullable();
            $table->string('account_name');
            $table->string('billing_id')->nullable();
            $table->string('ntpn')->nullable();
            $table->unsignedBigInteger('receipt_amount')->default(0);
            $table->unsignedBigInteger('expense_amount')->default(0);
            $table->bigInteger('balance_amount')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entry_date', 'category']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_entries');
    }
};
