<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_ticket_sbus', function (Blueprint $table) {
            $table->id();
            $table->string('origin_city', 120);
            $table->string('destination_city', 120);
            $table->unsignedBigInteger('business_amount')->nullable();
            $table->unsignedBigInteger('economy_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['origin_city', 'destination_city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_ticket_sbus');
    }
};
