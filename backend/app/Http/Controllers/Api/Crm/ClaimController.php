<?php

namespace App\Http\Controllers\Api\Crm;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Claim::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function storePublic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consumer_name' => 'required|string|max:160',
            'doc_type' => 'required|string|in:DNI,CE,PASAPORTE,RUC',
            'doc_number' => 'required|string|max:20',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            'address' => 'required|string|max:200',
            'claim_type' => 'required|string|in:queja,reclamo',
            'contracted_good_service' => 'required|string',
            'detail' => 'required|string',
            'consumer_request' => 'required|string',
        ]);

        $tenantId = app('current_tenant_id');
        $code = SequenceService::nextCode($tenantId, 'claim');

        $claim = Claim::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'code' => $code,
            'status' => 'pendiente',
        ]);

        AuditLogger::log('LibroReclamaciones', $claim->code, 'registrar_reclamo', null, $claim->consumer_name, true);

        return response()->json([
            'message' => 'Reclamación registrada formalmente.',
            'data' => $claim
        ], 201);
    }
}