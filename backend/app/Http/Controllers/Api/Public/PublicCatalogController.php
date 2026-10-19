<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Passenger;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicCatalogController extends Controller
{

    private function resolveTenantId(Request $request): string
    {
        if (app()->bound('current_tenant_id')) {
            return app('current_tenant_id');
        }

        $slug = $request->header('X-Tenant-Slug', 'demo-travel');
        $tenant = \App\Models\Tenant::where('slug', $slug)->firstOrFail();
        app()->instance('current_tenant_id', $tenant->id);

        return $tenant->id;
    }
    /**
     * Catálogo público de productos visibles en web
     */
    public function getProducts(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $products = Product::where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->where('is_bookable', true)
            ->with([
                'category:id,name',
                'destination:id,name,type',
                'productType:id,name',
                'rates' => function ($q) {
                    $q->orderBy('price', 'asc');
                }
            ])
            ->get();

        return response()->json(['data' => $products]);
    }

    /**
     * Detalle de un producto con tarifas públicas vigentes
     */
    public function getProductDetail(Request $request, string $idOrSlug): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $product = Product::where('tenant_id', $tenantId)
            ->where(function ($query) use ($idOrSlug) {
                if (\Illuminate\Support\Str::isUuid($idOrSlug)) {
                    $query->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
                } else {
                    $query->where('slug', $idOrSlug);
                }
            })
            ->where('status', 'ACTIVE')
            ->with([
                'category:id,name',
                'destination:id,name,type',
                'productType:id,name',
                'rates' => function ($q) {
                    $q->orderBy('price', 'asc');
                }
            ])
            ->firstOrFail();

        return response()->json([
            'data' => $product,
            'rates' => $product->rates,
        ]);
    }

    /**
     * Checkout público: Creación de cliente y reserva online directa
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:catalog_products,id',
            'rate_id' => 'required|uuid|exists:catalog_rates,id',
            'travel_date' => 'required|date|after_or_equal:today',
            // Datos del titular
            'contact_name' => 'required|string|max:160',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            'doc_type' => 'required|string|in:DNI,PASAPORTE,CE',
            'doc_number' => 'required|string|max:20',
            // Pasajeros
            'passengers' => 'required|array|min:1',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
            'passengers.*.doc_type' => 'required|string|in:DNI,PASAPORTE,CE',
            'passengers.*.doc_number' => 'required|string|max:30',
        ]);

        $tenantId = $this->resolveTenantId($request);

        return DB::transaction(function () use ($validated, $tenantId) {
            $rate = Rate::findOrFail($validated['rate_id']);
            $product = Product::findOrFail($validated['product_id']);
            $paxCount = count($validated['passengers']);

            // 1. Obtener o asignar un usuario del sistema como owner técnico
            $owner = User::where('tenant_id', $tenantId)->first();

            // 2. Crear o reutilizar cliente CRM
            $client = Client::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'email' => $validated['email'],
                ],
                [
                    'code' => SequenceService::nextCode($tenantId, 'client'),
                    'kind' => 'persona',
                    'name' => $validated['contact_name'],
                    'doc_type' => $validated['doc_type'],
                    'doc_number' => $validated['doc_number'],
                    'phone' => $validated['phone'],
                    'segment_code' => 'B2C_Web',
                    'owner_id' => $owner->id,
                ]
            );

            // 3. Calcular montos
            $unitPrice = $rate->price;
            $total = $unitPrice * $paxCount;

            // 4. Crear reserva confirmada / pendiente de pasarela
            $resCode = SequenceService::nextCode($tenantId, 'reservation');
            $reservation = Reservation::create([
                'tenant_id' => $tenantId,
                'code' => $resCode,
                'client_id' => $client->id,
                'owner_id' => $owner->id,
                'status' => 'confirmada',
                'travel_date_start' => $validated['travel_date'],
                'pax' => $paxCount,
                'currency' => $rate->currency,
                'fx_rate' => 3.75,
                'total' => $total,
                'paid_amount' => 0.00,
                'balance' => $total,
                'notes' => "Reserva directa online web B2C para {$product->name}",
            ]);

            // 5. Registrar pasajeros
            foreach ($validated['passengers'] as $index => $p) {
                Passenger::create([
                    'tenant_id' => $tenantId,
                    'reservation_id' => $reservation->id,
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'doc_type' => $p['doc_type'],
                    'doc_number' => $p['doc_number'],
                    'is_lead' => $index === 0,
                ]);
            }

            AuditLogger::log('CheckoutWeb', $reservation->code, 'checkout_publico', null, "Total: {$total} {$rate->currency}", true);

            return response()->json([
                'message' => 'Reserva web generada exitosamente.',
                'data' => [
                    'reservation_code' => $reservation->code,
                    'total' => $reservation->total,
                    'currency' => $reservation->currency,
                    'client' => $client->name,
                    'pax' => $reservation->pax,
                    'travel_date' => $reservation->travel_date_start,
                ]
            ], 201);
        });
    }
}