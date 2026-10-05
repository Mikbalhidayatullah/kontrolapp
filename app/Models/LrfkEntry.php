<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LrfkEntry extends Model
{
    use HasFactory;

    public const DATASET_LAMA = 'lama';

    public const DATASET_PERUBAHAN = 'perubahan';

    public const DATASET_DATA_OLAHAN = 'data_olahan';

    protected $fillable = [
        'parent_id',
        'dataset_version',
        'sort_order',
        'source_row',
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
        'budget_balance',
        'cash_plan_october',
        'cash_plan_november',
        'cash_plan_december',
        'cash_plan_quarter',
        'location',
        'notes',
        'variance',
        'variance_note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'source_row' => 'integer',
            'pagu_anggaran' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function perjadinEntries(): HasMany
    {
        return $this->hasMany(PerjadinEntry::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(LrfkEntryDetail::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
