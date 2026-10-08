<?php

namespace App\Http\Controllers\Api\Finance;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function getReceivables(Request $request): JsonResponse
    {
        $query = Receivable::with(['client:id,name,code', 'reservation:id,code']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->orderBy('due_date', 'asc')->paginate(20)
        ]);
    }

    public function registerPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receivable_id' => 'required|uuid|exists:fin_receivables,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:Transferencia,Tarjeta,Efectivo,Yape,Plin',
            'transaction_reference' => 'nullable|string|max:80',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $tenantId, $request) {
            $receivable = Receivable::findOrFail($validated['receivable_id']);

            if ($validated['amount'] > $receivable->balance) {
                return response()->json([
                    'error' => 'El monto del pago excede el saldo pendiente de la cuenta por cobrar.'
                ], 422);
            }

            $ref = SequenceService::nextCode($tenantId, 'payment');

            $payment = Payment::create([
                'tenant_id' => $tenantId,
                'receivable_id' => $receivable->id,
                'code' => $ref,
                'method' => $validated['method'],
                'currency' => $receivable->currency,
                'amount' => $validated['amount'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_date' => $validated['payment_date'],
                'received_by' => $request->user()->id,
                'status' => 'aplicado',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Actualizar cuenta por cobrar
            $newPaid = $receivable->paid_amount + $validated['amount'];
            $newBalance = $receivable->amount - $newPaid;
            $receivableStatus = $newBalance <= 0 ? 'pagada' : 'parcial';

            $receivable->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
                'status' => $receivableStatus,
            ]);

            // Actualizar montos en la reserva
            $reservation = Reservation::find($receivable->reservation_id);
            if ($reservation) {
                $resPaid = $reservation->paid_amount + $validated['amount'];
                $reservation->update([
                    'paid_amount' => $resPaid,
                    'balance' => max(0, $reservation->total - $resPaid),
                ]);
            }

            AuditLogger::log('Pago', $payment->code, 'registrar_pago', null, "{$payment->amount} {$payment->currency} vía {$payment->method}", true);

            return response()->json([
                'message' => 'Pago registrado y aplicado con éxito.',
                'data' => $payment
            ], 201);
        });
    }

    public function getPayments(Request $request): JsonResponse
    {
        $query = Payment::with(['receivable.client', 'receiver:id,name,email']);
        return response()->json([
            'data' => $query->orderBy('payment_date', 'desc')->paginate(20)
        ]);
    }
}