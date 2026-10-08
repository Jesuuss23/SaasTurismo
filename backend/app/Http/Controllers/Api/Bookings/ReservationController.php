<?php

namespace App\Http\Controllers\Api\Bookings;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Passenger;
use App\Models\Quote;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Reservation::with(['client:id,name,code', 'owner:id,name,email', 'quote:id,code']);

        if ($user->hasRole('sales')) {
            $query->where('owner_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(20)
        ]);
    }

    public function storeFromQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => 'required|uuid|exists:quote_quotes,id',
            'travel_date_start' => 'required|date',
            'passengers' => 'required|array|min:1',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
            'passengers.*.doc_type' => 'required|string|in:PASAPORTE,DNI,CE',
            'passengers.*.doc_number' => 'required|string|max:30',
        ]);

        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $tenantId, $request) {
            $quote = Quote::findOrFail($validated['quote_id']);
            $ref = SequenceService::nextCode($tenantId, 'reservation');

            $reservation = Reservation::create([
                'tenant_id' => $tenantId,
                'code' => $ref,
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'owner_id' => $request->user()->id,
                'status' => 'confirmada',
                'travel_date_start' => $validated['travel_date_start'],
                'pax' => count($validated['passengers']),
                'currency' => $quote->currency,
                'fx_rate' => $quote->fx_rate,
                'total' => $quote->total,
                'paid_amount' => 0.00,
                'balance' => $quote->total,
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

            // Haala tilmaama gatii gara 'convertida'tti jijjiiruu
            $quote->update(['status' => 'convertida']);

            AuditLogger::log('Reserva', $reservation->code, 'crear_desde_cotizacion', $quote->code, $reservation->code, true);

            return response()->json(['data' => $reservation->load('passengers')], 201);
        });
    }

    public function show(string $id): JsonResponse
    {
        $reservation = Reservation::with(['client', 'quote', 'owner', 'passengers'])->findOrFail($id);
        return response()->json(['data' => $reservation]);
    }
}