<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepresentationSbu extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_group',
        'unit_label',
        'outside_city_amount',
        'inside_city_over_8_hours_amount',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'outside_city_amount' => 'integer',
            'inside_city_over_8_hours_amount' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
