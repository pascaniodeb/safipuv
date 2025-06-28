<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas Generales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        @page {
            size: letter portrait;
            margin: 2cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            height: 60px;
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            font-size: 14px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th, td {
            border: 1px solid #000;
            padding: 1px;
            text-align: center;
        }

        th {
            background-color: #e6eff6;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>

    {{-- ENCABEZADO INSTITUCIONAL --}}
    <div class="header" style="text-align: center; margin-bottom: 15px;">
        <img src="{{ public_path('images/ipuv-logo.png') }}" alt="Logo IPUV" style="height: 80px; margin-bottom: 5px;">

        <h1 style="margin: 0; font-size: 18px; font-weight: bold;">IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</h1>
        <h2 style="margin: 0; font-size: 14px;">Sistema Administrativo y Financiero SAFIPUV</h2>
        <h2 style="margin: 0; font-size: 14px;">Estadísticas de Secretaría Nacional</h2>
        

        {{-- Región / Distrito / Sector --}}
        <table style="width: 100%; font-size: 12px; margin-top: 10px;">
            <tr>
                <td style="text-align: left;"><strong>Región:</strong> {{ $region }}</td>
                <td style="text-align: center;"><strong>Distrito:</strong> {{ $district }}</td>
                <td style="text-align: right;"><strong>Sector:</strong> {{ $sector }}</td>
            </tr>
        </table>
    </div>



    {{-- CUADRO 1: Pastores --}}
    <div class="section-title">1. Pastores</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 70%">Descripción</th>
                <th style="width: 25%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuadroPastores as $index => $row)
                @if($index === 0) @continue {{-- Saltar encabezado --}}
                @endif
                <tr>
                    <td>{{ $row[0] }}</td>
                    <td class="text-left">{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                </tr>
            @endforeach
        </tbody>        
    </table>

    {{-- CUADRO 2: Iglesias --}}
    <div class="section-title" style="margin-top: 30px;">2. Iglesias</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 70%">Descripción</th>
                <th style="width: 25%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuadroIglesias as $index => $row)
                @if($index === 0) @continue {{-- Saltar encabezado --}}
                @endif
                <tr>
                    <td>{{ $row[0] }}</td>
                    <td class="text-left">{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                </tr>
            @endforeach
        </tbody>        
    </table>

    {{-- CUADRO 3: Membresía --}}
    <div class="section-title" style="margin-top: 30px;">3. Membresía</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 70%">Descripción</th>
                <th style="width: 25%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuadroMembresia as $index => $row)
                @if($index === 0) @continue
                @endif
                <tr>
                    <td>{{ $row[0] }}</td>
                    <td class="text-left">{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                </tr>
            @endforeach
        </tbody>        
    </table>


</body>
</html>
