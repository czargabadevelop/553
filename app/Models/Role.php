<?php

namespace App\Models;

use App\Enums\Permission as PermissionSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['slug', 'name', 'description', 'is_system'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function hasPermission(PermissionSlug|string $permission): bool
    {
        $slug = $permission instanceof PermissionSlug ? $permission->value : $permission;

        return $this->permissions->contains('slug', $slug);
    }
}
