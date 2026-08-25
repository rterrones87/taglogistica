<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RolePermissionsFromJsonSeeder extends Seeder
{
    private const FILE_PATH = 'json/role_permissions.json';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $disk = Storage::disk('local');
        $items = json_decode($disk->get(self::FILE_PATH), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($items) {
            //Primero actualizamos la tabla de permisos
            $permissionNames = collect($items)
                ->pluck('permissions')
                ->flatten()
                ->map(fn ($permissionName) => strtolower($permissionName))
                ->unique();

            foreach ($permissionNames as $permissionName) {
                DB::table('permissions')->updateOrInsert(
                    ['name' => $permissionName],
                    [
                        'name' => $permissionName,
                        'updated_at' => now(),
                    ]
                );
            }

            $permissionsByName = Permission::query()
                ->get()
                ->keyBy(fn (Permission $permission) => strtolower($permission->name))
                ->toBase();

            // Ahora actualizamos los roles y los permisos asociados a cada rol
            foreach ($items as $item) {
                if ($item['role_id'] == 1) {
                    continue;
                }

                DB::table('roles')->updateOrInsert(
                    ['id' => $item['role_id']],
                    [
                        'name' => $item['role'],
                        'updated_at' => now(),
                    ]
                );

                $role = Role::find($item['role_id']);
                $rolePermissionNames = collect($item['permissions'])
                    ->map(fn ($permissionName) => strtolower($permissionName))
                    ->all();

                $permissionIds = $permissionsByName
                    ->only($rolePermissionNames)
                    ->pluck('id')
                    ->all();

                $role->permissions()->sync($permissionIds);
            }

            $administrador = Role::find(1);

            if ($administrador) {
                $administrador->permissions()->sync(
                    Permission::query()->pluck('id')->all()
                );
            }
        });

        if ($this->command) {
            $this->command->info(sprintf(
                'Permisos de %d roles sincronizados desde %s.',
                count($items),
                $disk->path(self::FILE_PATH)
            ));
        }
    }
}
