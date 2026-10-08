<?php

namespace Database\Seeders;

use App\Models\B2bAgency;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class B2bAgencySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $admin = User::where('tenant_id', $tenant->id)->first();

        B2bAgency::firstOrCreate(
            ['tenant_id' => $tenant->id, 'tax_id' => '20556789123'],
            [
                'id' => (string) Str::uuid(),
                'code' => 'B2B-001',
                'name' => 'Viajes Cóndor Andino S.A.C.',
                'commercial_name' => 'Cóndor Travel B2B',
                'country' => 'Perú',
                'city' => 'Lima',
                'contact_name' => 'Lucía Fernández',
                'email' => 'operaciones@condorviajes.com',
                'phone' => '+5112345678',
                'commission_pct' => 12.00,
                'currency' => 'USD',
                'credit_limit' => 5000.00,
                'credit_used' => 560.00,
                'credit_days' => 30,
                'status' => 'activo',
                'account_manager_id' => $admin->id,
                'notes' => 'Agencia mayorista aliada con tarifa neta preferencial',
            ]
        );
    }
}