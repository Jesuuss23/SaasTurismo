<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Rate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $client = Client::where('tenant_id', $tenant->id)->first();
        $opp = Opportunity::where('tenant_id', $tenant->id)->first();
        $admin = User::where('tenant_id', $tenant->id)->first();
        $product = Product::where('tenant_id', $tenant->id)->first();
        $rate = Rate::where('tenant_id', $tenant->id)->first();

        $quote = Quote::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'COT-2026-00001'],
            [
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'opportunity_id' => $opp?->id,
                'owner_id' => $admin->id,
                'title' => 'Cotización Tour Machu Picchu 2 Pax',
                'status' => 'enviada',
                'currency' => 'USD',
                'fx_rate' => 3.75,
                'valid_until' => '2026-10-31',
                'pax' => 2,
                'subtotal' => 560.00,
                'discount' => 0.00,
                'tax' => 0.00,
                'total' => 560.00,
                'cost_total' => 240.00,
                'profit' => 320.00,
                'margin_pct' => 57.14,
            ]
        );

        QuoteItem::firstOrCreate(
            ['tenant_id' => $tenant->id, 'quote_id' => $quote->id, 'concept' => 'Machu Picchu Full Day Clásico en Tren Expedition'],
            [
                'id' => (string) Str::uuid(),
                'product_id' => $product?->id,
                'rate_id' => $rate?->id,
                'service_date' => '2026-11-15',
                'pax' => 2,
                'quantity' => 1,
                'unit_cost' => 120.00,
                'unit_price' => 280.00,
                'total_cost' => 240.00,
                'total_price' => 560.00,
                'sort_order' => 1,
                'snapshot' => [
                    'product_name' => $product?->name,
                    'sku' => $product?->sku,
                ],
            ]
        );
    }
}