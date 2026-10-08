<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $admin = User::where('tenant_id', $tenant->id)->first();
        $product = Product::where('tenant_id', $tenant->id)->first();
        $destination = Destination::where('tenant_id', $tenant->id)->where('level', 6)->first();

        // 1. Maamila Fakkeenyaa (CLI-0001)
        $client = Client::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CLI-0001'],
            [
                'id' => (string) Str::uuid(),
                'kind' => 'persona',
                'name' => 'Carlos Mendoza Paredes',
                'doc_type' => 'DNI',
                'doc_number' => '45892134',
                'email' => 'carlos.mendoza@gmail.com',
                'phone' => '+51984123456',
                'country' => 'Perú',
                'city' => 'Lima',
                'segment_code' => 'Retail',
                'owner_id' => $admin->id,
            ]
        );

        // 2. Lead Fakkeenyaa (LEAD-0001)
        Lead::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'LEAD-0001'],
            [
                'id' => (string) Str::uuid(),
                'full_name' => 'Mariana Gomez Sanchez',
                'email' => 'mariana.gomez@outlook.com',
                'phone' => '+51998877665',
                'channel_code' => 'WhatsApp',
                'source_code' => 'WhatsApp',
                'interest_code' => 'Tour',
                'destination_id' => $destination?->id,
                'product_id' => $product?->id,
                'travel_date' => '2026-11-15',
                'pax' => 2,
                'status' => 'nuevo',
                'score' => 56,
                'owner_id' => $admin->id,
                'notes' => 'Gareen kun baabura Expedition dabalatee tajaajila barbaada.',
            ]
        );

        // Sadarkaalee Daandii Gurgurtaa (Pipeline Stages)
        $stages = [
            ['code' => 'calificacion', 'name' => 'Calificación', 'prob' => 20, 'closed' => false],
            ['code' => 'descubrimiento', 'name' => 'Descubrimiento', 'prob' => 35, 'closed' => false],
            ['code' => 'cotizacion', 'name' => 'Cotización', 'prob' => 55, 'closed' => false],
            ['code' => 'negociacion', 'name' => 'Negociación', 'prob' => 70, 'closed' => false],
            ['code' => 'ganada', 'name' => 'Ganada', 'prob' => 100, 'closed' => true],
            ['code' => 'perdida', 'name' => 'Perdida', 'prob' => 0, 'closed' => true],
        ];

        foreach ($stages as $i => $s) {
            \App\Models\PipelineStage::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $s['code']],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $s['name'],
                    'default_probability' => $s['prob'],
                    'sort_order' => $i + 1,
                    'is_closed' => $s['closed'],
                ]
            );
        }

        // Carraa Fakkeenyaa (OPP-0001)
        \App\Models\Opportunity::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'OPP-0001'],
            [
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'title' => 'Viaje Familiar Machu Picchu 2 Pax',
                'stage' => 'descubrimiento',
                'amount' => 560.00,
                'currency' => 'USD',
                'probability' => 35,
                'expected_close_date' => '2026-10-30',
                'owner_id' => $admin->id,
                'pax' => 2,
                'product_id' => $product?->id,
                'origin' => 'Conversión de lead',
            ]
        );
    }
}