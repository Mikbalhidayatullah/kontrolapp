<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalTransportSbu extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_key',
        'area_name',
        'row_code',
        'origin_regency',
        'origin_label',
        'destination_regency',
        'destination_label',
        'route_name',
        'unit_label',
        'amount',
        'notes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
