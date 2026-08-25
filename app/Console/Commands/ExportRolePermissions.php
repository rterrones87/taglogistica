<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:export-permissions
                            {--path=json/role_permissions.json : Ruta relativa dentro de storage/app}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta en JSON todos los roles con sus permisos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = ltrim((string) $this->option('path'), '/\\');

        if ($path === '' || pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
            $this->error('La ruta debe apuntar a un archivo con extensión .json.');

            return self::INVALID;
        }

        $roles = Role::query()
            ->with(['permissions' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('id')
            ->get()
            ->map(function (Role $role) {
                return [
                    'role_id' => $role->getKey(),
                    'role' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->values()->all(),
                ];
            })
            ->values()
            ->all();

        $json = json_encode(
            $roles,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        Storage::disk('local')->put($path, $json.PHP_EOL);

        $this->info(sprintf(
            'Se exportaron %d roles en %s.',
            count($roles),
            Storage::disk('local')->path($path)
        ));

        return self::SUCCESS;
    }
}
