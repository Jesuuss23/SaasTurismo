<?php

namespace App\Http\Controllers\Api\Portal;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Passenger;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Receivable;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class B2bPortalController extends Controller
{

    private function resolveAgency(Request $request): Client
    {
        $tenantId = app('current_tenant_id');
        $user = $request->user();

        // 1. Coincidencia por email o segmento B2B
        $agency = Client::where('tenant_id', $tenantId)
            ->where(function ($q) use ($user) {
                if ($user && !empty($user->email)) {
                    $q->where('email', $user->email);
                }
                $q->orWhere('segment_code', 'B2B_Agency')
                  ->orWhere('kind', 'empresa');
            })
            ->first();

        // 2. Fallback de demostración
        if (!$agency) {
            $agency = Client::where('tenant_id', $tenantId)
                ->where('kind', 'empresa')
                ->first();
        }

        if (!$agency) {
            abort(403, 'Usuario no vinculado a una agencia B2B registrada.');
        }

        return $agency;
    }

    /**
     * Resumen de la agencia: crédito, tarifas especiales y reservas emitidas
     */
    public function getAgencyProfile(Request $request): JsonResponse
    {
        $agency = $this->resolveAgency($request);
        $tenantId = app('current_tenant_id');

        $reservations = Reservation::where('tenant_id', $tenantId)
            ->where('client_id', $agency->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCreditUsed = Receivable::where('tenant_id', $tenantId)
            ->where('client_id', $agency->id)
            ->whereIn('status', ['pendiente', 'parcial'])
            ->sum('balance');

        return response()->json([
            'agency' => [
                'id' => $agency->id,
                'name' => $agency->name,
                'code' => $agency->code,
                'tax_id' => $agency->doc_number,
                'credit_limit' => 5000.00, // Límite asignado
                'credit_used' => round((float) $totalCreditUsed, 2),
                'credit_available' => round(5000.00 - (float) $totalCreditUsed, 2),
            ],
            'reservations' => $reservations,
        ]);
    }

    /**
     * Catálogo con tarifas B2B / Mayoristas
     */
    public function getCatalog(Request $request): JsonResponse
    {
        $tenantId = app('current_tenant_id');

        $products = Product::where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->with([
                'category:id,name',
                'destination:id,name',
                'rates' => function ($q) {
                    $q->where('channel', 'B2B')
                      ->orWhere('channel', 'Todos')
                      ->orderBy('price', 'asc');
                }
            ])
            ->get();

        return response()->json(['data' => $products]);
    }

    /**
     * Emisión directa de reserva mayorista contra línea de crédito
     */
    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:catalog_products,id',
            'rate_id' => 'required|uuid|exists:catalog_rates,id',
            'travel_date' => 'required|date|after_or_equal:today',
            'agency_reference' => 'nullable|string|max:80',
            'passengers' => 'required|array|min:1',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
            'passengers.*.doc_type' => 'required|string|in:DNI,PASAPORTE,CE',
            'passengers.*.doc_number' => 'required|string|max:30',
        ]);

        $agency = $this->resolveAgency($request);
        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $agency, $tenantId) {
            $rate = Rate::findOrFail($validated['rate_id']);
            $product = Product::findOrFail($validated['product_id']);
            $paxCount = count($validated['passengers']);
            $total = $rate->price * $paxCount;

            $owner = User::where('tenant_id', $tenantId)->first();
            $resCode = SequenceService::nextCode($tenantId, 'reservation');

            $reservation = Reservation::create([
                'tenant_id' => $tenantId,
                'code' => $resCode,
                'client_id' => $agency->id,
                'owner_id' => $owner->id,
                'status' => 'confirmada',
                'travel_date_start' => $validated['travel_date'],
                'pax' => $paxCount,
                'currency' => $rate->currency,
                'fx_rate' => 3.75,
                'total' => $total,
                'paid_amount' => 0.00,
                'balance' => $total,
                'notes' => "Reserva B2B por Agencia {$agency->name}. Ref externa: " . ($validated['agency_reference'] ?? 'S/R'),
            ]);

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

            // Registrar cuenta por cobrar corporativa a la agencia
            Receivable::create([
                'tenant_id' => $tenantId,
                'reservation_id' => $reservation->id,
                'client_id' => $agency->id,
                'concept' => "Facturación B2B Reserva {$reservation->code}",
                'amount' => $total,
                'paid_amount' => 0.00,
                'balance' => $total,
                'currency' => $rate->currency,
                'due_date' => now()->addDays(15)->toDateString(), // 15 días de crédito
                'status' => 'pendiente',
            ]);

            AuditLogger::log('PortalB2B', $reservation->code, 'emision_b2b', null, "Agencia {$agency->name} emitió reserva por {$total} {$rate->currency}", true);

            return response()->json([
                'message' => 'Reserva B2B emitida exitosamente contra cuenta corriente.',
                'data' => [
                    'reservation_code' => $reservation->code,
                    'total' => $reservation->total,
                    'currency' => $reservation->currency,
                    'agency' => $agency->name,
                ]
            ], 201);
        });
    }
}