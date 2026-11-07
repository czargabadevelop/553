<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionSlug;
use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(PermissionSlug::cases())->mapWithKeys(function (PermissionSlug $permission) {
            $model = Permission::query()->updateOrCreate(
                ['slug' => $permission->value],
                [
                    'name' => $permission->label(),
                    'group' => $permission->group(),
                ],
            );

            return [$permission->value => $model->id];
        });

        foreach (RoleSlug::cases() as $roleSlug) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $roleSlug->value],
                [
                    'name' => $roleSlug->label(),
                    'description' => $roleSlug->description(),
                    'is_system' => true,
                ],
            );

            $role->permissions()->sync(
                collect($roleSlug->permissions())->map(fn (PermissionSlug $permission) => $permissions[$permission->value])->all()
            );
        }
    }
}
