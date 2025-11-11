<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use GraphQL\Error\Error;

/**
 * Entity Resolver for Apollo Federation
 *
 * Resolves entity references from other subgraphs.
 * This is required for federation to work properly.
 */
class EntityResolver
{
    /**
     * Resolve User entity reference
     *
     * When another service references a User by ID, this resolver
     * fetches the complete User object from this service.
     *
     * @param array $obj Contains the __typename and primary key fields
     * @return User|null
     */
    public function resolveUserReference(array $obj)
    {
        try {
            $user = User::with(['roles', 'permissions'])
                ->find($obj['id']);

            if (!$user) {
                return null;
            }

            // Transform database model to GraphQL response format
            return [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'roles' => $user->roles->toArray(),
                'permissions' => $user->permissions->toArray(),
                'emailVerifiedAt' => $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null,
                'createdAt' => $user->created_at->format('Y-m-d H:i:s'),
                'updatedAt' => $user->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            \Log::error('EntityResolver: Error resolving User reference', [
                'userId' => $obj['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resolve Role entity reference
     *
     * @param array $obj Contains the __typename and primary key fields
     * @return array|null
     */
    public function resolveRoleReference(array $obj)
    {
        try {
            $role = Role::with('permissions')
                ->find($obj['id']);

            if (!$role) {
                return null;
            }

            return [
                'id' => (string) $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->toArray(),
                'createdAt' => $role->created_at->format('Y-m-d H:i:s'),
                'updatedAt' => $role->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            \Log::error('EntityResolver: Error resolving Role reference', [
                'roleId' => $obj['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resolve Permission entity reference
     *
     * @param array $obj Contains the __typename and primary key fields
     * @return array|null
     */
    public function resolvePermissionReference(array $obj)
    {
        try {
            $permission = Permission::find($obj['id']);

            if (!$permission) {
                return null;
            }

            return [
                'id' => (string) $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'createdAt' => $permission->created_at->format('Y-m-d H:i:s'),
                'updatedAt' => $permission->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            \Log::error('EntityResolver: Error resolving Permission reference', [
                'permissionId' => $obj['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
