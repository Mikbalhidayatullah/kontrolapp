<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightTicketSbu extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_city',
        'destination_city',
        'business_amount',
        'economy_amount',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'business_amount' => 'integer',
            'economy_amount' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
