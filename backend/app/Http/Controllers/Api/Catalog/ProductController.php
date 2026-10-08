<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['productType', 'category', 'destination', 'mainProvider']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%")
                    ->orWhere('ref', 'ilike', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:160',
            'code' => 'required|string|max:30',
            'product_type_id' => 'required|uuid|exists:catalog_product_types,id',
            'category_id' => 'required|uuid|exists:catalog_product_categories,id',
            'primary_destination_id' => 'required|uuid|exists:catalog_destinations,id',
            'main_provider_id' => 'nullable|uuid|exists:catalog_providers,id',
            'duration_value' => 'required|numeric|min:1',
            'duration_unit' => 'required|string|in:horas,días,noches',
            'modality' => 'required|string|in:Privado,Compartido,Grupal',
        ]);

        $tenantId = app('current_tenant_id');
        $ref = SequenceService::nextCode($tenantId, 'product');

        $product = Product::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'ref' => $ref,
            'sku' => 'SKU-' . strtoupper($validated['code']),
            'status' => 'DRAFT',
            'slug' => Str::slug($validated['name']),
        ]);

        AuditLogger::log('Producto', $product->ref, 'crear', null, $product->name);

        return response()->json(['data' => $product], 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with(['productType', 'category', 'destination', 'mainProvider'])->findOrFail($id);
        return response()->json(['data' => $product]);
    }
}