<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Gestión — {{ $group->name }}</title>
    <style>
        body { font-family: sans-serif; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #6c63ff; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #6c63ff; margin: 0; }
        .summary-box { background: #f4f7fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #eee; padding: 10px; text-align: left; font-size: 12px; }
        th { background: #6c63ff; color: white; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .footer { margin-top: 30px; font-size: 10px; text-align: center; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE SERVICIO ACTIVADO</h1>
        <p>MicroCohorts EP4 — Gestión de Proveedor</p>
    </div>

    <div class="summary-box">
        <h3>{{ $course->name }}</h3>
        <p><strong>Grupo:</strong> {{ $group->name }}</p>
        <p><strong>Proveedor:</strong> {{ $teacher->name }}</p>
        <p><strong>Estado:</strong> {{ $group->status }}</p>
        <p><strong>Clientes Inscritos:</strong> {{ $group->current_count }} / {{ $group->max_capacity }}</p>
    </div>

    <h3>Lista de Clientes (Adquisiciones Confirmadas)</h3>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Fecha de Pago</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservations as $res)
            <tr>
                <td>{{ $res->user->name }}</td>
                <td>{{ $res->user->email }}</td>
                <td>{{ $res->paid_at->format('d/m/Y H:i') }}</td>
                <td>${{ number_format($res->frozen_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">INGRESO TOTAL (LIBERADO):</td>
                <td>${{ number_format($totalRevenue, 2) }} MXN</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Documento generado por la plataforma MicroCohorts EP4</p>
        <p>Fecha de generación: {{ $generated_at }}</p>
    </div>
</body>
</html>
