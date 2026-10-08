<?php

namespace Database\Seeders;

use App\Models\Allotment;
use App\Models\AllotmentDate;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AllotmentSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $provider = Provider::where('tenant_id', $tenant->id)->where('ref', 'PRV-001')->first();
        $service = Service::where('tenant_id', $tenant->id)->where('ref', 'SRV-001')->first();

        $allotment = Allotment::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'ALT-2026-001'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Bloqueo Diario Tren Expedition Noviembre 2026',
                'provider_id' => $provider->id,
                'service_id' => $service?->id,
                'type' => 'asientos',
                'release_days' => 5,
                'date_from' => '2026-11-10',
                'date_to' => '2026-11-20',
                'status' => 'activo',
                'notes' => '10 asientos garantizados por salida diaria',
            ]
        );

        $period = CarbonPeriod::create('2026-11-10', '2026-11-20');
        foreach ($period as $date) {
            AllotmentDate::firstOrCreate(
                ['allotment_id' => $allotment->id, 'date' => $date->format('Y-m-d')],
                [
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'allocated_qty' => 10,
                    'used_qty' => ($date->format('Y-m-d') === '2026-11-15') ? 2 : 0, // 2 consumidos por RES-2026-000001
                    'released_qty' => 0,
                    'unit_cost' => 65.00,
                    'status' => 'abierto',
                ]
            );
        }
    }
}