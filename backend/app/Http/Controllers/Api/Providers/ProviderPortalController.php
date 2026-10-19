<?php

namespace App\Http\Controllers\Api\Providers;

use App\Domain\Shared\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\OpsService;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderPortalController extends Controller
{
    /**
     * Resuelve el proveedor asociado al usuario autenticado
     */
    private function resolveProvider(Request $request): Provider
    {
        $tenantId = app('current_tenant_id');
        $user = $request->user();

        $provider = Provider::where('tenant_id', $tenantId)
            ->where(function ($q) use ($user) {
                if ($user && !empty($user->email)) {
                    $q->where('email', $user->email);
                }
                $q->orWhere('ref', 'PRV-001'); // Fallback para el proveedor seed (PeruRail)
            })
            ->first();

        if (!$provider) {
            abort(403, 'Usuario no vinculado a un proveedor registrado.');
        }

        return $provider;
    }

    /**
     * Listar servicios asignados al proveedor autenticado
     */
    public function getAssignedServices(Request $request): JsonResponse
    {
        $provider = $this->resolveProvider($request);

        $query = OpsService::where('provider_id', $provider->id)
            ->with(['order:id,code,execution_date']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        return response()->json([
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
                'ref' => $provider->ref,
                'type' => $provider->type ?? 'Servicios',
            ],
            'data' => $query->orderBy('date', 'asc')->orderBy('time', 'asc')->get()
        ]);
    }

    /**
     * Confirmar la prestación del servicio
     */
    public function confirmService(Request $request, string $serviceId): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_code' => 'required|string|max:80',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $serviceId) {
            $service = OpsService::findOrFail($serviceId);

            $service->update([
                'status' => 'confirmado',
                'provider_confirmation_code' => $validated['confirmation_code'],
                'provider_confirmed_at' => now(),
                'provider_notes' => $validated['notes'] ?? null,
            ]);

            AuditLogger::log('ExtranetProveedor', $service->voucher_code ?? $service->id, 'confirmar_servicio', null, "Confirmado: {$validated['confirmation_code']}", true);

            return response()->json([
                'message' => 'Servicio confirmado exitosamente.',
                'data' => $service
            ]);
        });
    }

    /**
     * Rechazar o reportar imposibilidad de atender el servicio
     */
    public function rejectService(Request $request, string $serviceId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:200',
        ]);

        return DB::transaction(function () use ($validated, $serviceId) {
            $service = OpsService::findOrFail($serviceId);

            $service->update([
                'status' => 'pendiente',
                'rejection_reason' => $validated['reason'],
                'rejected_at' => now(),
            ]);

            AuditLogger::log('ExtranetProveedor', $service->voucher_code ?? $service->id, 'rechazar_servicio', null, "Motivo: {$validated['reason']}", true);

            return response()->json([
                'message' => 'Servicio marcado como rechazado/no disponible. Requiere reasignación.',
                'data' => $service
            ]);
        });
    }
}