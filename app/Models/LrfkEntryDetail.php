<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LrfkEntryDetail extends Model
{
    protected $fillable = [
        'source_row',
        'sort_order',
        'contract_value',
        'contract_number_date',
        'implementer',
        'output',
        'volume',
        'unit',
        'financial_realization',
        'financial_percent',
        'physical_percent',
        'budget_balance',
        'cash_plan_october',
        'cash_plan_november',
        'cash_plan_december',
        'cash_plan_quarter',
        'location',
        'notes',
        'variance',
        'variance_note',
    ];

    protected function casts(): array
    {
        return [
            'source_row' => 'integer',
            'sort_order' => 'integer',
            'contract_value' => 'integer',
            'financial_realization' => 'integer',
            'financial_percent' => 'decimal:8',
            'physical_percent' => 'decimal:8',
            'budget_balance' => 'integer',
            'cash_plan_october' => 'integer',
            'cash_plan_november' => 'integer',
            'cash_plan_december' => 'integer',
            'cash_plan_quarter' => 'integer',
            'variance' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(LrfkEntry::class, 'lrfk_entry_id');
    }
}
