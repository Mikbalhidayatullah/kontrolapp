<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_date',
        'handover_time',
        'transaction_type',
        'amount_out',
        'amount_in',
        'obligation_amount',
        'third_party',
        'receiving_officer',
        'appointed_official',
        'location',
        'purpose',
        'fund_source',
        'financier_name',
        'status',
        'partial_payment_amount',
        'auto_settle_open_debts',
        'proof_path',
        'proof_original_name',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount_out' => 'integer',
            'amount_in' => 'integer',
            'obligation_amount' => 'integer',
            'partial_payment_amount' => 'integer',
            'auto_settle_open_debts' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function debtSettlements(): HasMany
    {
        return $this->hasMany(ControlEntrySettlement::class, 'debt_entry_id');
    }

    public function savingSettlements(): HasMany
    {
        return $this->hasMany(ControlEntrySettlement::class, 'saving_inflow_entry_id');
    }

    public function savingAllocationSettlements(): HasMany
    {
        return $this->hasMany(SavingAllocationDebtSettlement::class, 'debt_entry_id');
    }

    public function settledAmount(): int
    {
        $legacySettlementsTotal = $this->relationLoaded('debtSettlements')
            ? (int) $this->debtSettlements->sum('amount')
            : (int) $this->debtSettlements()->sum('amount');

        $allocationSettlementsTotal = $this->relationLoaded('savingAllocationSettlements')
            ? (int) $this->savingAllocationSettlements->sum('amount')
            : (int) $this->savingAllocationSettlements()->sum('amount');

        return (int) $this->partial_payment_amount + $legacySettlementsTotal + $allocationSettlementsTotal;
    }

    public function remainingDebt(): int
    {
        return max($this->obligation_amount - $this->settledAmount(), 0);
    }

    public function transactionTypeLabel(): string
    {
        return match ($this->transaction_type) {
            'operasional_langsung' => 'Operasional Langsung',
            'operasional_talangan' => 'Operasional Ditalangi',
            'saving_masuk' => 'Saving Masuk',
            default => ucfirst(str_replace('_', ' ', (string) $this->transaction_type)),
        };
    }
}
