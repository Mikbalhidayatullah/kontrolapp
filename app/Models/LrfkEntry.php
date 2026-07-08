<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LrfkEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort_order',
        'level',
        'kode',
        'kode_rekening',
        'program_kegiatan',
        'pagu_anggaran',
        'contract_value',
        'contract_number_date',
        'implementer',
        'output',
        'volume',
        'unit',
        'financial_realization',
        'financial_percent',
        'physical_percent',
        'location',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'pagu_anggaran' => 'integer',
            'contract_value' => 'integer',
            'financial_realization' => 'integer',
            'financial_percent' => 'decimal:2',
            'physical_percent' => 'decimal:2',
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
