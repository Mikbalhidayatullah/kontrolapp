<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerjadinEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_date',
        'traveler_name',
        'destination_city',
        'departure_date',
        'return_date',
        'transport_type',
        'purpose',
        'budget_amount',
        'verified_amount',
        'status',
        'verifier_notes',
        'proof_path',
        'proof_original_name',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'departure_date' => 'date',
            'return_date' => 'date',
            'budget_amount' => 'integer',
            'verified_amount' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
