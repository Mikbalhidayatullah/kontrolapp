<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingAllocationDebtSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'saving_allocation_id',
        'debt_entry_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function savingAllocation(): BelongsTo
    {
        return $this->belongsTo(SavingAllocation::class);
    }

    public function debtEntry(): BelongsTo
    {
        return $this->belongsTo(ControlEntry::class, 'debt_entry_id');
    }
}
