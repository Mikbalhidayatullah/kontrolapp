<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerjadinEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'skpd_name',
        'executor_name',
        'position_name',
        'grade',
        'start_date',
        'end_date',
        'assignment_number',
        'assignment_date',
        'signature_location',
        'destination_city',
        'daily_allowance_enabled',
        'daily_allowance_days',
        'daily_allowance_rate',
        'daily_allowance_total',
        'representation_enabled',
        'representation_days',
        'representation_rate',
        'representation_total',
        'ticket_enabled',
        'ticket_transport_type',
        'ticket_departure_date',
        'ticket_return_date',
        'ticket_departure_price',
        'ticket_return_price',
        'ticket_total',
        'ticket_departure_operator',
        'ticket_return_operator',
        'ticket_departure_number',
        'ticket_return_number',
        'ticket_departure_booking_code',
        'ticket_return_booking_code',
        'lodging_enabled',
        'lodging_nights',
        'lodging_rate',
        'lodging_total',
        'lodging_hotel_name',
        'local_transport_enabled',
        'local_transport_domicile_to_airport',
        'local_transport_airport_to_domicile',
        'local_transport_airport_to_hotel',
        'local_transport_hotel_to_airport',
        'local_transport_other',
        'local_transport_total',
        'other_cost_enabled',
        'other_cost_amount',
        'grand_total',
        'activity_file_path',
        'activity_file_original_name',
        'receipt_file_path',
        'receipt_file_original_name',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'assignment_date' => 'date',
            'ticket_departure_date' => 'date',
            'ticket_return_date' => 'date',
            'daily_allowance_enabled' => 'boolean',
            'representation_enabled' => 'boolean',
            'ticket_enabled' => 'boolean',
            'lodging_enabled' => 'boolean',
            'local_transport_enabled' => 'boolean',
            'other_cost_enabled' => 'boolean',
            'daily_allowance_rate' => 'integer',
            'daily_allowance_total' => 'integer',
            'representation_rate' => 'integer',
            'representation_total' => 'integer',
            'ticket_departure_price' => 'integer',
            'ticket_return_price' => 'integer',
            'ticket_total' => 'integer',
            'lodging_rate' => 'integer',
            'lodging_total' => 'integer',
            'local_transport_domicile_to_airport' => 'integer',
            'local_transport_airport_to_domicile' => 'integer',
            'local_transport_airport_to_hotel' => 'integer',
            'local_transport_hotel_to_airport' => 'integer',
            'local_transport_other' => 'integer',
            'local_transport_total' => 'integer',
            'other_cost_amount' => 'integer',
            'grand_total' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
