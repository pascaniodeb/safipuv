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
        
    </style>
</head>
<body>

    <h1 style="text-align:center">
        <strong>IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</strong>
    </h1>

    <h2 style="text-align:center; margin: 0;">
        <strong>Sistema Administrativo y Financiero (SAFIPUV)</strong> <br>
        <strong>Reporte Mensual de Tesorería Regional</strong> <br>
    </h2>

    <p style="text-align:center; margin-top: 10px; margin-bottom: 20px; font-size: 12px;">
        <strong>Mes:</strong> {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }} |
        <strong>Región:</strong> {{ $regionNombre }}
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
            <th class="text-center">CONVENCIÓN REGIONAL</th>
            <th class="text-center">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @php
            $i = 1;
            // Nuevas variables para acumular totales por divisa
            $totalPorCategoria = [
                1 => ['bs' => 0, 'usd' => 0, 'cop' => 0],
                2 => ['bs' => 0, 'usd' => 0, 'cop' => 0],
                5 => ['bs' => 0, 'usd' => 0, 'cop' => 0]
            ];
            $granTotal = 0;
            $granTotalUSD = 0;
            $granTotalCOP = 0;
        @endphp

        @foreach ($sectoresResumen->sortBy('sector') as $s)
            @php
                $sectorNombre = $s['sector'];
                $sectorId = collect($sectoresConTasas)->filter(
                    fn($_, $id) => strtolower(trim($s['sector'])) === strtolower(trim($distritosConSectores[$s['distrito']][$id]['sector_nombre'] ?? ''))
                )->keys()->first();

                $tasaUsd = $sectoresConTasas[$sectorId]['usd_rate'] ?? null;
                $tasaCop = $sectoresConTasas[$sectorId]['cop_rate'] ?? null;

                $diezmos = $s['categorias'][1] ?? 0;
                $poder = $s['categorias'][2] ?? 0;
                $convencion = $s['categorias'][5] ?? 0;
                
                // Solo calcular conversiones si hay tasas REALES registradas
                $tieneTaskaUSD = $tasaUsd && $tasaUsd > 1;
                $tieneTaskaCOP = $tasaCop && $tasaCop > 1;
                
                if ($tieneTaskaUSD) {
                    // USD: DIVISIÓN (Bs → USD)
                    $diezmosUSD = $diezmos / $tasaUsd;
                    $poderUSD = $poder / $tasaUsd;
                    $convencionUSD = $convencion / $tasaUsd;
                } else {
                    $diezmosUSD = $poderUSD = $convencionUSD = 0;
                }
                
                if ($tieneTaskaCOP) {
                    // COP: MULTIPLICACIÓN (Bs → COP)
                    $diezmosCOP = $diezmos * $tasaCop;
                    $poderCOP = $poder * $tasaCop;
                    $convencionCOP = $convencion * $tasaCop;
                } else {
                    $diezmosCOP = $poderCOP = $convencionCOP = 0;
                }
                
                $total = $diezmos + $poder + $convencion;
                $totalUSD = $diezmosUSD + $poderUSD + $convencionUSD;
                $totalCOP = $diezmosCOP + $poderCOP + $convencionCOP;

                // Acumular SIEMPRE los Bs, pero USD/COP solo si hay tasas
                $totalPorCategoria[1]['bs'] += $diezmos;
                $totalPorCategoria[2]['bs'] += $poder;
                $totalPorCategoria[5]['bs'] += $convencion;
                $granTotal += $total;
                
                // Solo acumular USD/COP si el sector tiene tasas registradas
                if ($tieneTaskaUSD) {
                    $totalPorCategoria[1]['usd'] += $diezmosUSD;
                    $totalPorCategoria[2]['usd'] += $poderUSD;
                    $totalPorCategoria[5]['usd'] += $convencionUSD;
                    $granTotalUSD += $totalUSD;
                }
                
                if ($tieneTaskaCOP) {
                    $totalPorCategoria[1]['cop'] += $diezmosCOP;
                    $totalPorCategoria[2]['cop'] += $poderCOP;
                    $totalPorCategoria[5]['cop'] += $convencionCOP;
                    $granTotalCOP += $totalCOP;
                }
            @endphp
            <tr>
                <td class="text-center">{{ $i++ }}</td>
                <td class="text-left">{{ $sectorNombre }}</td>
                <td class="text-center">
                    {{ $tieneTaskaUSD ? number_format($tasaUsd, 2, ',', '.') : '—' }}
                </td>
                <td class="text-center">
                    {{ $tieneTaskaCOP ? number_format($tasaCop, 3, ',', '.') : '—' }}
                </td>
                <td class="text-right">{{ number_format($diezmos, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($poder, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($convencion, 2, ',', '.') }}</td>
                <td class="text-right"><strong>{{ number_format($total, 2, ',', '.') }}</strong></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        {{-- Fila 1: TOTALES Bs. --}}
        <tr style="border-top: 1px solid #A9A9A9;">
            <th colspan="4" class="text-right">TOTALES Bs.:</th>
            <th class="text-right">{{ number_format($totalPorCategoria[1]['bs'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalPorCategoria[2]['bs'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalPorCategoria[5]['bs'], 2, ',', '.') }}</th>
            <th class="text-right"><strong>{{ number_format($granTotal, 2, ',', '.') }}</strong></th>
        </tr>

        {{-- Fila 2: TOTALES USD --}}
        <tr style="border-top: 1px solid #A9A9A9;">
            <th colspan="4" class="text-right">TOTALES USD:</th>
            <th class="text-right">{{ number_format($totalPorCategoria[1]['usd'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalPorCategoria[2]['usd'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalPorCategoria[5]['usd'], 2, ',', '.') }}</th>
            <th class="text-right"><strong>{{ number_format($granTotalUSD, 2, ',', '.') }}</strong></th>
        </tr>

        {{-- Fila 3: TOTALES COP --}}
        <tr style="border-top: 1px solid #A9A9A9;">
            <th colspan="4" class="text-right">TOTALES COP:</th>
            <th class="text-right">{{ number_format($totalPorCategoria[1]['cop'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalPorCategoria[2]['cop'], 2, ',', '.') }}</th>
            <th class="text-right">{{ number_format($totalPorCategoria[5]['cop'], 2, ',', '.') }}</th>
            <th class="text-right"><strong>{{ number_format($granTotalCOP, 2, ',', '.') }}</strong></th>
        </tr>
    </tfoot>
</table>

{{-- CUADRO 3: DETALLE DE DEDUCCIÓN DISTRITAL AGRUPADO POR SECTOR CON SUBTOTALES --}}
<h2 style="margin-top: 12px; margin-bottom: 4px;">Detalle de Deducción Regional por Sector y Ofrenda</h2>

@php
    $totalBs = $totalUsd = $totalCop = 0;
    $totalesPorCategoria = [];
    
    // Ya es un array, no necesita toArray()
    $distritosKeys = array_keys($distritosConSectores);
    $mitad = ceil(count($distritosKeys) / 2);
    $primeraColumna = array_slice($distritosKeys, 0, $mitad);
    $segundaColumna = array_slice($distritosKeys, $mitad);
@endphp

{{-- Contenedor para las dos columnas --}}
<table style="width: 100%; border: none; margin-top: 10px;">
    <tr>
        {{-- PRIMERA COLUMNA --}}
        <td style="width: 48%; vertical-align: top; border: none;">
            @foreach ($primeraColumna as $distritoNombre)
                @php $sectores = $distritosConSectores[$distritoNombre]; @endphp
                
                <div class="borde-verde" style="margin-bottom: 15px;">
                    <h3 style="text-align: left; margin-bottom: 10px;">Distrito: {{ $distritoNombre }}</h3>

                    @foreach ($sectores as $sectorId => $sector)
                        <h4 style="margin-top: 10px; margin-bottom: 5px;">Sector: {{ $sector['sector_nombre'] }}</h4>

                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                            <thead>
                                <tr>
                                    <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px; text-align: left;">OFRENDA</th>
                                    <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Total Bs.</th>
                                    <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Total USD</th>
                                    <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Total COP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $subBs = $subUsd = $subCop = 0;
                                @endphp

                                @foreach ($sector['deducciones'] as $item)
                                    @php
                                        // Acumular por categoría
                                        $categoriaNombre = $item['categoria_nombre'];
                                        if (!isset($totalesPorCategoria[$categoriaNombre])) {
                                            $totalesPorCategoria[$categoriaNombre] = ['bs' => 0, 'usd' => 0, 'cop' => 0];
                                        }
                                    @endphp

                                    @if ($item['categoria_nombre'] === 'EL PODER DEL UNO')
                                        @php
                                            $regionalBs = $item['monto_bs'] * 0.4255;
                                            $iblcBs = $item['monto_bs'] * 0.5745;
                                            $regionalUsd = $item['monto_usd'] * 0.4255;
                                            $iblcUsd = $item['monto_usd'] * 0.5745;
                                            $regionalCop = $item['monto_cop'] * 0.4255;
                                            $iblcCop = $item['monto_cop'] * 0.5745;
                                            
                                            // Inicializar las claves si no existen
                                            if (!isset($totalesPorCategoria['EL PODER DEL UNO (Tesorería Regional)'])) {
                                                $totalesPorCategoria['EL PODER DEL UNO (Tesorería Regional)'] = ['bs' => 0, 'usd' => 0, 'cop' => 0];
                                            }
                                            if (!isset($totalesPorCategoria['EL PODER DEL UNO (Núcleo de Estudio IBLC)'])) {
                                                $totalesPorCategoria['EL PODER DEL UNO (Núcleo de Estudio IBLC)'] = ['bs' => 0, 'usd' => 0, 'cop' => 0];
                                            }
                                            
                                            // Acumular totales
                                            $totalesPorCategoria['EL PODER DEL UNO (Tesorería Regional)']['bs'] += $regionalBs;
                                            $totalesPorCategoria['EL PODER DEL UNO (Tesorería Regional)']['usd'] += $regionalUsd;
                                            $totalesPorCategoria['EL PODER DEL UNO (Tesorería Regional)']['cop'] += $regionalCop;
                                            
                                            $totalesPorCategoria['EL PODER DEL UNO (Núcleo de Estudio IBLC)']['bs'] += $iblcBs;
                                            $totalesPorCategoria['EL PODER DEL UNO (Núcleo de Estudio IBLC)']['usd'] += $iblcUsd;
                                            $totalesPorCategoria['EL PODER DEL UNO (Núcleo de Estudio IBLC)']['cop'] += $iblcCop;
                                        @endphp
                                        <tr>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">EL PODER DEL UNO (Tesorería Regional)</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($regionalBs, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($regionalUsd, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($regionalCop, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">EL PODER DEL UNO (Núcleo de Estudio IBLC)</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($iblcBs, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($iblcUsd, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($iblcCop, 2, ',', '.') }}</td>
                                        </tr>
                                        @php
                                            $subBs += $item['monto_bs'];
                                            $subUsd += $item['monto_usd'];
                                            $subCop += $item['monto_cop'];
                                        @endphp
                                    @else
                                        @php
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
                                            $subBs += $item['monto_bs'];
                                            $subUsd += $item['monto_usd'];
                                            $subCop += $item['monto_cop'];
                                        @endphp
                                    @endif
                                @endforeach

                                <tr style="background-color: #f9f9f9;">
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">SUBTOTAL</th>
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subBs, 2, ',', '.') }}</th>
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subUsd, 2, ',', '.') }}</th>
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subCop, 2, ',', '.') }}</th>
                                </tr>

                                @php
                                    $totalBs += $subBs;
                                    $totalUsd += $subUsd;
                                    $totalCop += $subCop;
                                @endphp
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endforeach
        </td>

        <td style="width: 4%; border: none;"></td> {{-- Espacio entre columnas --}}

        {{-- SEGUNDA COLUMNA --}}
        <td style="width: 48%; vertical-align: top; border: none;">
            @foreach ($segundaColumna as $distritoNombre)
                @php $sectores = $distritosConSectores[$distritoNombre]; @endphp
                
                <div class="borde-verde" style="margin-bottom: 15px;">
                    <h3 style="text-align: left; margin-bottom: 10px;">Distrito: {{ $distritoNombre }}</h3>

                    @foreach ($sectores as $sectorId => $sector)
                        <h4 style="margin-top: 10px; margin-bottom: 5px;">Sector: {{ $sector['sector_nombre'] }}</h4>

                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                            <thead>
                                <tr>
                                    <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px; text-align: left;">OFRENDA</th>
                                    <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Total Bs.</th>
                                    <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Total USD</th>
                                    <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">Total COP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $subBs = $subUsd = $subCop = 0;
                                @endphp

                                @foreach ($sector['deducciones'] as $item)
                                    @if ($item['categoria_nombre'] === 'EL PODER DEL UNO')
                                        @php
                                            $regionalBs = $item['monto_bs'] * 0.4255;
                                            $iblcBs = $item['monto_bs'] * 0.5745;
                                            $regionalUsd = $item['monto_usd'] * 0.4255;
                                            $iblcUsd = $item['monto_usd'] * 0.5745;
                                            $regionalCop = $item['monto_cop'] * 0.4255;
                                            $iblcCop = $item['monto_cop'] * 0.5745;
                                        @endphp
                                        <tr>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">EL PODER DEL UNO (Tesorería Regional)</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($regionalBs, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($regionalUsd, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($regionalCop, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">EL PODER DEL UNO (Núcleo de Estudio IBLC)</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($iblcBs, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($iblcUsd, 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($iblcCop, 2, ',', '.') }}</td>
                                        </tr>
                                        @php
                                            $subBs += $item['monto_bs'];
                                            $subUsd += $item['monto_usd'];
                                            $subCop += $item['monto_cop'];
                                        @endphp
                                    @else
                                        <tr>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_bs'], 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_usd'], 2, ',', '.') }}</td>
                                            <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto_cop'], 2, ',', '.') }}</td>
                                        </tr>
                                        @php
                                            $subBs += $item['monto_bs'];
                                            $subUsd += $item['monto_usd'];
                                            $subCop += $item['monto_cop'];
                                        @endphp
                                    @endif
                                @endforeach

                                <tr style="background-color: #f9f9f9;">
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">SUBTOTAL</th>
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subBs, 2, ',', '.') }}</th>
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subUsd, 2, ',', '.') }}</th>
                                    <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($subCop, 2, ',', '.') }}</th>
                                </tr>

                                @php
                                    $totalBs += $subBs;
                                    $totalUsd += $subUsd;
                                    $totalCop += $subCop;
                                @endphp
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endforeach
        </td>
    </tr>
</table>

{{-- TOTALES POR CATEGORÍA --}}
<h3 style="margin-top: 20px;">Deducción Regional</h3>
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
            {{-- ✅ Solo mostrar categorías que tienen valores mayores a 0 --}}
            @if($totales['bs'] > 0 || $totales['usd'] > 0 || $totales['cop'] > 0)
                <tr>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $categoria }}</td>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totales['bs'], 2, ',', '.') }}</td>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totales['usd'], 2, ',', '.') }}</td>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totales['cop'], 2, ',', '.') }}</td>
                </tr>
            @endif
        @endforeach
        
        <tr style="background-color: #f9f9f9;">
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">TOTAL GENERAL</th>
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalBs, 2, ',', '.') }}</th>
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalUsd, 2, ',', '.') }}</th>
            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalCop, 2, ',', '.') }}</th>
        </tr>
    </tbody>
</table>

</body>
</html>
