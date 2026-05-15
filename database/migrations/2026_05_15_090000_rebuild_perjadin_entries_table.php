<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('perjadin_entries');

        Schema::create('perjadin_entries', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('skpd_name');
            $table->string('executor_name');
            $table->string('position_name');
            $table->string('grade', 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('assignment_number');
            $table->date('assignment_date');
            $table->string('destination_city');

            $table->boolean('daily_allowance_enabled')->default(false);
            $table->unsignedInteger('daily_allowance_days')->nullable();
            $table->unsignedBigInteger('daily_allowance_rate')->nullable();
            $table->unsignedBigInteger('daily_allowance_total')->default(0);

            $table->boolean('representation_enabled')->default(false);
            $table->unsignedInteger('representation_days')->nullable();
            $table->unsignedBigInteger('representation_rate')->nullable();
            $table->unsignedBigInteger('representation_total')->default(0);

            $table->boolean('ticket_enabled')->default(false);
            $table->string('ticket_transport_type')->nullable();
            $table->date('ticket_departure_date')->nullable();
            $table->date('ticket_return_date')->nullable();
            $table->unsignedBigInteger('ticket_departure_price')->nullable();
            $table->unsignedBigInteger('ticket_return_price')->nullable();
            $table->unsignedBigInteger('ticket_total')->default(0);
            $table->string('ticket_departure_operator')->nullable();
            $table->string('ticket_return_operator')->nullable();
            $table->string('ticket_departure_number')->nullable();
            $table->string('ticket_return_number')->nullable();
            $table->string('ticket_departure_booking_code')->nullable();
            $table->string('ticket_return_booking_code')->nullable();

            $table->boolean('lodging_enabled')->default(false);
            $table->unsignedInteger('lodging_nights')->nullable();
            $table->unsignedBigInteger('lodging_rate')->nullable();
            $table->unsignedBigInteger('lodging_total')->default(0);
            $table->string('lodging_hotel_name')->nullable();

            $table->boolean('local_transport_enabled')->default(false);
            $table->unsignedBigInteger('local_transport_domicile_to_airport')->nullable();
            $table->unsignedBigInteger('local_transport_airport_to_domicile')->nullable();
            $table->unsignedBigInteger('local_transport_airport_to_hotel')->nullable();
            $table->unsignedBigInteger('local_transport_hotel_to_airport')->nullable();
            $table->unsignedBigInteger('local_transport_other')->nullable();
            $table->unsignedBigInteger('local_transport_total')->default(0);

            $table->boolean('other_cost_enabled')->default(false);
            $table->unsignedBigInteger('other_cost_amount')->nullable();
            $table->unsignedBigInteger('grand_total')->default(0);

            $table->string('activity_file_path')->nullable();
            $table->string('activity_file_original_name')->nullable();
            $table->string('receipt_file_path')->nullable();
            $table->string('receipt_file_original_name')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perjadin_entries');

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
};
