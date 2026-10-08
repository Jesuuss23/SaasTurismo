<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProviderAndServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        // 1. Proveedor de Tren: PeruRail
        $perurail = Provider::firstOrCreate(
            ['tenant_id' => $tenant->id, 'ruc' => '20412345678'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'PRV-001',
                'name' => 'PeruRail S.A.',
                'type' => 'Tren',
                'city' => 'Cusco',
                'contact_name' => 'Reservas Corporativas',
                'email' => 'reservas@perurail.com',
                'phone' => '+5184581414',
                'contract_start' => '2026-01-01',
                'contract_end' => '2026-12-31',
                'currency' => 'USD',
                'status' => 'activo',
            ]
        );

        // Servicio: Ticket Expedition
        Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'provider_id' => $perurail->id, 'name' => 'Tren Expedition Ollantaytambo - Machu Picchu'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'SRV-001',
                'type' => 'Tren',
                'unit' => 'pax',
                'cost' => 65.00,
                'currency' => 'USD',
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'status' => 'activo',
            ]
        );

        // 2. Proveedor de Entradas: Ministerio de Cultura
        $mincul = Provider::firstOrCreate(
            ['tenant_id' => $tenant->id, 'ruc' => '20504794637'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'PRV-002',
                'name' => 'Ministerio de Cultura - DDC Cusco',
                'type' => 'Entrada',
                'city' => 'Cusco',
                'contact_name' => 'Boletería Central',
                'contract_start' => '2026-01-01',
                'contract_end' => '2026-12-31',
                'currency' => 'PEN',
                'status' => 'activo',
            ]
        );

        // Servicio: Boleto Llaqta Machu Picchu Circuito 1/2
        Service::firstOrCreate(
            ['tenant_id' => $tenant->id, 'provider_id' => $mincul->id, 'name' => 'Boleto de Ingreso Llaqta Machu Picchu (Extranjero)'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'SRV-002',
                'type' => 'Entrada',
                'unit' => 'pax',
                'cost' => 152.00,
                'currency' => 'PEN',
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'status' => 'activo',
            ]
        );
    }
}