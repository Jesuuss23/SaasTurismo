<?php

namespace Database\Seeders;

use App\Models\OpsOrder;
use App\Models\OpsService;
use App\Models\Provider;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $reservation = Reservation::where('tenant_id', $tenant->id)->first();
        if (!$reservation) return;

        $trainProvider = Provider::where('tenant_id', $tenant->id)->where('ref', 'PRV-001')->first();
        $trainService = Service::where('tenant_id', $tenant->id)->where('ref', 'SRV-001')->first();

        // 1. Orden Operativa
        $order = OpsOrder::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'OP-2026-0001'],
            [
                'id' => (string) Str::uuid(),
                'reservation_id' => $reservation->id,
                'status' => 'confirmada',
                'execution_date' => $reservation->travel_date_start,
                'notes' => 'Pasajeros confirmados para circuito Machu Picchu',
            ]
        );

        // 2. Servicio Operativo con Voucher emitido
        OpsService::firstOrCreate(
            ['tenant_id' => $tenant->id, 'order_id' => $order->id, 'name' => 'Tren Expedition Ollantaytambo - Machu Picchu'],
            [
                'id' => (string) Str::uuid(),
                'service_id' => $trainService?->id,
                'provider_id' => $trainProvider?->id,
                'date' => $reservation->travel_date_start,
                'time' => '07:05:00',
                'meeting_point' => 'Estación Ollantaytambo',
                'pax' => $reservation->pax,
                'voucher_code' => 'VOU-2026-00001',
                'status' => 'confirmado',
                'cost' => 130.00,
                'currency' => 'USD',
            ]
        );
    }
}