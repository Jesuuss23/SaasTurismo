<?php

namespace App\Http\Controllers\Api\Bookings;

use App\Domain\Shared\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TravelDocumentController extends Controller
{
    /**
     * Resuelve la reserva evaluando si el parámetro es UUID o código de reserva
     */
    private function findReservation(string $tenantId, string $codeOrId): Reservation
    {
        $query = Reservation::where('tenant_id', $tenantId);

        if (Str::isUuid($codeOrId)) {
            $query->where(function ($q) use ($codeOrId) {
                $q->where('id', $codeOrId)
                  ->orWhere('code', $codeOrId);
            });
        } else {
            $query->where('code', $codeOrId);
        }

        return $query->with([
            'client',
            'passengers',
            'owner:id,name,email',
        ])->firstOrFail();
    }

    /**
     * Devuelve el payload estructurado del Voucher / Documento de Viaje
     */
    public function getVoucherData(Request $request, string $codeOrId): JsonResponse
    {
        $tenantId = app('current_tenant_id');
        $reservation = $this->findReservation($tenantId, $codeOrId);

        AuditLogger::log('DocumentosViaje', $reservation->code, 'consultar_voucher', null, "Voucher consultado para {$reservation->code}");

        return response()->json([
            'data' => [
                'document_type' => 'CONFIRMACION_Y_VOUCHER',
                'reservation_code' => $reservation->code,
                'issue_date' => now()->toIso8601String(),
                'status' => $reservation->status,
                'travel_date' => $reservation->travel_date_start->format('Y-m-d'),
                'client' => [
                    'name' => $reservation->client->name,
                    'doc_type' => $reservation->client->doc_type,
                    'doc_number' => $reservation->client->doc_number,
                    'email' => $reservation->client->email,
                    'phone' => $reservation->client->phone,
                ],
                'passengers' => $reservation->passengers->map(fn($p) => [
                    'full_name' => "{$p->first_name} {$p->last_name}",
                    'doc_type' => $p->doc_type,
                    'doc_number' => $p->doc_number,
                    'is_lead' => $p->is_lead,
                ]),
                'financial_summary' => [
                    'currency' => $reservation->currency,
                    'total' => $reservation->total,
                    'paid_amount' => $reservation->paid_amount,
                    'balance' => $reservation->balance,
                    'is_settled' => ((float)$reservation->balance <= 0.0),
                ],
            ]
        ]);
    }

    /**
     * Renderiza una vista HTML imprimible/convertible a PDF del Voucher
     */
    public function renderVoucherHtml(Request $request, string $codeOrId): Response
    {
        $tenantId = app('current_tenant_id');
        $reservation = $this->findReservation($tenantId, $codeOrId);

        $html = view('documents.voucher', compact('reservation'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}