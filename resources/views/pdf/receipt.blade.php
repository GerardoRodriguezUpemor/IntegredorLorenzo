<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago — EP4 MicroCohorts</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1a1a2e;
            padding: 40px;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #6c63ff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #6c63ff;
            letter-spacing: 2px;
        }
        .header p {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }
        .badge {
            display: inline-block;
            background: #27ae60;
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            color: #6c63ff;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 6px 0;
            font-weight: bold;
            color: #555;
            width: 40%;
            font-size: 13px;
        }
        .info-value {
            display: table-cell;
            padding: 6px 0;
            color: #1a1a2e;
            font-size: 13px;
        }
        .price-box {
            background: #f8f9ff;
            border: 2px solid #6c63ff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .price-amount {
            font-size: 36px;
            font-weight: bold;
            color: #6c63ff;
        }
        .price-label {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        .savings {
            background: #e8f5e9;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            margin-top: 10px;
        }
        .savings span {
            color: #27ae60;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 11px;
        }
        .receipt-id {
            font-family: monospace;
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EP4 MICROCOHORTS</h1>
        <p>Plataforma Educativa de Micro-Grupos</p>
        <div class="badge">✓ PAGO CONFIRMADO</div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Estudiante</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $student->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $student->email }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Curso</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Curso:</div>
                <div class="info-value">{{ $course->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Descripción:</div>
                <div class="info-value">{{ $course->description }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Profesor:</div>
                <div class="info-value">{{ $teacher->name }} ({{ $teacher->specialty }})</div>
            </div>
            <div class="info-row">
                <div class="info-label">Grupo:</div>
                <div class="info-value">{{ $group->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha Elegida:</div>
                <div class="info-value" style="color: #6c63ff; font-weight: bold;">
                    {{ $reservation->scheduleOption ? \Carbon\Carbon::parse($reservation->scheduleOption->proposed_date)->format('d/m/Y H:i') : 'Por confirmar' }}
                    <small style="display: block; color: #888; font-weight: normal;">(Elegida al momento de reservar)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="price-box">
        <div class="price-label">TOTAL PAGADO</div>
        <div class="price-amount">${{ number_format($reservation->frozen_price, 2) }} MXN</div>
        @if(isset($reservation->price_breakdown['saved_amount']) && $reservation->price_breakdown['saved_amount'] > 0)
        <div class="savings">
            Ahorraste <span>${{ number_format($reservation->price_breakdown['saved_amount'], 2) }}</span>
            ({{ number_format($reservation->price_breakdown['discount_percentage'], 1) }}% de descuento)
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Detalles de la Transacción</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">ID Reservación:</div>
                <div class="info-value"><span class="receipt-id">{{ $reservation->_id }}</span></div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha de Pago:</div>
                <div class="info-value">{{ $reservation->paid_at ? $reservation->paid_at->format('d/m/Y H:i:s') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Precio Base:</div>
                <div class="info-value">${{ number_format($reservation->price_breakdown['base_price'] ?? 50, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este recibo fue generado automáticamente por EP4 MicroCohorts</p>
        <p>Fecha de generación: {{ $generated_at }}</p>
        <p>Este documento es un comprobante válido de pago.</p>
    </div>
</body>
</html>
