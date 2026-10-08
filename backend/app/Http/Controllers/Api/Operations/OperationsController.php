<?php

namespace App\Http\Controllers\Api\Operations;

use App\Domain\Shared\Services\AuditLogger;
use App\Domain\Shared\Services\SequenceService;
use App\Http\Controllers\Controller;
use App\Models\OpsOrder;
use App\Models\OpsService;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OpsOrder::with([
            'reservation.client:id,name,code',
            'services.provider:id,name,ref,type'
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('execution_date', $request->date);
        }

        return response()->json([
            'data' => $query->orderBy('execution_date', 'asc')->paginate(20)
        ]);
    }

    public function generateVoucher(Request $request, string $serviceId): JsonResponse
    {
        $tenantId = app('current_tenant_id');

        return DB::transaction(function () use ($tenantId, $serviceId) {
            $service = OpsService::findOrFail($serviceId);

            if ($service->voucher_code) {
                return response()->json([
                    'message' => 'El servicio ya cuenta con un voucher emitido.',
                    'voucher_code' => $service->voucher_code
                ], 200);
            }

            $voucherCode = SequenceService::nextCode($tenantId, 'voucher');
            $service->update([
                'voucher_code' => $voucherCode,
                'status' => 'confirmado'
            ]);

            AuditLogger::log('Voucher', $voucherCode, 'emitir', null, "Voucher para {$service->name}", true);

            return response()->json([
                'message' => 'Voucher emitido correctamente.',
                'data' => $service
            ], 200);
        });
    }

    public function show(string $id): JsonResponse
    {
        $order = OpsOrder::with([
            'reservation.client',
            'reservation.passengers',
            'services.provider'
        ])->findOrFail($id);

        return response()->json(['data' => $order]);
    }
}