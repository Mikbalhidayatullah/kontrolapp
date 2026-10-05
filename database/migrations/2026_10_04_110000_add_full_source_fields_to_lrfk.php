<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->unsignedInteger('source_row')->nullable()->after('sort_order');
            $table->bigInteger('budget_balance')->default(0)->after('physical_percent');
            $table->bigInteger('cash_plan_october')->default(0)->after('budget_balance');
            $table->bigInteger('cash_plan_november')->default(0)->after('cash_plan_october');
            $table->bigInteger('cash_plan_december')->default(0)->after('cash_plan_november');
            $table->bigInteger('cash_plan_quarter')->default(0)->after('cash_plan_december');
            $table->bigInteger('variance')->default(0)->after('notes');
            $table->text('variance_note')->nullable()->after('variance');
        });

        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->decimal('financial_percent', 12, 8)->default(0)->change();
            $table->decimal('physical_percent', 12, 8)->default(0)->change();
        });

        Schema::create('lrfk_entry_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lrfk_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('source_row')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('contract_value')->default(0);
            $table->text('contract_number_date')->nullable();
            $table->text('implementer')->nullable();
            $table->text('output')->nullable();
            $table->string('volume')->nullable();
            $table->string('unit')->nullable();
            $table->unsignedBigInteger('financial_realization')->default(0);
            $table->decimal('financial_percent', 12, 8)->default(0);
            $table->decimal('physical_percent', 12, 8)->default(0);
            $table->bigInteger('budget_balance')->default(0);
            $table->bigInteger('cash_plan_october')->default(0);
            $table->bigInteger('cash_plan_november')->default(0);
            $table->bigInteger('cash_plan_december')->default(0);
            $table->bigInteger('cash_plan_quarter')->default(0);
            $table->text('location')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('variance')->default(0);
            $table->text('variance_note')->nullable();
            $table->timestamps();

            $table->index(['lrfk_entry_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lrfk_entry_details');

        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->dropColumn([
                'source_row',
                'budget_balance',
                'cash_plan_october',
                'cash_plan_november',
                'cash_plan_december',
                'cash_plan_quarter',
                'variance',
                'variance_note',
            ]);
        });

        Schema::table('lrfk_entries', function (Blueprint $table): void {
            $table->decimal('financial_percent', 8, 2)->default(0)->change();
            $table->decimal('physical_percent', 8, 2)->default(0)->change();
        });
    }
};
