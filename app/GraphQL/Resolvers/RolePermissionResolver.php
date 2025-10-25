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
     */
    private function formatRoleResponse(Role $role): array
    {
        return [
            'id' => (string) $role->id,
            'name' => $role->name,
            'description' => $role->description ?? null,
            'created_at' => $role->created_at->toIso8601String(),
            'updated_at' => $role->updated_at->toIso8601String(),
            'permissions' => $role->permissions->map(function ($permission) {
                return $this->formatPermissionResponse($permission);
            })->toArray(),
        ];
    }

    /**
     * Formatea la respuesta del permiso
     */
    private function formatPermissionResponse(Permission $permission): array
    {
        return [
            'id' => (string) $permission->id,
            'name' => $permission->name,
            'description' => $permission->description ?? null,
            'created_at' => $permission->created_at->toIso8601String(),
            'updated_at' => $permission->updated_at->toIso8601String(),
        ];
    }
}
