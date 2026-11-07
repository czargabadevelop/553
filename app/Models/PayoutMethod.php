<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutMethod extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'type',
        'required_fields',
        'is_active',
        'min_amount_minor',
        'max_amount_minor',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
            'is_active' => 'boolean',
            'min_amount_minor' => 'integer',
            'max_amount_minor' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
