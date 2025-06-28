<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #A9A9A9;
            padding: 3px 5px;
        }

        th {
            background-color: #e6eff6;
            /* Verde */
            text-align: center;
            font-size: 10px;
        }

        td {
            font-size: 9px;
            vertical-align: middle;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* NUEVO: para totales numéricos */
        .no-border td {
            border: none;
        }

        h2,
        h3 {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    @php
        $categorias = [
            1 => 'DIEZMOS',
            2 => 'EL PODER DEL UNO',
            3 => 'SEDE NACIONAL',
            4 => 'CONVENCIÓN DISTRITAL',
            5 => 'CONVENCIÓN REGIONAL',
            6 => 'CONVENCIÓN NACIONAL',
            7 => 'ÚNICA SECTORIAL',
            8 => 'CAMPAMENTO DE RETIROS',
            9 => 'ABISOP',
        ];

        $convencionNombre = match ($convencionId) {
            4 => 'DISTRITAL',
            5 => 'REGIONAL',
            6 => 'NACIONAL',
            default => null,
        };

        // Insertamos "CONVENCIÓN" como columna virtual 999
        $categorias = collect($categorias)
            ->except([4, 5, 6])
            ->slice(0, 3, true)
            ->put(999, 'CONVENCIÓN')
            ->union(
                collect($categorias)
                    ->except([4, 5, 6])
                    ->slice(3),
            )
            ->toArray();
    @endphp

    <h1 style="text-align:center">
        <strong>IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</strong>
    </h1>

    <h2 style="text-align:center; margin: 0;">
        <strong>Sistema Administrativo y Financiero (SAFIPUV)</strong> <br>
        <strong>Reporte Mensual de Tesorería Sectorial</strong> <br>
    </h2>

    <p style="text-align:center; margin-top: 10px; margin-bottom: 20px; font-size: 12px;">
        <strong>Mes:</strong> {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }} |
        <strong>Sector:</strong> {{ $sectorNombre }}
    </p>


    <table>
        <thead>
            <tr style="border-top: 1px solid #A9A9A9;">
                <th>#</th>
                <th class="text-center">IGLESIA</th>
                <th class="text-center">PASTOR</th>
                @foreach ($categorias as $catId => $label)
                    @if ($catId === 1 || $catId === 9)
                        <th>{{ $label }}</th>
                    @elseif ($catId === 999)
                        <th>CONVENCIÓN<br><span style="font-size:9px;">{{ $convencionNombre }}</span></th>
                    @else
                        <th>
                            @foreach (explode(' ', $label) as $word)
                                {{ $word }}<br>
                            @endforeach
                        </th>
                    @endif
                @endforeach
                <th><strong>TOTAL</strong></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($registrosCombinados as $i => $registro)
                @php
                    $reportId = $registro['report']->id;
                    $row = collect();

                    // Preparamos totales individuales por categoría
                    foreach ($categorias as $catId => $label) {
                        $realId = $catId === 999 ? $convencionId : $catId;
                        $key = $reportId . '.' . $realId;
                        $row[$realId] = $totales[$key] ?? 0;
                    }

                    $cat = fn($id) => number_format($row[$id] ?? 0, 2, ',', '.');
                    $totalIglesia = $totalesGenerales[$reportId] ?? 0;

                    $pastor = $registro['report']->pastor;
                    $nombreCorto = '—';
                    if ($pastor?->name && $pastor?->lastname) {
                        $nombre = explode(' ', trim($pastor->name));
                        $apellido = explode(' ', trim($pastor->lastname));
                        $nombreCorto = $nombre[0] . ' ' . $apellido[0];
                    }
                @endphp

                <tr style="border-top: 1px solid #A9A9A9;">
                    <td>{{ $i + 1 }}</td>
                    @php
                        $typeId = $pastor?->type_id;
                        $sufijo = match ($typeId) {
                            2 => ' (PASTOR ADJUNTO)',
                            3 => ' (PASTOR ASISTENTE)',
                            default => '',
                        };

                        $nombreIglesia = $registro['church_id']
                            ? \Illuminate\Support\Str::limit($registro['report']->church?->name . $sufijo, 30)
                            : 'SIN IGLESIA';
                    @endphp
                    <td class="text-left">{{ $nombreIglesia }}</td>

                        <td class="text-left">{{ $nombreCorto }}</td>
                        @foreach (array_keys($categorias) as $catId)
                            @php
                                $realId = $catId === 999 ? $convencionId : $catId;
                            @endphp
                            <td class="text-right">{{ $cat($realId) }}</td>
                        @endforeach
                    <td class="text-right"><strong>{{ number_format($totalIglesia, 2, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>


        <tfoot>
            <!-- Fila 1: SUBTOTAL Bs. (original) -->
            <tr style="border-top: 1px solid #A9A9A9;">
                <th colspan="3" class="text-right">SUBTOTAL Bs.:</th>
                @php $granTotal = 0; @endphp
                @foreach (array_keys($categorias) as $catId)
                    @php
                        $realId = $catId === 999 ? $convencionId : $catId;
                        $total = 0;
                        foreach ($registrosCombinados as $registro) {
                            $reportId = $registro['report']->id;
                            $key = $reportId . '.' . $realId;
                            $total += $totales[$key] ?? 0;
                        }
                        $granTotal += $total;
                    @endphp
                    <th class="text-right">{{ number_format($total, 2, ',', '.') }}</th>
                @endforeach
                <th class="text-right"><strong>{{ number_format($granTotal, 2, ',', '.') }}</strong></th>
            </tr>

            <!-- Fila 2: TOTAL USD -->
            <tr style="border-top: 1px solid #A9A9A9;">
                <th colspan="3" class="text-right">SUBTOTAL USD (Tasa: {{ number_format($usdRate, 2, ',', '.') }}):</th>
                @foreach (array_keys($categorias) as $catId)
                    @php
                        $realId = $catId === 999 ? $convencionId : $catId;
                        $total = 0;
                        foreach ($registrosCombinados as $registro) {
                            $reportId = $registro['report']->id;
                            $key = $reportId . '.' . $realId;
                            $total += $totales[$key] ?? 0;
                        }
                        $totalUsd = $total / $usdRate;
                    @endphp
                    <th class="text-right">{{ number_format($totalUsd, 2, ',', '.') }}</th>
                @endforeach
                <th class="text-right"><strong>{{ number_format($granTotal / $usdRate, 2, ',', '.') }}</strong></th>
            </tr>

            <!-- Fila 3: TOTAL COP -->
            <tr style="border-top: 1px solid #A9A9A9;">
                <th colspan="3" class="text-right">SUBTOTAL COP (Tasa: {{ number_format($copRate, 2, ',', '.') }}):</th>
                @foreach (array_keys($categorias) as $catId)
                    @php
                        $realId = $catId === 999 ? $convencionId : $catId;
                        $total = 0;
                        foreach ($registrosCombinados as $registro) {
                            $reportId = $registro['report']->id;
                            $key = $reportId . '.' . $realId;
                            $total += $totales[$key] ?? 0;
                        }
                        $totalCop = $total * $copRate;
                    @endphp
                    <th class="text-right">{{ number_format($totalCop, 2, ',', '.') }}</th>
                @endforeach
                <th class="text-right"><strong>{{ number_format($granTotal * $copRate, 2, ',', '.') }}</strong></th>
            </tr>
        </tfoot>


    </table>
{{--
    <h3 style="margin-top:10px;">Tasas de Cambio</h3>

    <table style="width: 60%; margin-bottom: 10px; text-align: center;">
        <thead>
            <tr>
                <th class="text-center">Dólares (USD)</th>
                <th class="text-center">Pesos (COP)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($usdRate, 2, ',', '.') }}</td>
                <td>{{ number_format($copRate, 3, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

--}}



    {{-- CONTENEDOR de Ingresos Globales + Deducción Sectorial --}}
    <table style="width: 100%; border: none; margin-top: 20px;">
        <tr>
            {{-- INGRESOS GLOBALES --}}
            <td style="width: 48%; vertical-align: top; border: none;">
                <h2>Ingresos Globales en Tesorería Sectorial</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">DESCRIPCIÓN</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL Bs.</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL USD</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL COP</th>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #A9A9A9; padding: 4px;"></th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: 1.00</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($usdRate, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($copRate, 2, ',', '.') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalGlobal = 0; @endphp
                        @foreach (array_keys($categorias) as $catId)
                            @php
                                $realId = $catId === 999 ? $convencionId : $catId;
                                $suma = 0;
                                foreach ($registrosCombinados as $registro) {
                                    $reportId = $registro['report']->id;
                                    $key = $reportId . '.' . $realId;
                                    $suma += $totales[$key] ?? 0;
                                }
                                $totalGlobal += $suma;
                                
                                // Calcular conversiones
                                $sumaUSD = $usdRate > 0 ? $suma / $usdRate : 0;
                                $sumaCOP = $copRate > 0 ? $suma * $copRate : 0;
                            @endphp
                            <tr>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">
                                    @if ($catId === 999)
                                        CONVENCIÓN – {{ $convencionNombre }}
                                    @else
                                        {{ $categorias[$catId] }}
                                    @endif
                                </td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($suma, 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($sumaUSD, 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($sumaCOP, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        @php
                            $totalUSD = $usdRate > 0 ? $totalGlobal / $usdRate : 0;
                            $totalCOP = $copRate > 0 ? $totalGlobal * $copRate : 0;
                        @endphp

                        <tr style="background-color: #f9f9f9;">
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">TOTAL GENERAL</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalGlobal, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalUSD, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalCOP, 2, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>
            </td>

            <td style="width: 4%; border: none;"></td> {{-- Espacio entre columnas --}}

            {{-- DEDUCCIÓN SECTORIAL --}}
            <td style="width: 48%; vertical-align: top; border: none;">
                <h2>Deducción Sectorial</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">DESCRIPCIÓN</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL Bs.</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL USD</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL COP</th>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #A9A9A9; padding: 4px;"></th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: 1.00</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($usdRate, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($copRate, 2, ',', '.') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalSector = 0;
                            $sectorDistribuciones = $resumenDeducciones['sectorial'] ?? [];
                        @endphp

                        @forelse ($sectorDistribuciones as $item)
                            @php
                                $montoUSD = $usdRate > 0 ? $item['monto'] / $usdRate : 0;
                                $montoCOP = $copRate > 0 ? $item['monto'] * $copRate : 0;
                            @endphp
                            <tr>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto'], 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoUSD, 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoCOP, 2, ',', '.') }}</td>
                            </tr>
                            @php $totalSector += $item['monto']; @endphp
                        @empty
                            <tr>
                                <td colspan="4" style="border: 1px solid #ddd; padding: 4px; text-align: center;">No hay deducciones registradas para el sector</td>
                            </tr>
                        @endforelse

                        @php
                            $totalSectorUSD = $usdRate > 0 ? $totalSector / $usdRate : 0;
                            $totalSectorCOP = $copRate > 0 ? $totalSector * $copRate : 0;
                        @endphp

                        <tr style="background-color: #f9f9f9;">
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">TOTAL GENERAL</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalSector, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalSectorUSD, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalSectorCOP, 2, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <br><br>

    {{-- CONTENEDOR de Deducción Distrital + Deducción Regional --}}
    <table style="width: 100%; border: none; margin-top: 10px;">
        <tr>
            {{-- DEDUCCIÓN DISTRITAL --}}
            <td style="width: 48%; vertical-align: top; border: none;">
                <h2>Deducción Distrital</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">DESCRIPCIÓN</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL Bs.</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL USD</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL COP</th>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #A9A9A9; padding: 4px;"></th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: 1.00</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($usdRate, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($copRate, 2, ',', '.') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalDistrital = 0;
                            $distritalDistribuciones = $resumenDeducciones['distrital'] ?? [];
                        @endphp

                        @forelse ($distritalDistribuciones as $item)
                            @php
                                $montoUSD = $usdRate > 0 ? $item['monto'] / $usdRate : 0;
                                $montoCOP = $copRate > 0 ? $item['monto'] * $copRate : 0;
                            @endphp
                            <tr>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto'], 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoUSD, 2, ',', '.') }}</td>
                                <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoCOP, 2, ',', '.') }}</td>
                            </tr>
                            @php $totalDistrital += $item['monto']; @endphp
                        @empty
                            <tr>
                                <td colspan="4" style="border: 1px solid #A9A9A9; padding: 4px; text-align: center;">No hay deducciones registradas para el distrito</td>
                            </tr>
                        @endforelse

                        @php
                            $totalDistritalUSD = $usdRate > 0 ? $totalDistrital / $usdRate : 0;
                            $totalDistritalCOP = $copRate > 0 ? $totalDistrital * $copRate : 0;
                        @endphp

                        <tr style="background-color: #f9f9f9;">
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">TOTAL GENERAL</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalDistrital, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalDistritalUSD, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalDistritalCOP, 2, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>
            </td>

            <td style="width: 4%; border: none;"></td> {{-- Espacio entre columnas --}}

            {{-- DEDUCCIÓN REGIONAL --}}
            <td style="width: 48%; vertical-align: top; border: none;">
                <h2>Deducción Regional</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">DESCRIPCIÓN</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL Bs.</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL USD</th>
                            <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL COP</th>
                        </tr>
                        <tr>
                            <th style="border: 1px solid #A9A9A9; padding: 4px;"></th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: 1.00</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($usdRate, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($copRate, 2, ',', '.') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalRegional = 0;
                            $regionalDistribuciones = $resumenDeducciones['regional'] ?? [];
                        @endphp

                        @forelse ($regionalDistribuciones as $item)
                            @if ($item['categoria_nombre'] === 'EL PODER DEL UNO')
                                @php
                                    $montoTotal = $item['monto'];
                                    $montoRegional = ($montoTotal * 42.55) / 100;
                                    $montoNucleo = ($montoTotal * 57.45) / 100;
                                    
                                    // Conversiones para el monto regional
                                    $montoRegionalUSD = $usdRate > 0 ? $montoRegional / $usdRate : 0;
                                    $montoRegionalCOP = $copRate > 0 ? $montoRegional * $copRate : 0;
                                    
                                    // Conversiones para el monto núcleo
                                    $montoNucleoUSD = $usdRate > 0 ? $montoNucleo / $usdRate : 0;
                                    $montoNucleoCOP = $copRate > 0 ? $montoNucleo * $copRate : 0;
                                @endphp
                                <tr>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">EL PODER DEL UNO (Tesorería Regional)</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoRegional, 2, ',', '.') }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoRegionalUSD, 2, ',', '.') }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoRegionalCOP, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">EL PODER DEL UNO (Núcleo de Estudio IBLC)</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoNucleo, 2, ',', '.') }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoNucleoUSD, 2, ',', '.') }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoNucleoCOP, 2, ',', '.') }}</td>
                                </tr>
                                @php
                                    $totalRegional += $montoRegional + $montoNucleo;
                                @endphp
                            @else
                                @php
                                    $montoUSD = $usdRate > 0 ? $item['monto'] / $usdRate : 0;
                                    $montoCOP = $copRate > 0 ? $item['monto'] * $copRate : 0;
                                @endphp
                                <tr>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto'], 2, ',', '.') }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoUSD, 2, ',', '.') }}</td>
                                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoCOP, 2, ',', '.') }}</td>
                                </tr>
                                @php
                                    $totalRegional += $item['monto'];
                                @endphp
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" style="border: 1px solid #A9A9A9; padding: 4px; text-align: center;">No hay deducciones registradas para la región</td>
                            </tr>
                        @endforelse

                        @php
                            $totalRegionalUSD = $usdRate > 0 ? $totalRegional / $usdRate : 0;
                            $totalRegionalCOP = $copRate > 0 ? $totalRegional * $copRate : 0;
                        @endphp

                        <tr style="background-color: #f9f9f9;">
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">TOTAL GENERAL</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalRegional, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalRegionalUSD, 2, ',', '.') }}</th>
                            <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalRegionalCOP, 2, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <br><br>

    {{-- DEDUCCIÓN NACIONAL (SOLA) --}}
    <h2 style="margin-top:10px;">Deducción Nacional</h2>
    <table style="width: 70%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">DESCRIPCIÓN</th>
                <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL Bs.</th>
                <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL USD</th>
                <th style="width: 20%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8; text-align: center;">TOTAL COP</th>
            </tr>
            <tr>
                <th style="border: 1px solid #A9A9A9; padding: 4px;"></th>
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: 1.00</th>
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($usdRate, 2, ',', '.') }}</th>
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: center; font-size: 10px;">Tasa: {{ number_format($copRate, 2, ',', '.') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalNacional = 0;
                $nacionalDistribuciones = $resumenDeducciones['nacional'] ?? [];
            @endphp

            @forelse ($nacionalDistribuciones as $item)
                @php
                    $montoUSD = $usdRate > 0 ? $item['monto'] / $usdRate : 0;
                    $montoCOP = $copRate > 0 ? $item['monto'] * $copRate : 0;
                @endphp
                <tr>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">{{ $item['categoria_nombre'] }}</td>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($item['monto'], 2, ',', '.') }}</td>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoUSD, 2, ',', '.') }}</td>
                    <td style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($montoCOP, 2, ',', '.') }}</td>
                </tr>
                @php $totalNacional += $item['monto']; @endphp
            @empty
                <tr>
                    <td colspan="4" style="border: 1px solid #ddd; padding: 4px; text-align: center;">No hay deducciones registradas para nacional</td>
                </tr>
            @endforelse

            @php
                $totalNacionalUSD = $usdRate > 0 ? $totalNacional / $usdRate : 0;
                $totalNacionalCOP = $copRate > 0 ? $totalNacional * $copRate : 0;
            @endphp

            <tr style="background-color: #f9f9f9;">
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: left;">TOTAL GENERAL</th>
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalNacional, 2, ',', '.') }}</th>
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalNacionalUSD, 2, ',', '.') }}</th>
                <th style="border: 1px solid #A9A9A9; padding: 4px; text-align: right;">{{ number_format($totalNacionalCOP, 2, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

</body>

</html>
