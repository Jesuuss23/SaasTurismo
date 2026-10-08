<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Provider::query()->withCount('services');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('ruc', 'like', "%{$q}%")
                    ->orWhere('ref', 'ilike', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('name')->paginate(25)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'type' => 'required|string|in:Hotel,Transporte,Tren,Guía,Conductor,Restaurante,Operador,Actividad,Entrada,Otros',
            'ruc' => ['required', 'string', 'size:11', 'regex:/^(10|15|17|20)\d{9}$/'],
            'city' => 'nullable|string|max:80',
            'stars' => 'nullable|integer|between:1,5',
            'contact_name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'contract_start' => 'required|date',
            'contract_end' => 'required|date|after_or_equal:contract_start',
            'currency' => 'required|string|in:PEN,USD',
            'payment_terms' => 'nullable|string|max:120',
        ]);

        $tenantId = app('current_tenant_id');
        $ref = SequenceService::nextCode($tenantId, 'provider');

        $provider = Provider::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'ref' => $ref,
            'status' => 'activo',
        ]);

        AuditLogger::log('Proveedor', $provider->ref, 'crear', null, $provider->name);

        return response()->json(['data' => $provider], 201);
    }

    public function show(string $id): JsonResponse
    {
        $provider = Provider::with('services')->findOrFail($id);
        return response()->json(['data' => $provider]);
    }
}