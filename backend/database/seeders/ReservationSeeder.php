<?php

namespace Database\Seeders;

use App\Models\Passenger;
use App\Models\Quote;
use App\Models\Reservation;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $quote = Quote::where('tenant_id', $tenant->id)->first();
        if (!$quote) return;

        $res = Reservation::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'RES-2026-000001'],
            [
                'id' => (string) Str::uuid(),
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'owner_id' => $quote->owner_id,
                'status' => 'confirmada',
                'travel_date_start' => '2026-11-15',
                'travel_date_end' => '2026-11-16',
                'pax' => 2,
                'currency' => 'USD',
                'fx_rate' => 3.75,
                'total' => 560.00,
                'paid_amount' => 224.00, // Dursee kaffalame (%40)
                'balance' => 336.00,
            ]
        );

        Passenger::firstOrCreate(
            ['tenant_id' => $tenant->id, 'reservation_id' => $res->id, 'doc_number' => '45892134'],
            [
                'id' => (string) Str::uuid(),
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza Paredes',
                'doc_type' => 'DNI',
                'is_lead' => true,
            ]
        );

        Passenger::firstOrCreate(
            ['tenant_id' => $tenant->id, 'reservation_id' => $res->id, 'doc_number' => '47120934'],
            [
                'id' => (string) Str::uuid(),
                'first_name' => 'Mariana',
                'last_name' => 'Gomez Sanchez',
                'doc_type' => 'DNI',
                'is_lead' => false,
            ]
        );
    }
}