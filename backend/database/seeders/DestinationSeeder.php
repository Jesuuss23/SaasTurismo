<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) {
            return;
        }

        app()->instance('current_tenant_id', $tenant->id);

        // 1. País
        $peru = Destination::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Perú'],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'D01',
                'type' => 'País',
                'level' => 0,
                'path' => 'Perú',
            ]
        );

        // 2. Departamento: Cusco
        $cusco = Destination::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Cusco', 'parent_id' => $peru->id],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'D02',
                'type' => 'Departamento',
                'level' => 2,
                'lat' => -13.531950,
                'lng' => -71.967463,
                'path' => 'Perú / Cusco',
            ]
        );

        // 3. Atracción: Machu Picchu
        Destination::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Santuario Histórico de Machu Picchu', 'parent_id' => $cusco->id],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'D03',
                'type' => 'Atracción',
                'level' => 6,
                'lat' => -13.163141,
                'lng' => -72.544963,
                'path' => 'Perú / Cusco / Santuario Histórico de Machu Picchu',
            ]
        );

        // 4. Departamento: Lima
        $lima = Destination::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Lima', 'parent_id' => $peru->id],
            [
                'id' => (string) Str::uuid(),
                'ref' => 'D04',
                'type' => 'Departamento',
                'level' => 2,
                'lat' => -12.046374,
                'lng' => -77.042793,
                'path' => 'Perú / Lima',
            ]
        );
    }
}