<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserResolver
{
    /**
     * Resolver para mutation createUser
     */
    public function createUser($rootValue, array $args)
    {
        try {
            // Validar que el usuario autenticado es admin
            $authUser = auth('sanctum')->user();
            if (!$authUser || !$authUser->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para crear usuarios',
                    'user' => null,
                ];
            }

            // Crear usuario
            $user = User::create([
                'email' => $args['email'],
                'phone' => $args['phone'] ?? null,
                'name' => $args['name'],
                'password' => Hash::make($args['password']),
                'status' => 'active',
            ]);

            // Asignar roles
            if (!empty($args['roles'])) {
                foreach ($args['roles'] as $roleId) {
                    $role = Role::find($roleId);
                    if ($role) {
                        $user->assignRole($role);
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'user' => $this->formatUserResponse($user),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creando usuario: ' . $e->getMessage(),
                'user' => null,
            ];
        }
    }

    /**
     * Resolver para mutation updateUser
     */
    public function updateUser($rootValue, array $args)
    {
        try {
            $user = User::find($args['id']);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'user' => null,
                ];
            }

            // Validar permisos
            $authUser = auth('sanctum')->user();
            if (!$authUser || (!$authUser->id === $user->id && !$authUser->hasRole('admin'))) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para actualizar este usuario',
                    'user' => null,
                ];
            }

            // Actualizar campos
            if (isset($args['name'])) {
                $user->name = $args['name'];
            }

            if (isset($args['phone'])) {
                $user->phone = $args['phone'];
            }

            if (isset($args['email'])) {
                $user->email = $args['email'];
            }

            $user->save();

            return [
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'user' => $this->formatUserResponse($user),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error actualizando usuario: ' . $e->getMessage(),
                'user' => null,
            ];
        }
    }

    /**
     * Resolver para mutation deleteUser
     */
    public function deleteUser($rootValue, array $args)
    {
        try {
            // Validar que el usuario autenticado es admin
            $authUser = auth('sanctum')->user();
            if (!$authUser || !$authUser->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar usuarios',
                ];
            }

            $user = User::find($args['id']);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ];
            }

            // No permitir eliminar a sí mismo
            if ($user->id === $authUser->id) {
                return [
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta',
                ];
            }

            // Eliminar roles y tokens
            $user->roles()->detach();
            $user->tokens()->delete();

            // Eliminar usuario
            $user->delete();

            return [
                'success' => true,
                'message' => 'Usuario eliminado correctamente',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error eliminando usuario: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolver para mutation assignRoleToUser
     */
    public function assignRoleToUser($rootValue, array $args)
    {
        try {
            // Validar permisos
            $authUser = auth('sanctum')->user();
            if (!$authUser || !$authUser->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para asignar roles',
                    'user' => null,
                ];
            }

            $user = User::find($args['userId']);
            $role = Role::find($args['roleId']);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'user' => null,
                ];
            }

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Rol no encontrado',
                    'user' => null,
                ];
            }

            $user->assignRole($role);

            return [
                'success' => true,
                'message' => "Rol '{$role->name}' asignado correctamente",
                'user' => $this->formatUserResponse($user),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error asignando rol: ' . $e->getMessage(),
                'user' => null,
            ];
        }
    }

    /**
     * Resolver para mutation removeRoleFromUser
     */
    public function removeRoleFromUser($rootValue, array $args)
    {
        try {
            // Validar permisos
            $authUser = auth('sanctum')->user();
            if (!$authUser || !$authUser->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para remover roles',
                    'user' => null,
                ];
            }

            $user = User::find($args['userId']);
            $role = Role::find($args['roleId']);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'user' => null,
                ];
            }

            if (!$role) {
                return [
                    'success' => false,
                    'message' => 'Rol no encontrado',
                    'user' => null,
                ];
            }

            $user->removeRole($role);

            return [
                'success' => true,
                'message' => "Rol '{$role->name}' removido correctamente",
                'user' => $this->formatUserResponse($user),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error removiendo rol: ' . $e->getMessage(),
                'user' => null,
            ];
        }
    }

    /**
     * Resolver para query users (with pagination)
     */
    public function users($rootValue, array $args)
    {
        try {
            // Validar que el usuario autenticado es admin
            $authUser = auth('sanctum')->user();
            if (!$authUser || !$authUser->hasRole('admin')) {
                return [
                    'data' => [],
                    'paginatorInfo' => [
                        'total' => 0,
                        'perPage' => $args['first'] ?? 10,
                        'currentPage' => $args['page'] ?? 1,
                        'lastPage' => 1,
                        'count' => 0,
                        'firstItem' => null,
                        'lastItem' => null,
                        'hasMorePages' => false,
                    ]
                ];
            }

            $perPage = $args['first'] ?? 10;
            $page = $args['page'] ?? 1;

            $users = User::paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $users->items() ? array_map(fn($user) => $this->formatUserResponse($user), $users->items()) : [],
                'paginatorInfo' => [
                    'total' => $users->total(),
                    'perPage' => $users->perPage(),
                    'currentPage' => $users->currentPage(),
                    'lastPage' => $users->lastPage(),
                    'count' => count($users->items()),
                    'firstItem' => $users->firstItem(),
                    'lastItem' => $users->lastItem(),
                    'hasMorePages' => $users->hasMorePages(),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'data' => [],
                'paginatorInfo' => [
                    'total' => 0,
                    'perPage' => $args['first'] ?? 10,
                    'currentPage' => $args['page'] ?? 1,
                    'lastPage' => 1,
                    'count' => 0,
                    'firstItem' => null,
                    'lastItem' => null,
                    'hasMorePages' => false,
                ]
            ];
        }
    }

    /**
     * Formatea la respuesta del usuario
     */
    private function formatUserResponse(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String(),
            'roles' => $user->roles->map(fn($role) => [
                'id' => (string) $role->id,
                'name' => $role->name,
                'description' => $role->description,
            ])->toArray(),
            'permissions' => $user->getAllPermissions()->map(fn($permission) => [
                'id' => (string) $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
            ])->toArray(),
        ];
    }
}
