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
     * Listar servicios asignados a un proveedor específico
     */
    public function index(Request $request, string $providerId): JsonResponse
    {
        $provider = Provider::findOrFail($providerId);

        $query = OpsService::where('provider_id', $provider->id)
            ->with(['order.reservation.passengers']);

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
                'type' => $provider->type,
            ],
            'services' => $query->orderBy('date', 'asc')->paginate(20)
        ]);
    }

    /**
     * Confirmar la prestación del servicio por parte del proveedor
     */
    public function confirmService(Request $request, string $serviceId): JsonResponse
    {
        $validated = $request->validate([
            'confirmation_code' => 'required|string|max:60',
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

            AuditLogger::log('ExtranetProveedor', $service->voucher_code ?? $service->id, 'confirmar_servicio', null, "Confirmado con código {$validated['confirmation_code']}", true);

            return response()->json([
                'message' => 'Servicio confirmado exitosamente por el proveedor.',
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
                'status' => 'pendiente', // Regresa a pendiente para reasignación
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