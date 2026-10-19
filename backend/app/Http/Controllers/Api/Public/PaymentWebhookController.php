<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\GatewayTransaction;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_code' => 'required|string|exists:booking_reservations,code',
            'gateway'          => 'required|string|in:stripe,niubiz,culqi,mercadopago',
            'transaction_id'   => 'required|string|max:120',
            'amount'           => 'required|numeric|min:0.01',
            'currency'         => 'required|string|in:USD,PEN',
            'card_brand'       => 'nullable|string|max:30',
            'card_last4'       => 'nullable|string|size:4',
        ]);

        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $tenantId, $request) {
            // 1. Idempotencia: Verificar si la transacción ya fue procesada
            $existingTx = GatewayTransaction::where('tenant_id', $tenantId)
                ->where('gateway', $validated['gateway'])
                ->where('transaction_id', $validated['transaction_id'])
                ->first();

            if ($existingTx && $existingTx->status === 'completed') {
                return response()->json([
                    'message' => 'Transacción ya procesada previamente (Idempotente).',
                    'transaction_id' => $existingTx->transaction_id
                ], 200);
            }

            $reservation = Reservation::where('tenant_id', $tenantId)
                ->where('code', $validated['reservation_code'])
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Registrar la transacción de pasarela
            $gatewayTx = GatewayTransaction::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'gateway' => $validated['gateway'],
                    'transaction_id' => $validated['transaction_id'],
                ],
                [
                    'reservation_id' => $reservation->id,
                    'amount' => $validated['amount'],
                    'currency' => $validated['currency'],
                    'status' => 'completed',
                    'card_brand' => $validated['card_brand'] ?? 'VISA',
                    'card_last4' => $validated['card_last4'] ?? '4242',
                    'raw_payload' => $request->all(),
                ]
            );

            // 3. Crear o actualizar la cuenta por cobrar (CxC)
            $receivable = Receivable::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'reservation_id' => $reservation->id,
                    'concept' => 'Pago Web Checkout',
                ],
                [
                    'client_id' => $reservation->client_id,
                    'due_date' => now()->toDateString(),
                    'currency' => $validated['currency'],
                    'amount' => $validated['amount'],
                    'paid_amount' => 0.00,
                    'balance' => $validated['amount'],
                    'status' => 'pendiente',
                ]
            );

            // 4. Registrar el pago formal en fin_payments
            $systemUser = User::where('tenant_id', $tenantId)->first();
            $paymentCode = SequenceService::nextCode($tenantId, 'payment');

            Payment::create([
                'tenant_id' => $tenantId,
                'receivable_id' => $receivable->id,
                'code' => $paymentCode,
                'method' => 'Tarjeta',
                'currency' => $validated['currency'],
                'amount' => $validated['amount'],
                'fx_rate' => $reservation->fx_rate,
                'transaction_reference' => "{$validated['gateway']}:{$validated['transaction_id']}",
                'payment_date' => now()->toDateString(),
                'received_by' => $systemUser->id,
                'status' => 'aplicado',
                'notes' => "Pago procesado vía pasarela {$validated['gateway']} ({$validated['card_brand']} ****{$validated['card_last4']})",
            ]);

            // 5. Actualizar saldos
            $receivable->update([
                'paid_amount' => $receivable->amount,
                'balance' => 0.00,
                'status' => 'pagada',
            ]);

            $newResPaid = $reservation->paid_amount + $validated['amount'];
            $newResBalance = max(0, $reservation->total - $newResPaid);

            $reservation->update([
                'paid_amount' => $newResPaid,
                'balance' => $newResBalance,
            ]);

            AuditLogger::log('PasarelaPago', $gatewayTx->transaction_id, 'confirmar_pago_online', null, "{$validated['amount']} {$validated['currency']} para {$reservation->code}", true);

            return response()->json([
                'message' => 'Pago recibido y reserva actualizada exitosamente.',
                'data' => [
                    'reservation_code' => $reservation->code,
                    'total' => $reservation->total,
                    'paid_amount' => $reservation->paid_amount,
                    'balance' => $reservation->balance,
                    'status' => $reservation->status,
                ]
            ], 200);
        });
    }
}