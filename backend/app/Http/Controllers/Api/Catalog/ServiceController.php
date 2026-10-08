<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with('provider:id,name,ref,type');

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'data' => $query->orderBy('name')->paginate(30)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|uuid|exists:catalog_providers,id',
            'name' => 'required|string|max:200',
            'type' => 'required|string|in:Traslado,Transporte,Tren,Entrada,Guía,Alimentación,Alojamiento,Actividad,Tour,Seguro,Otro',
            'unit' => 'required|string|in:pax,servicio,habitación,noche,vehículo,unidad',
            'cost' => 'required|numeric|min:0',
            'currency' => 'required|string|in:PEN,USD',
            'capacity' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after_or_equal:valid_from',
        ]);

        $tenantId = app('current_tenant_id');
        $ref = SequenceService::nextCode($tenantId, 'service');

        $service = Service::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'ref' => $ref,
            'status' => 'activo',
        ]);

        AuditLogger::log('Servicio', $service->ref, 'crear', null, "{$service->name} ({$service->cost} {$service->currency})");

        return response()->json(['data' => $service], 201);
    }
}