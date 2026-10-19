<?php

namespace App\Http\Controllers\Api\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use App\Models\OpsService;
use App\Models\Payable;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function getExecutiveSummary(Request $request): JsonResponse
    {
        $tenantId = app('current_tenant_id');

        // 1. Métricas de Ventas y Reservas
        $reservations = Reservation::where('tenant_id', $tenantId);
        $totalSales = (clone $reservations)->sum('total');
        $totalPaid = (clone $reservations)->sum('paid_amount');
        $totalPendingReceivables = (clone $reservations)->sum('balance');
        $countReservations = (clone $reservations)->count();

        // 2. Costos Operativos y CxP a Proveedores
        $totalOperationalCost = OpsService::where('tenant_id', $tenantId)->sum('cost');
        $totalPayablesBalance = Payable::where('tenant_id', $tenantId)
            ->whereIn('status', ['pendiente', 'parcial'])
            ->sum('balance');

        // 3. Utilidad y Margen Bruto
        $grossProfit = max(0, $totalSales - $totalOperationalCost);
        $marginPct = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 2) : 0.0;

        // 4. Embudo Comercial (Funnel)
        $totalLeads = Lead::where('tenant_id', $tenantId)->count();
        $totalQuotes = Quote::where('tenant_id', $tenantId)->count();
        $totalClients = Client::where('tenant_id', $tenantId)->count();

        $conversionRate = $totalLeads > 0 
            ? round(($countReservations / $totalLeads) * 100, 2) 
            : 0.0;

        // 5. Estado de Flujo de Caja Inmediato
        $cashflowPosition = [
            'total_inflow'   => round((float) $totalPaid, 2),
            'cxc_pending'    => round((float) $totalPendingReceivables, 2),
            'cxp_pending'    => round((float) $totalPayablesBalance, 2),
            'net_receivable' => round((float) ($totalPendingReceivables - $totalPayablesBalance), 2),
        ];

        return response()->json([
            'data' => [
                'currency' => 'USD',
                'commercial' => [
                    'total_sales'        => round((float) $totalSales, 2),
                    'total_collected'    => round((float) $totalPaid, 2),
                    'reservations_count' => $countReservations,
                    'gross_profit'       => round((float) $grossProfit, 2),
                    'margin_pct'         => $marginPct,
                ],
                'funnel' => [
                    'leads_count'     => $totalLeads,
                    'quotes_count'    => $totalQuotes,
                    'clients_count'   => $totalClients,
                    'conversion_rate' => $conversionRate,
                ],
                'cashflow' => $cashflowPosition,
                'as_of_date' => now()->toIso8601String(),
            ]
        ]);
    }
}