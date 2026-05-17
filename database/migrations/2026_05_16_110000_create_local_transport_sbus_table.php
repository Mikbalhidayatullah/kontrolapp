<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_transport_sbus', function (Blueprint $table) {
            $table->id();
            $table->string('component_key', 50);
            $table->string('route_name');
            $table->unsignedBigInteger('amount')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_transport_sbus');
    }
};
