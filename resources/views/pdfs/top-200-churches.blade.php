<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td {
            border: 1px solid #444;
            padding: 5px 7px;
            font-size: 9px;
        }
        th {
            background-color: #e0ffe0;
            text-align: center;
        }
        td { vertical-align: middle; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        h1, h2, h3 { text-align: center; margin: 0; }
        h3 { margin-top: 10px; margin-bottom: 5px; }
        .small { font-size: 8px; }
    </style>
</head>
<body>

    <h1>IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</h1>
    <h1>Las 200 Iglesias con Mayor Aporte</h1>
    <p class="text-center">
        <strong>Categoría:</strong> {{ strtoupper($categoria) }} <br>
        <strong>Período:</strong>
        @switch($periodo)
            @case('mes')
                {{ \Carbon\Carbon::createFromFormat('Y-m', $referencia)->translatedFormat('F \d\e Y') }}
                @break
            @case('trimestre')
                Trimestre {{ $referencia }}
                @break
            @case('semestre')
                Semestre {{ $referencia }}
                @break
            @case('año')
                Año {{ $referencia }}
                @break
            @default
                {{ $referencia }}
        @endswitch
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>NOMBRE IGLESIA</th>
                <th>NOMBRE SECTOR</th>
                <th class="text-right">MONTO ENVIADO (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach ($datos as $index => $fila)
                @php $total += $fila['monto']; @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $fila['church_nombre'] ?? '—' }}</td>
                    <td class="text-left">{{ $fila['sector_nombre'] ?? '—' }}</td>
                    <td class="text-right">{{ number_format($fila['monto'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3" class="text-right">TOTAL GENERAL</th>
                <th class="text-right">{{ number_format($total, 2, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

</body>
</html>
