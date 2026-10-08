<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Rate;
use App\Models\Season;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RatesAndItemsSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $product = Product::where('tenant_id', $tenant->id)->where('code', 'CUS-MP-FULL')->first();
        if (!$product) return;

        // 1. Waayila Waggaa uumuu (Temporada Alta 2026)
        $season = Season::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Temporada Alta 2026'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'TMP-01',
                'type' => 'Alta',
                'date_from' => '2026-05-01',
                'date_to' => '2026-10-31',
                'priority' => 1,
                'factor' => 1.15,
                'status' => 'activa',
            ]
        );

        // 2. Gatii Oomishaa uumuu (Tarifa General Adulto)
        Rate::firstOrCreate(
            ['tenant_id' => $tenant->id, 'product_id' => $product->id, 'ref' => 'TAR-001'],
            [
                'id' => (string) Str::uuid(),
                'rate_type' => 'Venta',
                'segment' => 'Adulto',
                'channel' => 'Directo',
                'currency' => 'USD',
                'price_basis' => 'Por persona',
                'cost' => 120.00,
                'price' => 280.00,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'season_id' => $season->id,
                'status' => 'vigente',
            ]
        );

        // 3. Komponentoota Oomishaa tajaajila duraan uumame wajjin walqabsiisuu
        $trainService = Service::where('tenant_id', $tenant->id)->where('ref', 'SRV-001')->first();
        $ticketService = Service::where('tenant_id', $tenant->id)->where('ref', 'SRV-002')->first();

        if ($trainService) {
            ProductItem::firstOrCreate(
                ['tenant_id' => $tenant->id, 'product_id' => $product->id, 'service_id' => $trainService->id],
                [
                    'id' => (string) Str::uuid(),
                    'provider_id' => $trainService->provider_id,
                    'quantity' => 1,
                    'unit' => 'pax',
                    'sort_order' => 1,
                    'is_required' => true,
                    'is_included' => true,
                    'notes' => 'Tikkeettii Baaburaa Expedition gara Machu Picchu',
                ]
            );
        }

        if ($ticketService) {
            ProductItem::firstOrCreate(
                ['tenant_id' => $tenant->id, 'product_id' => $product->id, 'service_id' => $ticketService->id],
                [
                    'id' => (string) Str::uuid(),
                    'provider_id' => $ticketService->provider_id,
                    'quantity' => 1,
                    'unit' => 'pax',
                    'sort_order' => 2,
                    'is_required' => true,
                    'is_included' => true,
                    'notes' => 'Tikkeettii Seensaa Llaqta Machu Picchu',
                ]
            );
        }
    }
}