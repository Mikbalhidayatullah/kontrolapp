<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NationalLodgingSbu extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_name',
        'unit_label',
        'head_region_amount',
        'member_eselon_2_amount',
        'eselon_3_gol_4_amount',
        'eselon_4_gol_3_2_1_amount',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'head_region_amount' => 'integer',
            'member_eselon_2_amount' => 'integer',
            'eselon_3_gol_4_amount' => 'integer',
            'eselon_4_gol_3_2_1_amount' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
