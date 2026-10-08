<?php

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogger
{
    public static function log(
        string $entity,
        string $entityId,
        string $action,
        ?string $before = null,
        ?string $after = null,
        bool $isCritical = false,
        ?array $evidence = null
    ): void {
        $user = auth()->user();
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : ($user?->tenant_id ?? null);

        DB::table('audit_events')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'actor_type' => $user ? 'user' : 'system',
            'actor_id' => $user?->id,
            'actor_role' => $user ? ($user->roles->first()?->name ?? 'sin_rol') : 'Sistema',
            'entity' => $entity,
            'entity_id' => $entityId,
            'action' => $action,
            'is_critical' => $isCritical,
            'before_value' => $before,
            'after_value' => $after,
            'ip' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 250),
            'correlation_id' => request()->header('X-Request-Id', (string) Str::uuid()),
            'evidence' => $evidence ? json_encode($evidence) : null,
            'occurred_at' => now(),
        ]);
    }
}