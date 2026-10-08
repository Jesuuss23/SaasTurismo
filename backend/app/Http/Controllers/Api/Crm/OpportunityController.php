<?php

namespace App\Http\Controllers\Api\Crm;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Opportunity::with(['client:id,name,code', 'owner:id,name,email', 'product:id,name,ref']);

        // Seera daangaa abbaa qabeenyaa (Ownership SEC-BR-09)
        if ($user->hasRole('sales')) {
            $query->where('owner_id', $user->id);
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('expected_close_date', 'asc')->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|uuid|exists:crm_clients,id',
            'title' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:PEN,USD',
            'expected_close_date' => 'required|date|after_or_equal:today',
            'pax' => 'required|integer|min:1',
            'product_id' => 'nullable|uuid|exists:catalog_products,id',
            'stage' => 'nullable|string|in:calificacion,descubrimiento,cotizacion,negociacion,ganada,perdida',
            'notes' => 'nullable|string',
        ]);

        $tenantId = app('current_tenant_id');
        $ref = SequenceService::nextCode($tenantId, 'opportunity');

        $opportunity = Opportunity::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'code' => $ref,
            'stage' => $validated['stage'] ?? 'descubrimiento',
            'owner_id' => $request->user()->id,
        ]);

        AuditLogger::log('Oportunidad', $opportunity->code, 'crear', null, $opportunity->title);

        return response()->json(['data' => $opportunity], 201);
    }

    public function show(string $id): JsonResponse
    {
        $opportunity = Opportunity::with(['client', 'owner', 'product'])->findOrFail($id);
        return response()->json(['data' => $opportunity]);
    }
}