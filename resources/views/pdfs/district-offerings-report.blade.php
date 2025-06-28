<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td {
            border: 1px solid #A9A9A9;
            padding: 4px 5px;
            font-size: 9px;
        }
        th { background-color: #e6eff6; text-align: center; }
        td { vertical-align: middle; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        h1, h2, h3 { text-align: center; margin: 0; }
        h3 { margin-top: 20px; }
    </style>
</head>
<body>

    <h1 style="text-align:center">
        <strong>IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</strong>
    </h1>

    <h2 style="text-align:center; margin: 0;">
        <strong>Sistema Administrativo y Financiero (SAFIPUV)</strong> <br>
        <strong>Reporte Mensual de Deducción Distrital</strong> <br>
    </h2>

    <p style="text-align:center; margin-top: 10px; margin-bottom: 20px; font-size: 12px;">
        <strong>Mes:</strong> {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }} |
        <strong>Distrito:</strong> {{ $districtNombre }}
    </p>

{{-- CUADRO 1: INGRESOS POR SECTOR --}}
<h2>Ingresos Globales por Sector</h2>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>SECTOR</th>
            <th class="text-center">TASA USD</th>
            <th class="text-center">TASA COP</th>
            <th class="text-center">DIEZMOS</th>
            <th class="text-center">EL PODER DEL UNO</th>
            <th class="text-center">CONVENCIÓN DISTRITAL</th>
            <th class="text-center">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @php
            // Nuevas variables para acumular totales por divisa
            $totalesPorCategoria = [
                1 => ['bs' => 0, 'usd' => 0, 'cop' => 0],
                2 => ['bs' => 0, 'usd' => 0, 'cop' => 0],
                4 => ['bs' => 0, 'usd' => 0, 'cop' => 0]
            ];
            $granTotalBs = 0;
            $granTotalUSD = 0;
            $granTotalCOP = 0;
        @endphp

        @foreach ($sectoresConTotales as $index => $sector)
            @php
                $sectorId = $sector['sector_id'];
                $diezmo = $sector['totales'][1] ?? 0;
                $poder = $sector['totales'][2] ?? 0;
                $convencion = $sector['totales'][4] ?? 0;
                
                $tasaUsd = $sectoresConTasas[$sectorId]['usd_rate'] ?? null;
                $tasaCop = $sectoresConTasas[$sectorId]['cop_rate'] ?? null;
                
                // Solo calcular conversiones si hay tasas REALES registradas
                $tieneTaskaUSD = $tasaUsd && $tasaUsd > 1;
                $tieneTaskaCOP = $tasaCop && $tasaCop > 1;
                
                if ($tieneTaskaUSD) {
                    $diezmoUSD = $diezmo / $tasaUsd;
                    $poderUSD = $poder / $tasaUsd;
                    $convencionUSD = $convencion / $tasaUsd;
                } else {
                    $diezmoUSD = $poderUSD = $convencionUSD = 0;
                }
                
                if ($tieneTaskaCOP) {
                    $diezmoCOP = $diezmo * $tasaCop;
                    $poderCOP = $poder * $tasaCop;
                    $convencionCOP = $convencion * $tasaCop;
                } else {
                    $diezmoCOP = $poderCOP = $convencionCOP = 0;
                }
                
                $total = $diezmo + $poder + $convencion;
                $totalUSD = $diezmoUSD + $poderUSD + $convencionUSD;
                $totalCOP = $diezmoCOP + $poderCOP + $convencionCOP;

                // Acumular SIEMPRE los Bs, pero USD/COP solo si hay tasas
                $totalesPorCategoria[1]['bs'] += $diezmo;
                $totalesPorCategoria[2]['bs'] += $poder;
                $totalesPorCategoria[4]['bs'] += $convencion;
                $granTotalBs += $total;
                
                // Solo acumular USD/COP si el sector tiene tasas registradas
                if ($tieneTaskaUSD) {
                    $totalesPorCategoria[1]['usd'] += $diezmoUSD;
                    $totalesPorCategoria[2]['usd'] += $poderUSD;
                    $totalesPorCategoria[4]['usd'] += $convencionUSD;
                    $granTotalUSD += $totalUSD;
                }
                
                if ($tieneTaskaCOP) {
                    $totalesPorCategoria[1]['cop'] += $diezmoCOP;
                    $totalesPorCategoria[2]['cop'] += $poderCOP;
                    $totalesPorCategoria[4]['cop'] += $convencionCOP;
                    $granTotalCOP += $totalCOP;
                }
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-left">{{ $sector['sector_nombre'] }}</td>
                <td class="text-center">
                    {{ $tieneTaskaUSD ? number_format($tasaUsd, 2, ',', '.') : '—' }}
                </td>
                <td class="text-center">
                    {{ $tieneTaskaCOP ? number_format($tasaCop, 3, ',', '.') : '—' }}
                </td>
                <td class="text-right">{{ number_format($diezmo, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($poder, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($convencion, 2, ',', '.') }}</td>
                <td class="text-right"><strong>{{ number_format($total, 2, ',', '.') }}</strong></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        {{-- Fila 1: TOTALES Bs. --}}
        <tr>
            <th colspan="4" class="text-right">TOTALES Bs.:</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[1]['bs'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[2]['bs'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[4]['bs'], 2, ',', '.') }}</th>
            <th class="text-right"><strong>{{ number_format($granTotalBs, 2, ',', '.') }}</strong></th>
        </tr>

        {{-- Fila 2: TOTALES USD --}}
        <tr style="border-top: 1px solid #A9A9A9;">
            <th colspan="4" class="text-right">TOTALES USD:</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[1]['usd'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[2]['usd'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[4]['usd'], 2, ',', '.') }}</th>
            <th class="text-right"><strong>{{ number_format($granTotalUSD, 2, ',', '.') }}</strong></th>
        </tr>

        {{-- Fila 3: TOTALES COP --}}
        <tr style="border-top: 1px solid #A9A9A9;">
            <th colspan="4" class="text-right">TOTALES COP:</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[1]['cop'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[2]['cop'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalesPorCategoria[4]['cop'], 2, ',', '.') }}</th>
            <th class="text-right"><strong>{{ number_format($granTotalCOP, 2, ',', '.') }}</strong></th>
        </tr>
    </tfoot>
