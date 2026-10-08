<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetPostgresTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        // Si el usuario está autenticado vía Sanctum
        if ($request->user() && $request->user()->tenant_id) {
            $tenantId = $request->user()->tenant_id;
        } 
        // Para subdominios públicos (e-commerce y portales)
        elseif ($request->header('X-Tenant-Id')) {
            $tenantId = $request->header('X-Tenant-Id');
        }

        if ($tenantId) {
            app()->instance('current_tenant_id', $tenantId);
            // Setea el tenant a nivel de sesión en PostgreSQL para RLS
            DB::statement("SELECT set_config('app.tenant_id', ?, true)", [$tenantId]);
        }

        return $next($request);
    }
}