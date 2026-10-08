<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        // 1. Gosa Oomishaa uumuu
        $type = ProductType::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'TOUR'],
            [
                'id' => (string) Str::uuid(),
                'requires_itinerary' => true,
                'requires_components' => true,
            ]
        );

        // 2. Ramaddii Oomishaa uumuu
        $category = Category::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Aventura y Cultura'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'CAT-1',
            ]
        );

        // 3. Iddoo fi Dhiyeessaa barbaaduu
        $destination = Destination::where('tenant_id', $tenant->id)->where('name', 'Santuario Histórico de Machu Picchu')->first();
        $provider = Provider::where('tenant_id', $tenant->id)->where('ref', 'PRV-001')->first();

        // 4. Oomisha uumuu (PRD-001)
        Product::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CUS-MP-FULL'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'PRD-001',
                'sku' => 'SKU-CUS-MP-FULL',
                'name' => 'Machu Picchu Full Day Clásico en Tren Expedition',
                'internal_name' => 'Machu Picchu Full Day',
                'product_type_id' => $type->id,
                'category_id' => $category->id,
                'primary_destination_id' => $destination?->id ?? Destination::first()->id,
                'main_provider_id' => $provider?->id,
                'status' => 'ACTIVE',
                'duration_value' => 1,
                'duration_unit' => 'días',
                'modality' => 'Grupal',
                'minimum_passengers' => 1,
                'maximum_passengers' => 15,
                'is_bookable' => true,
                'is_quoteable' => true,
                'markup_pct' => 25.00,
                'slug' => 'machu-picchu-full-day-clasico',
            ]
        );
    }
}