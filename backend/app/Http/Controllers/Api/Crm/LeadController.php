<?php

namespace App\Http\Controllers\Api\Crm;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Lead::with(['owner:id,name,email', 'product:id,name,ref', 'destination:id,name']);

        // Akkaataa seera ownership (SEC-BR-09) tti, gurgurtaan kan ofii qofa arga
        if ($user->hasRole('sales')) {
            $query->where(function ($sub) use ($user) {
                $sub->where('owner_id', $user->id)
                    ->orWhereNull('owner_id'); // kuusaa banaa (cola comercial)
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:160',
            'company' => 'nullable|string|max:160',
            'email' => 'required_without:phone|nullable|email',
            'phone' => 'required_without:email|nullable|string|min:7|max:40',
            'channel_code' => 'required|string|max:40',
            'source_code' => 'required|string|max:40',
            'interest_code' => 'required|string|max:40',
            'destination_id' => 'nullable|uuid|exists:catalog_destinations,id',
            'product_id' => 'nullable|uuid|exists:catalog_products,id',
            'travel_date' => 'nullable|date|after_or_equal:today',
            'pax' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $tenantId = app('current_tenant_id');
        $ref = SequenceService::nextCode($tenantId, 'lead');

        // Qabxii jalqabaa (score) herreguu (CRM-BR-07)
        $score = 25;
        if (!empty($validated['email'])) $score += 8;
        if (!empty($validated['phone'])) $score += 8;
        if (!empty($validated['travel_date'])) $score += 10;
        if ($validated['pax'] > 1) $score += 5;
        if (!empty($validated['product_id'])) $score += 8;

        $lead = Lead::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'code' => $ref,
            'status' => 'nuevo',
            'score' => min(70, $score),
            'owner_id' => $request->user()->hasRole('sales') ? $request->user()->id : null,
        ]);

        AuditLogger::log('Lead', $lead->code, 'crear', null, $lead->full_name);

        return response()->json(['data' => $lead], 201);
    }

    public function show(string $id): JsonResponse
    {
        $lead = Lead::with(['owner', 'product', 'destination'])->findOrFail($id);
        return response()->json(['data' => $lead]);
    }
}