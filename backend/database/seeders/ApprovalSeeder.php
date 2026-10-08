<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $admin = User::where('tenant_id', $tenant->id)->first();
        $quote = Quote::where('tenant_id', $tenant->id)->first();

        Approval::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'APR-2026-0001'],
            [
                'id' => (string) Str::uuid(),
                'type' => 'descuento_cotizacion',
                'target_entity' => 'Quote',
                'target_id' => $quote?->id ?? (string) Str::uuid(),
                'requester_id' => $admin->id,
                'status' => 'pendiente',
                'reason' => 'Solicitud de descuento comercial del 8% por grupo familiar',
                'snapshot' => [
                    'quote_code' => $quote?->code ?? 'COT-2026-00001',
                    'requested_discount_pct' => 8.0,
                    'current_margin_pct' => 57.14,
                ],
            ]
        );
    }
}