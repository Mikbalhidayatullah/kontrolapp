<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representation_sbus', function (Blueprint $table) {
            $table->id();
            $table->string('position_group', 150)->unique();
            $table->string('unit_label', 20)->default('OH');
            $table->unsignedBigInteger('outside_city_amount')->nullable();
            $table->unsignedBigInteger('inside_city_over_8_hours_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representation_sbus');
    }
};
