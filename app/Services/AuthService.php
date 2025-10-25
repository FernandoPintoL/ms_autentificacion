<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthService
{
    /**
     * Autenticar usuario con email y contraseña
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        // Validar que el usuario existe
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'El usuario no existe.',
            ]);
        }

        // Validar contraseña
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña es incorrecta.',
            ]);
        }

        // Validar que el usuario está activo
        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => 'El usuario está inactivo.',
            ]);
        }

        // Generar token
        return $this->generateTokenResponse($user);
    }

    /**
     * Autenticar usuario por teléfono (WhatsApp)
     *
     * @param string $phone
     * @return array
     * @throws ValidationException
     */
    public function loginWhatsApp(string $phone): array
    {
        // Limpiar y formatear número de teléfono
        $phone = $this->formatPhoneNumber($phone);

        $user = User::where('phone', $phone)->first();

        // Si el usuario no existe, crear uno nuevo con rol de paramédico
        if (!$user) {
            $user = User::create([
                'phone' => $phone,
                'email' => 'whatsapp_' . time() . '@ambulancia.local',
                'name' => 'Paramédico ' . $phone,
                'password' => Hash::make(Str::random(32)),
                'status' => 'active',
            ]);

            // Asignar rol de paramédico
            $user->assignRole('paramedic');

            return array_merge(
                $this->generateTokenResponse($user),
                ['isNewUser' => true, 'requiresSetup' => true]
            );
        }

        // Validar que el usuario está activo
        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'phone' => 'El usuario está inactivo.',
            ]);
        }

        return array_merge(
            $this->generateTokenResponse($user),
            ['isNewUser' => false, 'requiresSetup' => false]
        );
    }

    /**
     * Logout - revocar token actual
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        // Revocar token actual
        $user->currentAccessToken()->delete();

        return true;
    }

    /**
     * Refresh token
     *
     * @param User $user
     * @return array
     */
    public function refreshToken(User $user): array
    {
        // Revocar token anterior
        $user->currentAccessToken()->delete();

        // Generar nuevo token
        return $this->generateTokenResponse($user);
    }

    /**
     * Validar token
     *
     * @param string $token
     * @return array
     */
    public function validateToken(string $token): array
    {
        try {
            // Intentar encontrar el token en la base de datos
            $parts = explode('|', $token);

            if (count($parts) !== 2) {
                return [
                    'isValid' => false,
                    'message' => 'Token inválido',
                    'userId' => null,
                    'expiresAt' => null,
                ];
            }

            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if (!$tokenModel || $tokenModel->revoked) {
                return [
                    'isValid' => false,
                    'message' => 'Token revocado o expirado',
                    'userId' => null,
                    'expiresAt' => null,
                ];
            }

            $user = $tokenModel->tokenable;

            return [
                'isValid' => true,
                'userId' => (string) $user->id,
                'expiresAt' => $tokenModel->expires_at?->toIso8601String(),
                'message' => 'Token válido',
            ];
        } catch (\Exception $e) {
            return [
                'isValid' => false,
                'message' => 'Error validando token: ' . $e->getMessage(),
                'userId' => null,
                'expiresAt' => null,
            ];
        }
    }

    /**
     * Generar respuesta de token
     *
     * @param User $user
     * @return array
     */
    private function generateTokenResponse(User $user): array
    {
        // Crear token con Sanctum
        $token = $user->createToken(
            'ambulancia-token',
            $user->getPermissionNames()->toArray()
        );

        $permissions = $user->getAllPermissions()->map(fn($permission) => [
            'id' => (string) $permission->id,
            'name' => $permission->name,
        ])->toArray();

        return [
            'success' => true,
            'message' => 'Autenticación exitosa',
            'token' => $token->plainTextToken,
            'tokenType' => 'Bearer',
            'expiresAt' => now()->addHours(24)->toIso8601String(),
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'roles' => $user->roles->map(fn($role) => [
                    'id' => (string) $role->id,
                    'name' => $role->name,
                ])->toArray(),
                'permissions' => $permissions,
            ],
            'permissions' => $permissions,
        ];
    }

    /**
     * Formatear número de teléfono
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remover caracteres especiales
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si comienza con +34, es España
        if (strpos($phone, '+34') === 0) {
            return $phone;
        }

        // Si comienza con 34, agregar +
        if (strpos($phone, '34') === 0) {
            return '+' . $phone;
        }

        // Si comienza con 6 o 7 (España), agregar +34
        if (preg_match('/^[67]/', $phone)) {
            return '+34' . $phone;
        }

        return $phone;
    }
}
