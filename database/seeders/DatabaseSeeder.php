<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders en orden
        // 1. Primero crear roles y permisos
        $this->call(RolePermissionSeeder::class);

        // 2. Luego crear usuarios admin y de prueba
        $this->call(AdminUserSeeder::class);

        // 3. Crear usuario de prueba básico si es necesario
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );
    }
}
