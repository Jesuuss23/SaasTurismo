<?php

namespace App\Http\Controllers\Api\Collaboration;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Approval::with(['requester:id,name,email', 'approver:id,name,email']);

        // Ventas solo ve las solicitudes que ha generado
        if ($user->hasRole('sales')) {
            $query->where('requester_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function resolve(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:aprobar,rechazar',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        // Solo roles con permisos de aprobación (Tenant Admin y Manager)
        if (!$user->can('quotes.approve_internal') && !$user->hasRole('tenant_admin')) {
            return response()->json(['error' => 'No autorizado para resolver solicitudes.'], 403);
        }

        return DB::transaction(function () use ($request, $id, $user) {
            $approval = Approval::findOrFail($id);

            if ($approval->status !== 'pendiente') {
                return response()->json(['error' => 'La solicitud ya fue procesada anteriormente.'], 422);
            }

            $newStatus = $request->action === 'aprobar' ? 'aprobada' : 'rechazada';

            $approval->update([
                'status' => $newStatus,
                'approver_id' => $user->id,
                'resolution_notes' => $request->notes,
                'resolved_at' => now(),
            ]);

            AuditLogger::log('Aprobacion', $approval->code, $request->action, 'pendiente', $newStatus, true);

            return response()->json([
                'message' => "Solicitud {$newStatus} con éxito.",
                'data' => $approval
            ]);
        });
    }
}