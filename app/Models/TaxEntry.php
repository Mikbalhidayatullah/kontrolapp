<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_date',
        'category',
        'proof_number',
        'description',
        'account_code',
        'account_name',
        'billing_id',
        'ntpn',
        'receipt_amount',
        'expense_amount',
        'balance_amount',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'receipt_amount' => 'integer',
            'expense_amount' => 'integer',
            'balance_amount' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
