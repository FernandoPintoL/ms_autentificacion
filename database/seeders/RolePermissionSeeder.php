<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar cache de permisos
        app()['cache']->forget('spatie.permission.cache');

        // ======================================
        // CREAR PERMISOS
        // ======================================

        // Permisos de usuarios
        $permissions = [
            // Usuarios
            'view-users',
            'view-user',
            'create-user',
            'edit-user',
            'delete-user',

            // Ambulancias
            'view-ambulances',
            'manage-ambulances',

            // Despachos
            'view-dispatches',
            'create-dispatch',
            'update-dispatch',

            // Pacientes
            'view-patients',
            'create-patient',
            'edit-patient',

            // Reportes
            'view-reports',
            'export-reports',

            // Configuración
            'manage-roles',
            'manage-permissions',
            'manage-settings',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // ======================================
        // CREAR ROLES
        // ======================================

        // ADMIN - Acceso total
        if (!Role::where('name', 'admin')->exists()) {
            $adminRole = Role::create(['name' => 'admin']);
            $adminRole->syncPermissions(Permission::all());
            $this->command->info('✅ Rol ADMIN creado');
        }

        // PARAMEDIC - Paramédico en terreno
        if (!Role::where('name', 'paramedic')->exists()) {
            $paramedicRole = Role::create(['name' => 'paramedic']);
            $paramedicRole->syncPermissions([
                'view-patients',
                'create-patient',
                'edit-patient',
                'view-ambulances',
                'view-dispatches',
            ]);
            $this->command->info('✅ Rol PARAMEDIC creado');
        }

        // DISPATCHER - Operador de despacho
        if (!Role::where('name', 'dispatcher')->exists()) {
            $dispatcherRole = Role::create(['name' => 'dispatcher']);
            $dispatcherRole->syncPermissions([
                'view-users',
                'view-ambulances',
                'manage-ambulances',
                'view-dispatches',
                'create-dispatch',
                'update-dispatch',
                'view-patients',
            ]);
            $this->command->info('✅ Rol DISPATCHER creado');
        }

        // HOSPITAL - Personal de hospital
        if (!Role::where('name', 'hospital')->exists()) {
            $hospitalRole = Role::create(['name' => 'hospital']);
            $hospitalRole->syncPermissions([
                'view-patients',
                'edit-patient',
                'view-reports',
                'export-reports',
            ]);
            $this->command->info('✅ Rol HOSPITAL creado');
        }

        // DOCTOR - Doctor/Médico
        if (!Role::where('name', 'doctor')->exists()) {
            $doctorRole = Role::create(['name' => 'doctor']);
            $doctorRole->syncPermissions([
                'view-patients',
                'edit-patient',
                'view-reports',
                'export-reports',
            ]);
            $this->command->info('✅ Rol DOCTOR creado');
        }

        // SYSTEM - Sistema automático (n8n, etc)
        if (!Role::where('name', 'system')->exists()) {
            $systemRole = Role::create(['name' => 'system']);
            $systemRole->syncPermissions([
                'create-patient',
                'view-ambulances',
                'create-dispatch',
                'view-dispatches',
            ]);
            $this->command->info('✅ Rol SYSTEM creado');
        }

        $this->command->info('✅ Todos los roles y permisos se han creado correctamente');
    }
}
