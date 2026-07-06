<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerjadinPaymentGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_number',
        'assignment_date',
        'purpose',
    ];

    protected function casts(): array
    {
        return [
            'assignment_date' => 'date',
        ];
    }
}
