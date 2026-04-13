<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directorio de Usuarios — EP4 MicroCohorts</title>
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
            border-bottom: 2px solid #6c63ff;
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
            color: #6c63ff;
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
            background-color: #f8f9ff;
            color: #6c63ff;
            font-size: 11px;
            text-align: left;
            padding: 12px 10px;
            border-bottom: 2px solid #6c63ff;
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
        .badge-student { background: #2ed573; }
        .badge-teacher { background: #ffa502; }
        .badge-admin { background: #ff4757; }
        
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
            <h1>Reporte de Usuarios</h1>
            <p>Directorio Administrativo del Sistema</p>
        </div>
        <div class="header-content meta">
            Fecha de Reporte: {{ $generated_at }}<br>
            Total Usuarios: {{ count($users) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Teléfono</th>
                <th>Registrado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td><small style="font-family: monospace; color: #888;">{{ $user->_id }}</small></td>
                <td><strong>{{ $user->name }}</strong></td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->role === 'CLIENT')
                        <span class="badge badge-student">CLIENTE</span>
                    @elseif($user->role === 'PROVIDER')
                        <span class="badge badge-teacher">PROVEEDOR</span>
                    @else
                        <span class="badge badge-admin">ADMIN</span>
                    @endif
                </td>
                <td>{{ $user->phone ?? 'N/A' }}</td>
                <td>{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>EP4 MicroCohorts - Sistema de Gestión Administrativa</p>
        <p>Este documento es para uso exclusivo administrativo.</p>
    </div>
</body>
</html>
