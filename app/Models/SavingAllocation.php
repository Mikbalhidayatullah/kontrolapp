<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_month',
        'period_year',
        'source_name',
        'amount',
        'sort_order',
        'is_active',
        'auto_settle_debts',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'auto_settle_debts' => 'boolean',
        ];
    }

    public function debtSettlements(): HasMany
    {
        return $this->hasMany(SavingAllocationDebtSettlement::class);
    }

    public function settledAmount(): int
    {
        return $this->relationLoaded('debtSettlements')
            ? (int) $this->debtSettlements->sum('amount')
            : (int) $this->debtSettlements()->sum('amount');
    }
}
