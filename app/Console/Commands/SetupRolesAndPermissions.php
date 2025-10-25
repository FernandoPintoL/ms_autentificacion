<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class SetupRolesAndPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup roles and permissions for the authentication system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting setup of roles and permissions...');

        try {
            // Limpiar cache
            app()['cache']->forget('spatie.permission.cache');

            // Crear permisos
            $this->createPermissions();

            // Crear roles
            $this->createRoles();

            $this->info('✅ Setup completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Crear permisos
     */
    private function createPermissions()
    {
        $this->info('Creating permissions...');

        $permissions = [
            'view-users',
            'view-user',
            'create-user',
            'edit-user',
            'delete-user',
            'view-ambulances',
            'manage-ambulances',
            'view-dispatches',
            'create-dispatch',
            'update-dispatch',
            'view-patients',
            'create-patient',
            'edit-patient',
            'view-reports',
            'export-reports',
            'manage-roles',
            'manage-permissions',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            try {
                // Verificar si ya existe
                $exists = DB::table('permissions')->where('name', $permission)->exists();
                if (!$exists) {
                    // SQL Server ODBC workaround: usar insert raw sin timestamps
                    DB::statement(
                        "INSERT INTO permissions (name, guard_name) VALUES (?, ?)",
                        [$permission, 'sanctum']
                    );
                }
                $this->line("  ✓ Permission: {$permission}");
            } catch (\Exception $e) {
                $this->warn("  ⚠ Could not create permission {$permission}: " . $e->getMessage());
            }
        }
    }

    /**
     * Crear roles
     */
    private function createRoles()
    {
        $this->info('Creating roles...');

        $roles = [
            ['name' => 'admin', 'permissions' => null], // null = all permissions
            ['name' => 'paramedic', 'permissions' => ['view-patients', 'create-patient', 'edit-patient', 'view-ambulances', 'view-dispatches']],
            ['name' => 'dispatcher', 'permissions' => ['view-users', 'view-ambulances', 'manage-ambulances', 'view-dispatches', 'create-dispatch', 'update-dispatch', 'view-patients']],
            ['name' => 'hospital', 'permissions' => ['view-patients', 'edit-patient', 'view-reports', 'export-reports']],
            ['name' => 'doctor', 'permissions' => ['view-patients', 'edit-patient', 'view-reports', 'export-reports']],
            ['name' => 'system', 'permissions' => ['create-patient', 'view-ambulances', 'create-dispatch', 'view-dispatches']],
        ];

        foreach ($roles as $roleData) {
            try {
                // Verificar si ya existe
                $roleExists = DB::table('roles')->where('name', $roleData['name'])->exists();

                if (!$roleExists) {
                    // SQL Server ODBC workaround: usar insert raw sin timestamps
                    DB::statement(
                        "INSERT INTO roles (name, guard_name) VALUES (?, ?)",
                        [$roleData['name'], 'sanctum']
                    );
                }

                $role = Role::where('name', $roleData['name'])->first();

                // Asignar permisos
                if ($roleData['permissions'] === null) {
                    // Admin get all permissions
                    $permissions = Permission::all();
                    foreach ($permissions as $permission) {
                        $exists = DB::table('role_has_permissions')
                            ->where('role_id', $role->id)
                            ->where('permission_id', $permission->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('role_has_permissions')->insert([
                                'permission_id' => $permission->id,
                                'role_id' => $role->id,
                            ]);
                        }
                    }
                } else {
                    // Asignar permisos específicos
                    foreach ($roleData['permissions'] as $permissionName) {
                        $permission = Permission::where('name', $permissionName)->first();
                        if ($permission) {
                            $exists = DB::table('role_has_permissions')
                                ->where('role_id', $role->id)
                                ->where('permission_id', $permission->id)
                                ->exists();

                            if (!$exists) {
                                DB::table('role_has_permissions')->insert([
                                    'permission_id' => $permission->id,
                                    'role_id' => $role->id,
                                ]);
                            }
                        }
                    }
                }

                $this->line("  ✓ Role: {$roleData['name']}");
            } catch (\Exception $e) {
                $this->warn("  ⚠ Could not create role {$roleData['name']}: " . $e->getMessage());
            }
        }
    }
}