</table>


{{-- CUADRO 3: DETALLE DE DEDUCCIÓN DISTRITAL AGRUPADO POR SECTOR CON SUBTOTALES --}}
<h2 style="margin-top: 12px; margin-bottom: 4px;">Detalle de Deducción Distrital por Sector y Ofrenda</h2>

@php
    $totalBs = 0;
    $totalUsd = 0;
    $totalCop = 0;
    $totalesPorCategoria = [];

    // Agrupar deducciones por sector
    $deduccionesPorSector = collect($deduccionesDistritales)
        ->groupBy('sector_nombre')
        ->sortKeys();
        
    $sectoresArray = $deduccionesPorSector->toArray();
    $sectoresKeys = array_keys($sectoresArray);
    $mitad = ceil(count($sectoresKeys) / 2);
    $primeraColumna = array_slice($sectoresKeys, 0, $mitad);
    $segundaColumna = array_slice($sectoresKeys, $mitad);
@endphp

{{-- Contenedor para las dos columnas --}}
<table style="width: 100%; border: none; margin-top: 10px;">
    <tr>
        {{-- PRIMERA COLUMNA --}}
        <td style="width: 48%; vertical-align: top; border: none;">
            @foreach ($primeraColumna as $sectorNombre)
                @php $deducciones = $deduccionesPorSector[$sectorNombre]; @endphp
                
                <h4 style="margin-top: 15px;">Sector: {{ $sectorNombre }}</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px; text-align: left;">OFRENDA</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Bs.</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">USD</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $subtotalBs = 0;
                            $subtotalUsd = 0;
                            $subtotalCop = 0;
                        @endphp

                        @foreach ($deducciones as $item)
                            @php
                                // Acumular por categoría
                                $categoriaNombre = $item['categoria_nombre'];
                                if (!isset($totalesPorCategoria[$categoriaNombre])) {
                                    $totalesPorCategoria[$categoriaNombre] = ['bs' => 0, 'usd' => 0, 'cop' => 0];
                                }
                                $totalesPorCategoria[$categoriaNombre]['bs'] += $item['monto_bs'];
                                $totalesPorCategoria[$categoriaNombre]['usd'] += $item['monto_usd'];
                                $totalesPorCategoria[$categoriaNombre]['cop'] += $item['monto_cop'];
                            @endphp
                            <tr>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_bs'], 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_usd'], 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_cop'], 2, ',', '.') }}</td>
                            </tr>
                            @php
                                $subtotalBs += $item['monto_bs'];
                                $subtotalUsd += $item['monto_usd'];
                                $subtotalCop += $item['monto_cop'];
                            @endphp
                        @endforeach

                        <tr style="background-color: #f9f9f9;">
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">SUBTOTAL</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subtotalBs, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subtotalUsd, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subtotalCop, 2, ',', '.') }}</th>
                        </tr>

                        @php
                            $totalBs += $subtotalBs;
                            $totalUsd += $subtotalUsd;
                            $totalCop += $subtotalCop;
                        @endphp
                    </tbody>
                </table>
            @endforeach
        </td>

        <td style="width: 4%; border: none;"></td> {{-- Espacio entre columnas --}}

        {{-- SEGUNDA COLUMNA --}}
        <td style="width: 48%; vertical-align: top; border: none;">
            @foreach ($segundaColumna as $sectorNombre)
                @php $deducciones = $deduccionesPorSector[$sectorNombre]; @endphp
                
                <h4 style="margin-top: 15px;">Sector: {{ $sectorNombre }}</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px; text-align: left;">OFRENDA</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Bs.</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">USD</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $subtotalBs = 0;
                            $subtotalUsd = 0;
                            $subtotalCop = 0;
                        @endphp

                        @foreach ($deducciones as $item)
                            @php
                                // Acumular por categoría (ya se hizo en la primera columna)
                                if (!isset($totalesPorCategoria[$item['categoria_nombre']])) {
                                    $totalesPorCategoria[$item['categoria_nombre']] = ['bs' => 0, 'usd' => 0, 'cop' => 0];
                                }
                                $totalesPorCategoria[$item['categoria_nombre']]['bs'] += $item['monto_bs'];
                                $totalesPorCategoria[$item['categoria_nombre']]['usd'] += $item['monto_usd'];
                                $totalesPorCategoria[$item['categoria_nombre']]['cop'] += $item['monto_cop'];
                            @endphp
                            <tr>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_bs'], 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_usd'], 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_cop'], 2, ',', '.') }}</td>
                            </tr>
                            @php
                                $subtotalBs += $item['monto_bs'];
                                $subtotalUsd += $item['monto_usd'];
                                $subtotalCop += $item['monto_cop'];
                            @endphp
                        @endforeach

                        <tr style="background-color: #f9f9f9;">
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">SUBTOTAL</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subtotalBs, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subtotalUsd, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subtotalCop, 2, ',', '.') }}</th>
                        </tr>

                        @php
                            $totalBs += $subtotalBs;
                            $totalUsd += $subtotalUsd;
                            $totalCop += $subtotalCop;
                        @endphp
                    </tbody>
                </table>
            @endforeach
        </td>
    </tr>
</table>

{{-- TOTALES POR CATEGORÍA --}}
<h3 style="margin-top: 20px;">Deducción Distrital</h3>
<table style="width: 70%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">OFRENDA</th>
            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL Bs.</th>
            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL USD</th>
            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL COP</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($totalesPorCategoria as $categoria => $totales)
            <tr>
                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $categoria }}</td>
                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totales['bs'], 2, ',', '.') }}</td>
                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totales['usd'], 2, ',', '.') }}</td>
                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totales['cop'], 2, ',', '.') }}</td>
            </tr>
        @endforeach
        
        <tr style="background-color: #f9f9f9;">
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">TOTAL GENERAL</th>
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalBs, 2, ',', '.') }}</th>
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalUsd, 2, ',', '.') }}</th>
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalCop, 2, ',', '.') }}</th>
        </tr>
    </tbody>
</table>





</body>
</html>
