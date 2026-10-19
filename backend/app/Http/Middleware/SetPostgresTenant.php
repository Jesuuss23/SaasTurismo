<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetPostgresTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        // 1. Si el usuario ya está autenticado (Sanctum)
        if ($request->user() && !empty($request->user()->tenant_id)) {
            $tenantId = $request->user()->tenant_id;
        }

        // 2. Si viene por header X-Tenant-Id
        if (!$tenantId && $request->hasHeader('X-Tenant-Id')) {
            $tenantId = $request->header('X-Tenant-Id');
        }

        // 3. Si viene por header X-Tenant-Slug
        if (!$tenantId && $request->hasHeader('X-Tenant-Slug')) {
            $slug = $request->header('X-Tenant-Slug');
            $tenant = Tenant::where('slug', $slug)->first();
            if ($tenant) {
                $tenantId = $tenant->id;
            }
        }

        // 4. Si viene por Query Parameter (?tenant=demo-travel)
        if (!$tenantId && $request->filled('tenant')) {
            $slugOrId = (string) $request->query('tenant');
            $tenant = \Illuminate\Support\Str::isUuid($slugOrId)
                ? Tenant::where('id', $slugOrId)->first()
                : Tenant::where('slug', $slugOrId)->first();

            if ($tenant) {
                $tenantId = $tenant->id;
            }
        }

        // 5. Fallback por subdominio
        if (!$tenantId) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) >= 3 && !in_array($parts[0], ['turismo', 'turismobk', 'api', 'app', 'www'])) {
                $tenant = Tenant::where('slug', $parts[0])->first();
                if ($tenant) {
                    $tenantId = $tenant->id;
                }
            }
        }

        // 6. Fallback final para demo si no se especificó
        if (!$tenantId && config('app.env') !== 'production') {
            $tenant = Tenant::where('slug', 'demo-travel')->first();
            if ($tenant) {
                $tenantId = $tenant->id;
            }
        }

        if (!$tenantId) {
            return response()->json(['error' => 'Inquilino (tenant) no identificado o inválido.'], 400);
        }

        app()->instance('current_tenant_id', $tenantId);
        DB::statement("SET LOCAL app.tenant_id = '{$tenantId}';");

        return $next($request);
    }
}