<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_tu_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('category');
            $table->string('kode_kegiatan')->nullable();
            $table->text('nama_belanja');
            $table->string('sp2d_number')->nullable();
            $table->date('sp2d_date')->nullable();
            $table->unsignedBigInteger('pagu_amount')->default(0);
            $table->unsignedBigInteger('requested_amount')->default(0);
            $table->unsignedBigInteger('realization_1_amount')->default(0);
            $table->date('realization_1_date')->nullable();
            $table->unsignedBigInteger('realization_2_amount')->default(0);
            $table->date('realization_2_date')->nullable();
            $table->unsignedBigInteger('realization_3_amount')->default(0);
            $table->date('realization_3_date')->nullable();
            $table->unsignedBigInteger('realization_4_amount')->default(0);
            $table->date('realization_4_date')->nullable();
            $table->string('deposit_letter_number')->nullable();
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->date('deposit_date')->nullable();
            $table->unsignedBigInteger('ppn_amount')->default(0);
            $table->string('ppn_billing_id')->nullable();
            $table->string('ppn_ntpn')->nullable();
            $table->unsignedBigInteger('pph22_amount')->default(0);
            $table->string('pph22_billing_id')->nullable();
            $table->string('pph22_ntpn')->nullable();
            $table->unsignedBigInteger('pph23_amount')->default(0);
            $table->string('pph23_billing_id')->nullable();
            $table->string('pph23_ntpn')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'sp2d_date']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_tu_entries');
    }
};
