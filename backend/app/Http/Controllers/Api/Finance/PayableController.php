<?php

namespace App\Http\Controllers\Api\Finance;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\OpsService;
use App\Models\Payable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payable::with(['provider:id,name,ref', 'opsService:id,name,voucher_code']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        return response()->json([
            'data' => $query->orderBy('due_date', 'asc')->paginate(20)
        ]);
    }

    /**
     * Generar CxP a partir de un servicio operativo confirmado
     */
    public function storeFromService(Request $request, string $serviceId): JsonResponse
    {
        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($tenantId, $serviceId, $request) {
            $service = OpsService::findOrFail($serviceId);

            if (!$service->provider_id) {
                return response()->json(['error' => 'El servicio no tiene un proveedor asignado.'], 422);
            }

            $existing = Payable::where('tenant_id', $tenantId)
                ->where('ops_service_id', $service->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'La cuenta por pagar ya existe para este servicio.',
                    'data' => $existing
                ], 200);
            }

            $code = SequenceService::nextCode($tenantId, 'payable');

            $payable = Payable::create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'provider_id' => $service->provider_id,
                'ops_service_id' => $service->id,
                'concept' => "Liquidación servicio {$service->name} (Voucher: {$service->voucher_code})",
                'due_date' => $service->date->copy()->addDays(15),
                'currency' => $service->currency,
                'amount' => $service->cost,
                'paid_amount' => 0.00,
                'balance' => $service->cost,
                'status' => 'pendiente',
            ]);

            AuditLogger::log('LiquidacionCxP', $payable->code, 'generar_cxp', null, "CXP por {$payable->amount} {$payable->currency} para {$service->provider->name}", true);

            return response()->json(['data' => $payable], 201);
        });
    }

    /**
     * Registrar desembolso / pago a proveedor
     */
    public function registerDisbursement(Request $request, string $payableId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:Transferencia,Cheque,Efectivo',
            'bank_reference' => 'nullable|string|max:80',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($validated, $tenantId, $payableId, $request) {
            $payable = Payable::findOrFail($payableId);

            if ($validated['amount'] > $payable->balance) {
                return response()->json(['error' => 'El monto a desembolsar excede el saldo pendiente.'], 422);
            }

            $dspCode = SequenceService::nextCode($tenantId, 'disbursement');

            $disbursement = Disbursement::create([
                'tenant_id' => $tenantId,
                'payable_id' => $payable->id,
                'code' => $dspCode,
                'method' => $validated['method'],
                'currency' => $payable->currency,
                'amount' => $validated['amount'],
                'bank_reference' => $validated['bank_reference'] ?? null,
                'payment_date' => $validated['payment_date'],
                'paid_by' => $request->user()->id,
                'status' => 'aplicado',
                'notes' => $validated['notes'] ?? null,
            ]);

            $newPaid = $payable->paid_amount + $validated['amount'];
            $newBalance = max(0, $payable->amount - $newPaid);
            $newStatus = $newBalance <= 0 ? 'pagada' : 'parcial';

            $payable->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
                'status' => $newStatus,
            ]);

            AuditLogger::log('Desembolso', $disbursement->code, 'registrar_desembolso', null, "{$disbursement->amount} {$disbursement->currency} a {$payable->provider->name}", true);

            return response()->json([
                'message' => 'Desembolso registrado con éxito.',
                'data' => $disbursement,
                'payable_balance' => $newBalance,
            ], 201);
        });
    }
}