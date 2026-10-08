<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Destination::query();

        if ($request->boolean('tree')) {
            // Retorna solo nodos raíz con sus descendientes
            $destinations = $query->whereNull('parent_id')
                ->with('children.children.children')
                ->orderBy('name')
                ->get();
            return response()->json(['data' => $destinations]);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where('name', 'ilike', "%{$q}%");
        }

        return response()->json([
            'data' => $query->orderBy('level')->orderBy('name')->paginate(50)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|uuid|exists:catalog_destinations,id',
            'name' => 'required|string|max:120',
            'type' => 'required|string|in:País,Región,Departamento,Provincia,Ciudad,Zona,Lugar,Atracción,Ruta',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180|required_with:lat',
            'description' => 'nullable|string',
        ]);

        $tenantId = app('current_tenant_id');

        // Niveles de jerarquía definidos por la especificación
        $levelMap = [
            'País' => 0,
            'Región' => 1,
            'Departamento' => 2,
            'Provincia' => 3,
            'Ciudad' => 4,
            'Zona' => 4,
            'Lugar' => 5,
            'Atracción' => 6,
            'Ruta' => 6,
        ];

        $level = $levelMap[$validated['type']] ?? 0;
        $path = $validated['name'];

        if (!empty($validated['parent_id'])) {
            $parent = Destination::findOrFail($validated['parent_id']);
            $path = $parent->path . ' / ' . $validated['name'];
        }

        $ref = SequenceService::nextCode($tenantId, 'destination');

        $destination = Destination::create([
            'tenant_id' => $tenantId,
            'ref' => $ref,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'level' => $level,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'description' => $validated['description'] ?? null,
            'path' => $path,
        ]);

        AuditLogger::log('Destino', $destination->ref, 'crear', null, $destination->name);

        return response()->json(['data' => $destination], 201);
    }

    public function show(string $id): JsonResponse
    {
        $destination = Destination::with(['parent', 'children'])->findOrFail($id);
        return response()->json(['data' => $destination]);
    }
}