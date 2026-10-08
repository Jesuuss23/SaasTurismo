<?php

namespace App\Http\Controllers\Api;

use App\Domain\Shared\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'tenant_slug' => 'nullable|string',
        ]);

        // Determinar tenant (por parámetro o por dominio)
        $tenantSlug = $request->input('tenant_slug', 'demo-travel');
        $tenant = Tenant::where('slug', $tenantSlug)->first();

        if (!$tenant || $tenant->status !== 'activo') {
            return response()->json(['error' => 'Empresa o agencia no disponible.'], 403);
        }

        // Buscar usuario dentro de dicho tenant
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuditLogger::log('Sesion', $request->email, 'login_fallido', null, 'Credenciales incorrectas');
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user->status !== 'activo') {
            return response()->json(['error' => 'Usuario inactivo. Contacte al administrador.'], 403);
        }

        // Actualizar último login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Crear token con Sanctum
        $token = $user->createToken('api-token', ['*'], now()->addHours(12))->plainTextToken;

        // Registrar auditoría de inicio de sesión
        app()->instance('current_tenant_id', $tenant->id);
        AuditLogger::log('Sesion', $user->code ?? $user->email, 'login', '', 'OK', true);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'code' => $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'currency' => $tenant->currency,
            ]
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLogger::log('Sesion', $user->code ?? $user->email, 'logout', 'OK', 'Sesion finalizada');
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}