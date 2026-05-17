<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAllowanceSbu extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_name',
        'unit_label',
        'outside_city_amount',
        'sofifi_inside_city_over_8_hours_amount',
        'diklat_amount',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'outside_city_amount' => 'integer',
            'sofifi_inside_city_over_8_hours_amount' => 'integer',
            'diklat_amount' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
