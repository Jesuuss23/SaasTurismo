<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Voucher de Confirmación - {{ $reservation->code }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; margin: 40px; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 15px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-start; }
        .title { font-size: 24px; font-weight: bold; color: #0f172a; margin: 0; }
        .code { font-size: 18px; color: #d97706; font-weight: 600; margin-top: 5px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .data-item { font-size: 13px; line-height: 1.6; }
        .data-item strong { color: #334155; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
        th { background-color: #f8fafc; color: #334155; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-paid { background-color: #dcfce7; color: #15803d; }
        .badge-pending { background-color: #fef3c7; color: #b45309; }
        .footer { margin-top: 40px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">QHAPAQ TRAVEL SUITE</h1>
            <div class="code">VOUCHER OFICIAL: {{ $reservation->code }}</div>
        </div>
        <div>
            @if($reservation->balance <= 0)
                <span class="badge badge-paid">PAGADO TOTAL</span>
            @else
                <span class="badge badge-pending">PENDIENTE DE PAGO</span>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Titular y Viaje</div>
        <div class="grid">
            <div class="data-item">
                <strong>Titular:</strong> {{ $reservation->client->name }}<br>
                <strong>Documento:</strong> {{ $reservation->client->doc_type }} {{ $reservation->client->doc_number }}<br>
                <strong>Contacto:</strong> {{ $reservation->client->email }} | {{ $reservation->client->phone ?? 'N/A' }}
            </div>
            <div class="data-item">
                <strong>Fecha del Servicio:</strong> {{ $reservation->travel_date_start->format('d/m/Y') }}<br>
                <strong>Total de Pasajeros:</strong> {{ $reservation->pax }} persona(s)<br>
                <strong>Estado de Reserva:</strong> {{ strtoupper($reservation->status) }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Pasajeros Acreditados</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre Completo</th>
                    <th>Documento</th>
                    <th>Condición</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservation->passengers as $idx => $p)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $p->first_name }} {{ $p->last_name }}</td>
                    <td>{{ $p->doc_type }}: {{ $p->doc_number }}</td>
                    <td>{{ $p->is_lead ? 'Titular del grupo' : 'Acompañante' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Resumen Financiero</div>
        <div class="data-item">
            <strong>Monto Total:</strong> {{ $reservation->currency }} {{ number_format($reservation->total, 2) }}<br>
            <strong>Monto Abonado:</strong> {{ $reservation->currency }} {{ number_format($reservation->paid_amount, 2) }}<br>
            <strong>Saldo Pendiente:</strong> {{ $reservation->currency }} {{ number_format($reservation->balance, 2) }}
        </div>
    </div>

    <div class="footer">
        Documento generado automáticamente por Qhapaq Travel Suite · Presentar este comprobante y documento de identidad al momento de abordar o tomar el servicio.
    </div>
</body>
</html>