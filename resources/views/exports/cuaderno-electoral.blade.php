<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 80px 40px 50px 40px; /* 👈 más margen superior para espacio al header */
        }

        body { font-family: sans-serif; font-size: 10px; margin: 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        
        th, td {
            border: 1px solid #333;
            padding: 6px 5px;
        }

        th {
            background-color: #00ff00; /* Color verde */
            text-align: center;
            font-size: 10px;
        }

        td {
            text-align: left;
            font-size: 9px;
            vertical-align: middle;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .header img {
            height: 60px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        .subtitle {
            font-size: 12px;
            margin-top: 2px;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>

    {{-- Encabezado institucional (solo se genera una vez en la primera página) --}}
    @if ($__env->getContainer()->make('request')->input('page') == 1 || !isset($__page))
        <div class="header">
            <img src="{{ public_path('images/ipuv-logo.png') }}" alt="Logo IPUV">
            <div class="title">IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</div>
            <div class="subtitle">CUADERNO ELECTORAL DE PASTORES</div>
            <div style="margin-top:5px;">Generado el: {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}
            </div>
            <table class="info-table">
                <tr>
                    <td><strong>Región:</strong> {{ $region }}</td>
                    <td><strong>Distrito:</strong> {{ $district }}</td>
                    <td><strong>Sector:</strong> {{ $sector }}</td>
                </tr>
            </table>
        </div>
    @endif

    {{-- Tabla principal --}}
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 17%;">APELLIDOS</th>
                <th style="width: 17%;">NOMBRES</th>
                <th style="width: 12%;">CÉDULA</th>
                <th style="width: 10%;">CÓDIGO</th>
                <th style="width: 10%;">LICENCIA</th>
                <th style="width: 31%;">REGISTRE LAS VECES QUE EL PASTOR HA VOTADO</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $key => $value)
                        @if ($key === 6)
                            <td style="width: 31%;">{{ $value }}</td>
                        @else
                            <td>{{ $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
