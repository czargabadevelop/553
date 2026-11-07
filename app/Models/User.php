<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Permission as PermissionSlug;
use App\Enums\RoleSlug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'profile_picture',
        'password',
        'role',
        'account_status',
        'suspended_at',
        'suspended_reason',
        'last_login_at',
    ];

    /**
     * Default attribute values mirroring the database defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'player',
        'account_status' => 'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_status' => AccountStatus::class,
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /** @return HasOne<PlayerProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(PlayerProfile::class);
    }

    /** @return HasOne<AccountVerification, $this> */
    public function verification(): HasOne
    {
        return $this->hasOne(AccountVerification::class);
    }

    /** @return HasOne<Wallet, $this> */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /** @return HasMany<Deposit, $this> */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    /** @return HasMany<Withdrawal, $this> */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /** @return HasMany<GamePlayer, $this> */
    public function gamePlayers(): HasMany
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function hasRole(RoleSlug|string $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return $this->role === $slug || $this->roles->contains('slug', $slug);
    }

    public function isSuperAdministrator(): bool
    {
        return $this->hasRole(RoleSlug::SuperAdministrator);
    }

    public function hasPermission(PermissionSlug|string $permission): bool
    {
        if ($this->isSuperAdministrator()) {
            return true;
        }

        $slug = $permission instanceof PermissionSlug ? $permission->value : $permission;

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $slug))
            ->exists();
    }

    public function isSuspended(): bool
    {
        // A freshly created model may not have refreshed the database default yet.
        return $this->account_status !== null && $this->account_status !== AccountStatus::Active;
    }

    public function displayName(): string
    {
        return $this->name ?: (string) $this->username;
    }
}
