<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTuEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'kode_kegiatan',
        'nama_belanja',
        'sp2d_number',
        'sp2d_date',
        'pagu_amount',
        'requested_amount',
        'realization_1_amount',
        'realization_1_date',
        'realization_2_amount',
        'realization_2_date',
        'realization_3_amount',
        'realization_3_date',
        'realization_4_amount',
        'realization_4_date',
        'deposit_letter_number',
        'deposit_amount',
        'deposit_date',
        'ppn_amount',
        'ppn_billing_id',
        'ppn_ntpn',
        'pph21_amount',
        'pph21_billing_id',
        'pph21_ntpn',
        'pph22_amount',
        'pph22_billing_id',
        'pph22_ntpn',
        'pph23_amount',
        'pph23_billing_id',
        'pph23_ntpn',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sp2d_date' => 'date',
            'pagu_amount' => 'integer',
            'requested_amount' => 'integer',
            'realization_1_amount' => 'integer',
            'realization_1_date' => 'date',
            'realization_2_amount' => 'integer',
            'realization_2_date' => 'date',
            'realization_3_amount' => 'integer',
            'realization_3_date' => 'date',
            'realization_4_amount' => 'integer',
            'realization_4_date' => 'date',
            'deposit_amount' => 'integer',
            'deposit_date' => 'date',
            'ppn_amount' => 'integer',
            'pph21_amount' => 'integer',
            'pph22_amount' => 'integer',
            'pph23_amount' => 'integer',
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

    public function totalRealization(): int
    {
        return (int) $this->realization_1_amount
            + (int) $this->realization_2_amount
            + (int) $this->realization_3_amount
            + (int) $this->realization_4_amount;
    }

    public function tuBalance(): int
    {
        return (int) $this->requested_amount - $this->totalRealization();
    }

    public function depositBalance(): int
    {
        return $this->tuBalance() - (int) $this->deposit_amount;
    }
}
