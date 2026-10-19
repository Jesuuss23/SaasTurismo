<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MessagingSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-travel')->first();
        if (!$tenant) return;

        app()->instance('current_tenant_id', $tenant->id);

        // 1. Plantilla Email: Confirmación de Reserva
        MessageTemplate::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'TPL-EMAIL-RESERVATION-CONFIRMED'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Confirmación de Reserva Web (Email)',
                'channel' => 'email',
                'subject' => '¡Tu viaje está confirmado! Reserva {{reservation_code}}',
                'body_template' => "Hola {{client_name}},\n\nTu reserva con código {{reservation_code}} para la fecha {{travel_date}} ha sido confirmada con éxito.\nTotal: {{currency}} {{total}}.\n\nPuedes ver tu voucher en: {{voucher_url}}\n\n¡Gracias por viajar con nosotros!",
                'variables' => ['client_name', 'reservation_code', 'travel_date', 'currency', 'total', 'voucher_url'],
                'status' => 'activo',
            ]
        );

        // 2. Plantilla WhatsApp: Alerta Rápida de Reserva
        MessageTemplate::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'TPL-WSP-RESERVATION-ALERT'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Alerta WhatsApp Reserva Confirmada',
                'channel' => 'whatsapp',
                'subject' => null,
                'body_template' => "Hola {{client_name}} 👋, confirmamos tu pago y reserva *{{reservation_code}}* para Machu Picchu. Fecha de inicio: {{travel_date}}. Descarga tu voucher aquí: {{voucher_url}}",
                'variables' => ['client_name', 'reservation_code', 'travel_date', 'voucher_url'],
                'status' => 'activo',
            ]
        );
    }
}