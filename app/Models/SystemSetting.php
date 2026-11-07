<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['key', 'value', 'type', 'group', 'description', 'is_sensitive', 'updated_by'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_sensitive' => 'boolean',
        ];
    }
}
