<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

describe('GraphQL Authentication', function () {
    beforeEach(function () {
        // Setup roles and permissions
        setupTestRolesAndPermissions();
    });

    describe('Login Mutation', function () {
        test('user can login with valid credentials', function () {
            // Create a user
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');

            $query = <<<'GRAPHQL'
                mutation Login($email: String!, $password: String!) {
                    login(email: $email, password: $password) {
                        success
                        message
                        user {
                            id
                            name
                            email
                            phone
                            status
                        }
                        token
                        permissions
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'email' => 'test@ambulancia.local',
                'password' => 'password123',
            ]);

            $this->assertGraphQLHasNoErrors($response);
            $this->assertGraphQLDataKeyExists($response, 'login');

            $loginData = $response['data']['login'];
            expect($loginData['success'])->toBeTrue();
            expect($loginData['user']['email'])->toBe('test@ambulancia.local');
            expect($loginData['token'])->not->toBeNull();
            expect($loginData['permissions'])->toBeArray();
        });

        test('user cannot login with invalid email', function () {
            User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $query = <<<'GRAPHQL'
                mutation Login($email: String!, $password: String!) {
                    login(email: $email, password: $password) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'email' => 'nonexistent@ambulancia.local',
                'password' => 'password123',
            ]);

            $this->assertGraphQLHasErrors($response);
            $error = $this->getGraphQLError($response);
            expect($error)->toContain('not found');
        });

        test('user cannot login with invalid password', function () {
            User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $query = <<<'GRAPHQL'
                mutation Login($email: String!, $password: String!) {
                    login(email: $email, password: $password) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'email' => 'test@ambulancia.local',
                'password' => 'wrongpassword',
            ]);

            $this->assertGraphQLHasErrors($response);
        });

        test('inactive user cannot login', function () {
            User::create([
                'name' => 'Inactive User',
                'email' => 'inactive@ambulancia.local',
                'phone' => '+34612345679',
                'password' => Hash::make('password123'),
                'status' => 'inactive',
            ]);

            $query = <<<'GRAPHQL'
                mutation Login($email: String!, $password: String!) {
                    login(email: $email, password: $password) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'email' => 'inactive@ambulancia.local',
                'password' => 'password123',
            ]);

            $this->assertGraphQLHasErrors($response);
            $error = $this->getGraphQLError($response);
            expect($error)->toContain('inactive');
        });
    });

    describe('LoginWhatsApp Mutation', function () {
        test('user can login via WhatsApp with existing phone', function () {
            User::create([
                'name' => 'WhatsApp User',
                'email' => 'whatsapp@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $query = <<<'GRAPHQL'
                mutation LoginWhatsApp($phone: String!) {
                    loginWhatsApp(phone: $phone) {
                        success
                        message
                        user {
                            id
                            phone
                            status
                        }
                        token
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'phone' => '34612345678', // without +
            ]);

            $this->assertGraphQLHasNoErrors($response);
            $data = $response['data']['loginWhatsApp'];
            expect($data['success'])->toBeTrue();
            expect($data['user']['phone'])->toContain('34612345678');
            expect($data['token'])->not->toBeNull();
        });

        test('new user is created on WhatsApp login', function () {
            $query = <<<'GRAPHQL'
                mutation LoginWhatsApp($phone: String!) {
                    loginWhatsApp(phone: $phone) {
                        success
                        message
                        user {
                            id
                            phone
                            status
                        }
                        token
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'phone' => '34699999999',
            ]);

            $this->assertGraphQLHasNoErrors($response);
            $data = $response['data']['loginWhatsApp'];
            expect($data['success'])->toBeTrue();

            // Verify user was created
            $user = User::where('phone', '+34699999999')->first();
            expect($user)->not->toBeNull();
            expect($user->status)->toBe('active');
        });
    });

    describe('Me Query', function () {
        test('authenticated user can get their profile', function () {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');
            $token = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query Me {
                    me {
                        id
                        name
                        email
                        phone
                        status
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $me = $response['data']['me'];
            expect($me['email'])->toBe('test@ambulancia.local');
            expect($me['name'])->toBe('Test User');
        });

        test('unauthenticated user cannot access me query', function () {
            $query = <<<'GRAPHQL'
                query Me {
                    me {
                        id
                        name
                        email
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, []);

            $this->assertGraphQLHasErrors($response);
            $error = $this->getGraphQLError($response);
            expect($error)->toContain('Unauthenticated');
        });

        test('me query includes user permissions', function () {
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@ambulancia.local',
                'phone' => '+34612345680',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('admin');
            $token = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query Me {
                    me {
                        id
                        name
                        permissions
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $me = $response['data']['me'];
            expect($me['permissions'])->toBeArray();
            expect(count($me['permissions']))->toBeGreaterThan(0);
        });
    });

    describe('Logout Mutation', function () {
        test('authenticated user can logout', function () {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $token = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation Logout {
                    logout {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $logout = $response['data']['logout'];
            expect($logout['success'])->toBeTrue();

            // Verify token is invalidated
            $response = $this->graphql(<<<'GRAPHQL'
                query Me {
                    me {
                        id
                    }
                }
            GRAPHQL, [], $token);

            $this->assertGraphQLHasErrors($response);
        });

        test('unauthenticated user cannot logout', function () {
            $query = <<<'GRAPHQL'
                mutation Logout {
                    logout {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, []);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('RefreshToken Mutation', function () {
        test('authenticated user can refresh their token', function () {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');
            $oldToken = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation RefreshToken {
                    refreshToken {
                        success
                        message
                        token
                        permissions
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $oldToken);

            $this->assertGraphQLHasNoErrors($response);
            $refreshed = $response['data']['refreshToken'];
            expect($refreshed['success'])->toBeTrue();
            expect($refreshed['token'])->not->toBeNull();
            expect($refreshed['token'])->not->toBe($oldToken);
            expect($refreshed['permissions'])->toBeArray();
        });

        test('unauthenticated user cannot refresh token', function () {
            $query = <<<'GRAPHQL'
                mutation RefreshToken {
                    refreshToken {
                        success
                        message
                        token
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, []);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('ValidateToken Query', function () {
        test('valid token passes validation', function () {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');
            $token = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query ValidateToken($token: String!) {
                    validateToken(token: $token) {
                        valid
                        message
                        user {
                            id
                            email
                        }
                        permissions
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['token' => $token]);

            $this->assertGraphQLHasNoErrors($response);
            $validation = $response['data']['validateToken'];
            expect($validation['valid'])->toBeTrue();
            expect($validation['user']['email'])->toBe('test@ambulancia.local');
        });

        test('invalid token fails validation', function () {
            $query = <<<'GRAPHQL'
                query ValidateToken($token: String!) {
                    validateToken(token: $token) {
                        valid
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['token' => 'invalid-token-xyz']);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('CreateUser Mutation', function () {
        test('admin can create a new user', function () {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@ambulancia.local',
                'phone' => '+34612345680',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $admin->assignRole('admin');
            $adminToken = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation CreateUser($name: String!, $email: String!, $phone: String!, $password: String!, $role: String!) {
                    createUser(name: $name, email: $email, phone: $phone, password: $password, role: $role) {
                        success
                        message
                        user {
                            id
                            name
                            email
                            phone
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'name' => 'New Paramedic',
                'email' => 'paramedic@ambulancia.local',
                'phone' => '+34612345681',
                'password' => 'newpass123',
                'role' => 'paramedic',
            ], $adminToken);

            $this->assertGraphQLHasNoErrors($response);
            $createUser = $response['data']['createUser'];
            expect($createUser['success'])->toBeTrue();
            expect($createUser['user']['email'])->toBe('paramedic@ambulancia.local');

            // Verify user was created with role
            $newUser = User::where('email', 'paramedic@ambulancia.local')->first();
            expect($newUser)->not->toBeNull();
            expect($newUser->hasRole('paramedic', 'sanctum'))->toBeTrue();
        });

        test('non-admin user cannot create users', function () {
            $paramedic = User::create([
                'name' => 'Paramedic User',
                'email' => 'paramedic@ambulancia.local',
                'phone' => '+34612345681',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $paramedic->assignRole('paramedic');
            $token = $paramedic->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation CreateUser($name: String!, $email: String!, $phone: String!, $password: String!, $role: String!) {
                    createUser(name: $name, email: $email, phone: $phone, password: $password, role: $role) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'name' => 'Another User',
                'email' => 'another@ambulancia.local',
                'phone' => '+34612345682',
                'password' => 'pass123',
                'role' => 'doctor',
            ], $token);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('User Permissions', function () {
        test('paramedic role has correct permissions', function () {
            $user = User::create([
                'name' => 'Paramedic User',
                'email' => 'paramedic@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');
            $token = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query UserPermissions {
                    userPermissions {
                        id
                        name
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $permissions = $response['data']['userPermissions'];

            $permissionNames = array_map(fn($p) => $p['name'], $permissions);

            // Paramedic should have these permissions
            expect(in_array('view-patients', $permissionNames))->toBeTrue();
            expect(in_array('create-patient', $permissionNames))->toBeTrue();
            expect(in_array('view-ambulances', $permissionNames))->toBeTrue();

            // But should not have admin permissions
            expect(in_array('manage-roles', $permissionNames))->toBeFalse();
        });

        test('admin role has all permissions', function () {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@ambulancia.local',
                'phone' => '+34612345680',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $admin->assignRole('admin');
            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query UserPermissions {
                    userPermissions {
                        id
                        name
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $permissions = $response['data']['userPermissions'];

            // Admin should have many permissions
            expect(count($permissions))->toBeGreaterThan(10);
        });
    });

});

// Helper function to setup roles and permissions
function setupTestRolesAndPermissions(): void
{
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
        Permission::firstOrCreate(['name' => $permission]);
    }

    $roles = [
        'admin' => null,
        'paramedic' => ['view-patients', 'create-patient', 'edit-patient', 'view-ambulances', 'view-dispatches'],
        'dispatcher' => ['view-users', 'view-ambulances', 'manage-ambulances', 'view-dispatches', 'create-dispatch', 'update-dispatch', 'view-patients'],
        'hospital' => ['view-patients', 'edit-patient', 'view-reports', 'export-reports'],
        'doctor' => ['view-patients', 'edit-patient', 'view-reports', 'export-reports'],
        'system' => ['create-patient', 'view-ambulances', 'create-dispatch', 'view-dispatches'],
    ];

    foreach ($roles as $roleName => $rolePermissions) {
        $role = Role::firstOrCreate(['name' => $roleName]);

        if ($rolePermissions === null) {
            // Admin gets all permissions
            $role->syncPermissions(Permission::all());
        } else {
            $permissionObjects = Permission::whereIn('name', $rolePermissions)->get();
            $role->syncPermissions($permissionObjects);
        }
    }
}
