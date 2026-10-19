<?php

namespace Database\Seeders;

use App\Models\OpsService;
use App\Models\Payable;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PayableSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $service = OpsService::where('tenant_id', $tenant->id)->first();
        if (!$service || !$service->provider_id) return;

        Payable::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CXP-2026-0001'],
            [
                'id' => (string) Str::uuid(),
                'provider_id' => $service->provider_id,
                'ops_service_id' => $service->id,
                'concept' => "Liquidación tren Machu Picchu (Voucher: {$service->voucher_code})",
                'due_date' => '2026-11-30',
                'currency' => 'USD',
                'amount' => $service->cost,
                'paid_amount' => 0.00,
                'balance' => $service->cost,
                'status' => 'pendiente',
            ]
        );
    }
}