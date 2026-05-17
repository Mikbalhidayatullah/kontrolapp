<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lodging_sbus', function (Blueprint $table) {
            $table->id();
            $table->string('region_name', 150)->unique();
            $table->string('unit_label', 20)->default('OH');
            $table->unsignedBigInteger('head_region_amount')->nullable();
            $table->unsignedBigInteger('member_eselon_2_amount')->nullable();
            $table->unsignedBigInteger('eselon_3_gol_4_amount')->nullable();
            $table->unsignedBigInteger('eselon_4_gol_3_2_1_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lodging_sbus');
    }
};
