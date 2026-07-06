<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perjadin_payment_groups', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_number');
            $table->date('assignment_date');
            $table->text('purpose')->nullable();
            $table->timestamps();

            $table->unique(['assignment_number', 'assignment_date'], 'perjadin_payment_groups_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perjadin_payment_groups');
    }
};
