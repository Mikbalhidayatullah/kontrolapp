<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlEntrySettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'saving_inflow_entry_id',
        'debt_entry_id',
        'amount',
        'settlement_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'settlement_date' => 'date',
        ];
    }

    public function savingInflow(): BelongsTo
    {
        return $this->belongsTo(ControlEntry::class, 'saving_inflow_entry_id');
    }

    public function debtEntry(): BelongsTo
    {
        return $this->belongsTo(ControlEntry::class, 'debt_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
