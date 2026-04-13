<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Servicios — EP4 MicroCohorts</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1a1a2e;
            padding: 30px;
            background: #fff;
        }
        .header {
            text-align: left;
            border-bottom: 2px solid #00d2ff;
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: table;
            width: 100%;
        }
        .header-content {
            display: table-cell;
            vertical-align: middle;
        }
        .header h1 {
            font-size: 24px;
            color: #00d2ff;
            text-transform: uppercase;
        }
        .header p {
            color: #666;
            font-size: 11px;
            margin-top: 3px;
        }
        .meta {
            text-align: right;
            font-size: 11px;
            color: #888;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f0fbff;
            color: #0088aa;
            font-size: 11px;
            text-align: left;
            padding: 12px 10px;
            border-bottom: 2px solid #00d2ff;
            text-transform: uppercase;
        }
        td {
            padding: 10px;
            font-size: 12px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            color: #fff;
        }
        .badge-approved { background: #2ed573; }
        .badge-pending { background: #ffa502; }
        .badge-draft { background: #888; }
        .badge-rejected { background: #ff4757; }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Catálogo de Servicios</h1>
            <p>Reporte Técnico de Servicios y Gestión de Grupos</p>
        </div>
        <div class="header-content meta">
            Fecha de Reporte: {{ $generated_at }}<br>
            Total Servicios: {{ count($courses) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Servicio</th>
                <th>Proveedor</th>
                <th>Estado</th>
                <th>Grupos</th>
                <th>Clientes Totales</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($courses as $course)
            <tr>
                <td>
                    <strong>{{ $course->name }}</strong><br>
                    <small style="color: #666;">{{ Str::limit($course->description, 50) }}</small>
                </td>
                <td>{{ $course->provider ? $userNames[$course->provider_id] ?? 'Proveedor' : 'Sin asignar' }}</td>
                <td>
                    @if($course->status === 'APPROVED')
                        <span class="badge badge-approved">APROBADO</span>
                    @elseif($course->status === 'PENDING_APPROVAL')
                        <span class="badge badge-pending">PENDIENTE</span>
                    @elseif($course->status === 'REJECTED')
                        <span class="badge badge-rejected">RECHAZADO</span>
                    @else
                        <span class="badge badge-draft">BORRADOR</span>
                    @endif
                </td>
                <td>{{ count($course->groups) }}</td>
                <td>{{ $course->groups->sum('current_count') }} / {{ $course->groups->sum('max_capacity') }}</td>
                <td>{{ $course->created_at ? $course->created_at->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>EP4 MicroCohorts - Gestión Administrativa</p>
        <p>Documento generado digitalmente.</p>
    </div>
</body>
</html>
