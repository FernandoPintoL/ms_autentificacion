<?php

namespace App\GraphQL\Resolvers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Validation\ValidationException;

class AuthResolver
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Resolver para mutation login
     */
    public function login($rootValue, array $args)
    {
        try {
            $response = $this->authService->login($args['email'], $args['password']);
            return $response;
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Resolver para mutation loginWhatsApp
     */
    public function loginWhatsApp($rootValue, array $args)
    {
        try {
            $response = $this->authService->loginWhatsApp($args['phone']);
            return $response;
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Resolver para mutation logout
     */
    public function logout($rootValue, array $args, $context)
    {
        try {
            $user = auth('sanctum')->user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'No hay usuario autenticado',
                ];
            }

            $this->authService->logout($user);

            return [
                'success' => true,
                'message' => 'Sesión cerrada correctamente',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolver para mutation refreshToken
     */
    public function refreshToken($rootValue, array $args)
    {
        try {
            // Buscar el usuario del token
            $tokenParts = explode('|', $args['token']);
            if (count($tokenParts) !== 2) {
                throw new \Exception('Token inválido');
            }

            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($args['token']);

            if (!$tokenModel) {
                throw new \Exception('Token no encontrado');
            }

            $user = $tokenModel->tokenable;

            $response = $this->authService->refreshToken($user);
            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error refrescando token: ' . $e->getMessage());
        }
    }

    /**
     * Resolver para query validateToken
     */
    public function validateToken($rootValue, array $args)
    {
        return $this->authService->validateToken($args['token']);
    }

    /**
     * Resolver para query me
     */
    public function me($rootValue, array $args, $context)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return null;
        }

        return $this->formatUserResponse($user);
    }

    /**
     * Resolver para query user
     */
    public function user($rootValue, array $args)
    {
        $user = User::find($args['id']);

        if (!$user) {
            return null;
        }

        return $this->formatUserResponse($user);
    }

    /**
     * Formatea la respuesta del usuario
     * Convierte campos snake_case a camelCase para cumplir con Apollo Federation
     */
    private function formatUserResponse(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'emailVerifiedAt' => $user->email_verified_at?->format('Y-m-d H:i:s'),
            'createdAt' => $user->created_at->format('Y-m-d H:i:s'),
            'updatedAt' => $user->updated_at->format('Y-m-d H:i:s'),
            'roles' => $user->roles->map(fn($role) => [
                'id' => (string) $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'createdAt' => $role->created_at->format('Y-m-d H:i:s'),
                'updatedAt' => $role->updated_at->format('Y-m-d H:i:s'),
            ])->toArray(),
            'permissions' => $user->getAllPermissions()->map(fn($permission) => [
                'id' => (string) $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'createdAt' => $permission->created_at->format('Y-m-d H:i:s'),
                'updatedAt' => $permission->updated_at->format('Y-m-d H:i:s'),
            ])->toArray(),
        ];
    }
}
