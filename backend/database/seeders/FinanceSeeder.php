<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Receivable;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        $reservation = Reservation::where('tenant_id', $tenant->id)->first();
        if (!$reservation) return;

        $admin = User::where('tenant_id', $tenant->id)->first();

        // 1. Anticipo 40% (224.00 USD - Pagado)
        $rec1 = Receivable::firstOrCreate(
            ['tenant_id' => $tenant->id, 'reservation_id' => $reservation->id, 'concept' => 'Anticipo 40%'],
            [
                'id' => (string) Str::uuid(),
                'client_id' => $reservation->client_id,
                'due_date' => '2026-10-15',
                'currency' => 'USD',
                'amount' => 224.00,
                'paid_amount' => 224.00,
                'balance' => 0.00,
                'status' => 'pagada',
            ]
        );

        // Pago del anticipo
        Payment::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'REC-2026-00001'],
            [
                'id' => (string) Str::uuid(),
                'receivable_id' => $rec1->id,
                'method' => 'Transferencia',
                'currency' => 'USD',
                'amount' => 224.00,
                'fx_rate' => 3.75,
                'transaction_reference' => 'BCP-OP-9812451',
                'payment_date' => '2026-10-08',
                'received_by' => $admin->id,
                'status' => 'aplicado',
                'notes' => 'Abono verificado en cuenta BCP Dólares',
            ]
        );

        // 2. Saldo 60% (336.00 USD - Pendiente)
        Receivable::firstOrCreate(
            ['tenant_id' => $tenant->id, 'reservation_id' => $reservation->id, 'concept' => 'Saldo 60%'],
            [
                'id' => (string) Str::uuid(),
                'client_id' => $reservation->client_id,
                'due_date' => '2026-11-05',
                'currency' => 'USD',
                'amount' => 336.00,
                'paid_amount' => 0.00,
                'balance' => 336.00,
                'status' => 'pendiente',
            ]
        );
    }
}