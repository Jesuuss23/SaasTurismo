<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Allotment;
use App\Models\AllotmentDate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllotmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Allotment::with(['provider:id,name,ref', 'service:id,name,ref']);

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'provider_id' => 'required|uuid|exists:catalog_providers,id',
            'service_id' => 'nullable|uuid|exists:catalog_services,id',
            'type' => 'required|string|in:asientos,habitaciones,cupos',
            'release_days' => 'required|integer|min:0',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'daily_qty' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $tenantId) {
            $code = SequenceService::nextCode($tenantId, 'allotment');

            $allotment = Allotment::create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'name' => $validated['name'],
                'provider_id' => $validated['provider_id'],
                'service_id' => $validated['service_id'] ?? null,
                'type' => $validated['type'],
                'release_days' => $validated['release_days'],
                'date_from' => $validated['date_from'],
                'date_to' => $validated['date_to'],
                'status' => 'activo',
            ]);

            // Generar fechas del período automáticamente
            $period = CarbonPeriod::create($validated['date_from'], $validated['date_to']);
            foreach ($period as $date) {
                AllotmentDate::create([
                    'tenant_id' => $tenantId,
                    'allotment_id' => $allotment->id,
                    'date' => $date->format('Y-m-d'),
                    'allocated_qty' => $validated['daily_qty'],
                    'used_qty' => 0,
                    'released_qty' => 0,
                    'unit_cost' => $validated['unit_cost'],
                    'status' => 'abierto',
                ]);
            }

            AuditLogger::log('Cupo', $allotment->code, 'crear', null, $allotment->name, true);

            return response()->json(['data' => $allotment->load('dates')], 201);
        });
    }

    public function show(string $id): JsonResponse
    {
        $allotment = Allotment::with(['provider', 'service', 'dates'])->findOrFail($id);
        return response()->json(['data' => $allotment]);
    }
}