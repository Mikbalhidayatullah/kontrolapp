<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingReduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_month',
        'period_year',
        'source_name',
        'amount',
        'reduction_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'amount' => 'integer',
            'reduction_date' => 'date',
        ];
    }
}
