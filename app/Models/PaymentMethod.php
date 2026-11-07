<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'type',
        'instructions',
        'requires_proof',
        'is_active',
        'min_amount_minor',
        'max_amount_minor',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requires_proof' => 'boolean',
            'is_active' => 'boolean',
            'min_amount_minor' => 'integer',
            'max_amount_minor' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
