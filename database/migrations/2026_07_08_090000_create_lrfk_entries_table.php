<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lrfk_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('level');
            $table->string('kode')->nullable();
            $table->string('kode_rekening')->nullable();
            $table->text('program_kegiatan');
            $table->unsignedBigInteger('pagu_anggaran')->default(0);
            $table->unsignedBigInteger('contract_value')->default(0);
            $table->string('contract_number_date')->nullable();
            $table->string('implementer')->nullable();
            $table->text('output')->nullable();
            $table->string('volume')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedBigInteger('financial_realization')->default(0);
            $table->decimal('financial_percent', 8, 2)->default(0);
            $table->decimal('physical_percent', 8, 2)->default(0);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['level', 'sort_order']);
            $table->index('kode_rekening');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lrfk_entries');
    }
};
