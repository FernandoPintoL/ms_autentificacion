<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user and test users for ambulancia system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating admin user...');

        try {
            // Crear admin
            $this->createAdminUser();

            // Crear usuarios de prueba
            $this->createTestUsers();

            $this->info('');
            $this->info('✅ Setup completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Crear usuario admin
     */
    private function createAdminUser()
    {
        $email = 'admin@ambulancia.local';

        // Verificar si existe
        if (User::where('email', $email)->exists()) {
            $this->warn('Admin user already exists');
            return;
        }

        $password = Hash::make('admin@123456');

        // Usar insert raw para evitar problemas con timestamps
        DB::statement(
            "INSERT INTO users (name, email, phone, password, status) VALUES (?, ?, ?, ?, ?)",
            ['Administrator', $email, '+34600000000', $password, 'active']
        );

        $admin = User::where('email', $email)->first();
        // Asignar rol con la guardia correcta
        $admin->assignRole('admin', 'sanctum');

        $this->line('✓ Admin user created');
        $this->info('');
        $this->info('Admin Credentials:');
        $this->line('  Email: admin@ambulancia.local');
        $this->line('  Password: admin@123456');
        $this->warn('⚠️  CHANGE PASSWORD IN PRODUCTION!');
    }

    /**
     * Crear usuarios de prueba
     */
    private function createTestUsers()
    {
        $this->info('Creating test users...');

        $testUsers = [
            [
                'name' => 'Paramédico Test',
                'email' => 'paramedic@ambulancia.local',
                'phone' => '+34612345678',
                'password' => 'paramedic@123456',
                'role' => 'paramedic',
            ],
            [
                'name' => 'Operador Despacho Test',
                'email' => 'dispatcher@ambulancia.local',
                'phone' => '+34612345679',
                'password' => 'dispatcher@123456',
                'role' => 'dispatcher',
            ],
            [
                'name' => 'Hospital Test',
                'email' => 'hospital@ambulancia.local',
                'phone' => '+34612345680',
                'password' => 'hospital@123456',
                'role' => 'hospital',
            ],
            [
                'name' => 'Doctor Test',
                'email' => 'doctor@ambulancia.local',
                'phone' => '+34612345681',
                'password' => 'doctor@123456',
                'role' => 'doctor',
            ],
            [
                'name' => 'System n8n',
                'email' => 'system@ambulancia.local',
                'phone' => '+34612345682',
                'password' => Hash::make(Str::random(32)),
                'role' => 'system',
            ],
        ];

        foreach ($testUsers as $userData) {
            try {
                if (User::where('email', $userData['email'])->exists()) {
                    $this->warn("  ⚠ User {$userData['email']} already exists");
                    continue;
                }

                $password = is_string($userData['password']) && strpos($userData['password'], '$2y$') !== 0
                    ? Hash::make($userData['password'])
                    : $userData['password'];

                DB::statement(
                    "INSERT INTO users (name, email, phone, password, status) VALUES (?, ?, ?, ?, ?)",
                    [$userData['name'], $userData['email'], $userData['phone'], $password, 'active']
                );

                $user = User::where('email', $userData['email'])->first();
                $user->assignRole($userData['role'], 'sanctum');

                $this->line("  ✓ {$userData['name']} ({$userData['role']})");
            } catch (\Exception $e) {
                $this->warn("  ✗ Could not create {$userData['email']}: " . $e->getMessage());
            }
        }

        $this->info('');
        $this->info('Test User Credentials:');
        $this->line('  Paramedic: paramedic@ambulancia.local / paramedic@123456');
        $this->line('  Dispatcher: dispatcher@ambulancia.local / dispatcher@123456');
        $this->line('  Hospital: hospital@ambulancia.local / hospital@123456');
        $this->line('  Doctor: doctor@ambulancia.local / doctor@123456');
    }
}
