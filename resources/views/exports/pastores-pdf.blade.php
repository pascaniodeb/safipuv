<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header img {
            height: 60px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }

        .info {
            font-size: 12px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #00ff00;
            /* Color verde */
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>

<body>

    {{-- Encabezado institucional --}}
    <div class="header">
        <img src="{{ public_path('images/ipuv-logo.png') }}" alt="Logo IPUV">

        <div class="title">{{ $title }}</div>

        <div style="margin-top: 5px;">
            Generado el: {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>

        <table style="width: 100%; margin-top: 10px; font-size: 12px;">
            <tr>
                <td><strong>Región:</strong> {{ $region ?? 'TODOS' }}</td>
                <td><strong>Distrito:</strong> {{ $district ?? 'TODOS' }}</td>
                <td><strong>Sector:</strong> {{ $sector ?? 'TODOS' }}</td>
            </tr>
        </table>
    </div>


    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}" style="text-align: center;">
                        No hay registros disponibles.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
