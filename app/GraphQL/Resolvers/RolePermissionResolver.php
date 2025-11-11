<?php

namespace App\GraphQL\Resolvers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionResolver
{
    /**
     * Resolver para query roles
     */
    public function roles($rootValue, array $args)
    {
        // Check authentication
        $user = auth('sanctum')->user();
        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        $roles = Role::with('permissions')->get();

        return $roles->map(function ($role) {
            return $this->formatRoleResponse($role);
        })->toArray();
    }

    /**
     * Resolver para query permissions
     */
    public function permissions($rootValue, array $args)
    {
        // Check authentication
        $user = auth('sanctum')->user();
        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        $permissions = Permission::all();

        return $permissions->map(function ($permission) {
            return $this->formatPermissionResponse($permission);
        })->toArray();
    }

    /**
     * Resolver para query userPermissions
     */
    public function userPermissions($rootValue, array $args)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return [];
        }

        return $user->getAllPermissions()->map(function ($permission) {
            return $this->formatPermissionResponse($permission);
        })->toArray();
    }

    /**
     * Formatea la respuesta del rol
     * Convierte campos snake_case a camelCase para cumplir con Apollo Federation
     */
    private function formatRoleResponse(Role $role): array
    {
        return [
            'id' => (string) $role->id,
            'name' => $role->name,
            'description' => $role->description ?? null,
            'createdAt' => $role->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $role->updated_at->format('Y-m-d H:i:s'),
            'permissions' => $role->permissions->map(function ($permission) {
                return $this->formatPermissionResponse($permission);
            })->toArray(),
        ];
    }

    /**
     * Formatea la respuesta del permiso
     * Convierte campos snake_case a camelCase para cumplir con Apollo Federation
     */
    private function formatPermissionResponse(Permission $permission): array
    {
        return [
            'id' => (string) $permission->id,
            'name' => $permission->name,
            'description' => $permission->description ?? null,
            'createdAt' => $permission->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $permission->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
