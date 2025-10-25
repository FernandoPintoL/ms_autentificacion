<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

describe('GraphQL User Management', function () {
    beforeEach(function () {
        setupManagementTestRolesAndPermissions();
    });

    describe('Get User Query', function () {
        test('authorized user can get another user by id', function () {
            $admin = createTestAdminUser();
            $targetUser = User::create([
                'name' => 'Target User',
                'email' => 'target@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query User($id: ID!) {
                    user(id: $id) {
                        id
                        name
                        email
                        phone
                        status
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['id' => $targetUser->id], $token);

            $this->assertGraphQLHasNoErrors($response);
            $user = $response['data']['user'];
            expect($user['email'])->toBe('target@ambulancia.local');
        });

        test('unauthenticated user cannot get user details', function () {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $query = <<<'GRAPHQL'
                query User($id: ID!) {
                    user(id: $id) {
                        id
                        email
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['id' => $user->id]);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('List Users Query', function () {
        test('admin can list all users with pagination', function () {
            $admin = createTestAdminUser();

            // Create multiple test users
            for ($i = 0; $i < 5; $i++) {
                User::create([
                    'name' => "User {$i}",
                    'email' => "user{$i}@ambulancia.local",
                    'phone' => "+3461234567{$i}",
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                ]);
            }

            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query Users($first: Int!, $page: Int!) {
                    users(first: $first, page: $page) {
                        data {
                            id
                            name
                            email
                        }
                        paginatorInfo {
                            total
                            perPage
                            currentPage
                            lastPage
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['first' => 10, 'page' => 1], $token);

            $this->assertGraphQLHasNoErrors($response);
            $users = $response['data']['users'];
            expect($users['paginatorInfo']['total'])->toBeGreaterThanOrEqual(5);
            expect(count($users['data']))->toBeGreaterThanOrEqual(5);
        });

        test('non-admin user cannot list all users', function () {
            $paramedic = User::create([
                'name' => 'Paramedic User',
                'email' => 'paramedic@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $paramedic->assignRole('paramedic');
            $token = $paramedic->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                query Users($first: Int!, $page: Int!) {
                    users(first: $first, page: $page) {
                        data {
                            id
                            email
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['first' => 10, 'page' => 1], $token);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('UpdateUser Mutation', function () {
        test('user can update their own profile', function () {
            $user = User::create([
                'name' => 'Original Name',
                'email' => 'user@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');
            $token = $user->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation UpdateUser($id: ID!, $name: String!, $phone: String!) {
                    updateUser(id: $id, name: $name, phone: $phone) {
                        success
                        message
                        user {
                            id
                            name
                            phone
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'id' => $user->id,
                'name' => 'Updated Name',
                'phone' => '+34699999999',
            ], $token);

            $this->assertGraphQLHasNoErrors($response);
            $updateData = $response['data']['updateUser'];
            expect($updateData['success'])->toBeTrue();
            expect($updateData['user']['name'])->toBe('Updated Name');

            // Verify in database
            $user->refresh();
            expect($user->name)->toBe('Updated Name');
            expect($user->phone)->toBe('+34699999999');
        });

        test('admin can update any user', function () {
            $admin = createTestAdminUser();
            $targetUser = User::create([
                'name' => 'Target User',
                'email' => 'target@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation UpdateUser($id: ID!, $name: String!, $status: String!) {
                    updateUser(id: $id, name: $name, status: $status) {
                        success
                        message
                        user {
                            name
                            status
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'id' => $targetUser->id,
                'name' => 'Suspended User',
                'status' => 'inactive',
            ], $token);

            $this->assertGraphQLHasNoErrors($response);
            $updateData = $response['data']['updateUser'];
            expect($updateData['user']['status'])->toBe('inactive');
        });
    });

    describe('DeleteUser Mutation', function () {
        test('admin can delete a user', function () {
            $admin = createTestAdminUser();
            $userToDelete = User::create([
                'name' => 'To Delete',
                'email' => 'delete@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation DeleteUser($id: ID!) {
                    deleteUser(id: $id) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['id' => $userToDelete->id], $token);

            $this->assertGraphQLHasNoErrors($response);
            $deleteData = $response['data']['deleteUser'];
            expect($deleteData['success'])->toBeTrue();

            // Verify deletion
            expect(User::find($userToDelete->id))->toBeNull();
        });

        test('non-admin user cannot delete users', function () {
            $paramedic = User::create([
                'name' => 'Paramedic',
                'email' => 'paramedic@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $paramedic->assignRole('paramedic');
            $token = $paramedic->createToken('test', ['*'])->plainTextToken;

            $userToDelete = User::create([
                'name' => 'To Delete',
                'email' => 'delete@ambulancia.local',
                'phone' => '+34612345679',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $query = <<<'GRAPHQL'
                mutation DeleteUser($id: ID!) {
                    deleteUser(id: $id) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, ['id' => $userToDelete->id], $token);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('AssignRole Mutation', function () {
        test('admin can assign role to user', function () {
            $admin = createTestAdminUser();
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation AssignRole($userId: ID!, $role: String!) {
                    assignRoleToUser(userId: $userId, role: $role) {
                        success
                        message
                        user {
                            id
                            name
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'userId' => $user->id,
                'role' => 'dispatcher',
            ], $token);

            $this->assertGraphQLHasNoErrors($response);
            $assignData = $response['data']['assignRoleToUser'];
            expect($assignData['success'])->toBeTrue();

            // Verify role was assigned
            $user->refresh();
            expect($user->hasRole('dispatcher', 'sanctum'))->toBeTrue();
        });

        test('cannot assign non-existent role', function () {
            $admin = createTestAdminUser();
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation AssignRole($userId: ID!, $role: String!) {
                    assignRoleToUser(userId: $userId, role: $role) {
                        success
                        message
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'userId' => $user->id,
                'role' => 'invalid-role',
            ], $token);

            $this->assertGraphQLHasErrors($response);
        });
    });

    describe('RemoveRole Mutation', function () {
        test('admin can remove role from user', function () {
            $admin = createTestAdminUser();
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@ambulancia.local',
                'phone' => '+34612345678',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]);

            $user->assignRole('paramedic');
            $token = $admin->createToken('test', ['*'])->plainTextToken;

            $query = <<<'GRAPHQL'
                mutation RemoveRole($userId: ID!, $role: String!) {
                    removeRoleFromUser(userId: $userId, role: $role) {
                        success
                        message
                        user {
                            id
                            name
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [
                'userId' => $user->id,
                'role' => 'paramedic',
            ], $token);

            $this->assertGraphQLHasNoErrors($response);
            $removeData = $response['data']['removeRoleFromUser'];
            expect($removeData['success'])->toBeTrue();

            // Verify role was removed
            $user->refresh();
            expect($user->hasRole('paramedic', 'sanctum'))->toBeFalse();
        });
    });

    describe('Roles Query', function () {
        test('authenticated user can list all roles', function () {
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
                query Roles {
                    roles {
                        id
                        name
                        permissions {
                            id
                            name
                        }
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $roles = $response['data']['roles'];
            expect(count($roles))->toBeGreaterThan(0);

            $roleNames = array_map(fn($r) => $r['name'], $roles);
            expect(in_array('admin', $roleNames))->toBeTrue();
            expect(in_array('paramedic', $roleNames))->toBeTrue();
        });
    });

    describe('Permissions Query', function () {
        test('authenticated user can list all permissions', function () {
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
                query Permissions {
                    permissions {
                        id
                        name
                    }
                }
            GRAPHQL;

            $response = $this->graphql($query, [], $token);

            $this->assertGraphQLHasNoErrors($response);
            $permissions = $response['data']['permissions'];
            expect(count($permissions))->toBeGreaterThan(10);

            $permissionNames = array_map(fn($p) => $p['name'], $permissions);
            expect(in_array('view-users', $permissionNames))->toBeTrue();
            expect(in_array('create-user', $permissionNames))->toBeTrue();
        });
    });

});

// Helper function to create admin user
function createTestAdminUser(): User
{
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@ambulancia.local',
        'phone' => '+34612345680',
        'password' => Hash::make('password123'),
        'status' => 'active',
    ]);

    $admin->assignRole('admin');
    return $admin;
}

// Helper function to setup roles and permissions
function setupManagementTestRolesAndPermissions(): void
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
            $role->syncPermissions(Permission::all());
        } else {
            $permissionObjects = Permission::whereIn('name', $rolePermissions)->get();
            $role->syncPermissions($permissionObjects);
        }
    }
}
