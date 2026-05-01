<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_reductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('source_name');
            $table->unsignedBigInteger('amount');
            $table->date('reduction_date');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['period_year', 'period_month']);
            $table->index(['period_year', 'period_month', 'source_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_reductions');
    }
};
