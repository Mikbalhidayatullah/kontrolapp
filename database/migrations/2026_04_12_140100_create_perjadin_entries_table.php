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
        Schema::create('perjadin_entries', function (Blueprint $table) {
            $table->id();
            $table->date('submission_date');
            $table->string('traveler_name');
            $table->string('destination_city');
            $table->date('departure_date');
            $table->date('return_date');
            $table->string('transport_type');
            $table->text('purpose');
            $table->unsignedBigInteger('budget_amount');
            $table->unsignedBigInteger('verified_amount')->default(0);
            $table->string('status');
            $table->text('verifier_notes')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perjadin_entries');
    }
};
