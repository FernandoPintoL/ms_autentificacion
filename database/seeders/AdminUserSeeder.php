<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating admin user...');

        try {
            // Verificar si el admin ya existe
            if (User::where('email', 'admin@ambulancia.local')->exists()) {
                $this->command->warn('Admin user already exists');
                return;
            }

            // Crear usuario admin
            $admin = User::create([
                'name'     => 'Administrator',
                'email'    => 'admin@ambulancia.local',
                'phone'    => '+34600000000',
                'password' => Hash::make('admin123456'), // ⚠️ CAMBIAR EN PRODUCCIÓN
                'status'   => 'active',
            ]);

            // Asignar rol admin
            $admin->assignRole('admin');

            $this->command->info('✅ Admin user created successfully');
            $this->command->line('');
            $this->command->info('Admin Credentials:');
            $this->command->line('  Email: admin@ambulancia.local');
            $this->command->line('  Password: admin123456');
            $this->command->warn('⚠️  CHANGE PASSWORD IN PRODUCTION!');
            $this->command->line('');

            // Crear usuarios de prueba para cada rol
            $this->createTestUsers();

        } catch (\Exception $e) {
            $this->command->error('Error creating admin user: ' . $e->getMessage());
            $this->command->line('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Crear usuarios de prueba para cada rol
     */
    private function createTestUsers(): void
    {
        $this->command->info('Creating test users...');

        $testUsers = [
            [
                'name'     => 'Paramédico Test',
                'email'    => 'paramedic@ambulancia.local',
                'phone'    => '+34612345678',
                'password' => 'paramedic123456',
                'role'     => 'paramedic',
            ],
            [
                'name'     => 'Operador Despacho Test',
                'email'    => 'dispatcher@ambulancia.local',
                'phone'    => '+34612345679',
                'password' => 'dispatcher123456',
                'role'     => 'dispatcher',
            ],
            [
                'name'     => 'Hospital Test',
                'email'    => 'hospital@ambulancia.local',
                'phone'    => '+34612345680',
                'password' => 'hospital123456',
                'role'     => 'hospital',
            ],
            [
                'name'     => 'Doctor Test',
                'email'    => 'doctor@ambulancia.local',
                'phone'    => '+34612345681',
                'password' => 'doctor123456',
                'role'     => 'doctor',
            ],
            [
                'name'     => 'System n8n',
                'email'    => 'system@ambulancia.local',
                'phone'    => '+34612345682',
                'password' => null, // Contraseña aleatoria para sistema
                'role'     => 'system',
            ],
        ];

        foreach ($testUsers as $userData) {
            try {
                if (User::where('email', $userData['email'])->exists()) {
                    $this->command->warn("User {$userData['email']} already exists");
                    continue;
                }

                // Para el usuario system, usar contraseña aleatoria
                $password = isset($userData['password']) && $userData['password'] ? $userData['password'] : Str::random(32);

                $user = User::create([
                    'name'     => $userData['name'],
                    'email'    => $userData['email'],
                    'phone'    => $userData['phone'],
                    'password' => Hash::make($password),
                    'status'   => 'active',
                ]);

                // Asignar rol
                $user->assignRole($userData['role']);

                $this->command->info("  ✓ {$userData['name']} ({$userData['role']})");
            } catch (\Exception $e) {
                $this->command->warn("  ✗ Could not create {$userData['email']}: " . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info('✅ Test User Credentials:');
        $this->command->line('  Paramedic: paramedic@ambulancia.local / paramedic123456');
        $this->command->line('  Dispatcher: dispatcher@ambulancia.local / dispatcher123456');
        $this->command->line('  Hospital: hospital@ambulancia.local / hospital123456');
        $this->command->line('  Doctor: doctor@ambulancia.local / doctor123456');
        $this->command->line('  System: system@ambulancia.local / (random password)');
    }
}
