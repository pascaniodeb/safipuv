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
            font-size: 9px;
        }

        th {
            background-color: #e6eff6;
            text-align: center;
        }

        td {
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        h1,
        h2,
        h3 {
            text-align: center;
            margin: 0;
        }

        .no-border td {
            border: none;
        }
    </style>
</head>

<body>

    <h1 style="text-align:center">
        <strong>IGLESIA PENTECOSTAL UNIDA DE VENEZUELA</strong>
    </h1>

    <h2 style="text-align:center">
        <strong>Sistema Administrativo y Financiero (SAFIPUV)</strong> <br>
        <strong>Reporte Mensual de Tesorería Nacional</strong> <br>
    </h2>

    <p style="text-align:center; margin-top: 10px; margin-bottom: 10px; font-size: 12px;">
        <strong>Mes:</strong> {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}
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
                <th class="text-center">SEDE NACIONAL</th>
                <th class="text-center">CONVENCIÓN NACIONAL</th>
                <th class="text-center">UNICA SECTORIAL</th>
                <th class="text-center">ABISOP</th>
                <th class="text-center">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Variables para acumular totales por divisa
                $totalesPorCategoria = [
                    1 => ['bs' => 0, 'usd' => 0, 'cop' => 0], // DIEZMOS
                    2 => ['bs' => 0, 'usd' => 0, 'cop' => 0], // EL PODER DEL UNO
                    3 => ['bs' => 0, 'usd' => 0, 'cop' => 0], // SEDE NACIONAL
                    6 => ['bs' => 0, 'usd' => 0, 'cop' => 0], // CONVENCIÓN NACIONAL
                    7 => ['bs' => 0, 'usd' => 0, 'cop' => 0], // UNICA SECTORIAL
                    9 => ['bs' => 0, 'usd' => 0, 'cop' => 0]  // ABISOP
                ];
                $granTotal = 0;
                $granTotalUSD = 0;
                $granTotalCOP = 0;
            @endphp

            @foreach ($sectoresResumen as $index => $sector)
                @php
                    $sectorId = $sector['id'] ?? null;
                    $tasaUsd = $sector['usd_rate'] ?? null;
                    $tasaCop = $sector['cop_rate'] ?? null;

                    $diezmos = $sector['categorias'][1] ?? 0;
                    $poderUno = $sector['categorias'][2] ?? 0;
                    $sedeNac = $sector['categorias'][3] ?? 0;
                    $convencion = $sector['categorias'][6] ?? 0;
                    $unicaSec = $sector['categorias'][7] ?? 0;
                    $abisop = $sector['categorias'][9] ?? 0;

                    // Solo calcular conversiones si hay tasas REALES registradas
                    $tieneTaskaUSD = $tasaUsd && $tasaUsd > 1;
                    $tieneTaskaCOP = $tasaCop && $tasaCop > 1;
                    
                    if ($tieneTaskaUSD) {
                        // USD: DIVISIÓN (Bs → USD)
                        $diezmosUSD = $diezmos / $tasaUsd;
                        $poderUnoUSD = $poderUno / $tasaUsd;
                        $sedeNacUSD = $sedeNac / $tasaUsd;
                        $convencionUSD = $convencion / $tasaUsd;
                        $unicaSecUSD = $unicaSec / $tasaUsd;
                        $abisopUSD = $abisop / $tasaUsd;
                    } else {
                        $diezmosUSD = $poderUnoUSD = $sedeNacUSD = $convencionUSD = $unicaSecUSD = $abisopUSD = 0;
                    }
                    
                    if ($tieneTaskaCOP) {
                        // COP: MULTIPLICACIÓN (Bs → COP)
                        $diezmosCOP = $diezmos * $tasaCop;
                        $poderUnoCOP = $poderUno * $tasaCop;
                        $sedeNacCOP = $sedeNac * $tasaCop;
                        $convencionCOP = $convencion * $tasaCop;
                        $unicaSecCOP = $unicaSec * $tasaCop;
                        $abisopCOP = $abisop * $tasaCop;
                    } else {
                        $diezmosCOP = $poderUnoCOP = $sedeNacCOP = $convencionCOP = $unicaSecCOP = $abisopCOP = 0;
                    }

                    $total = $diezmos + $poderUno + $sedeNac + $convencion + $unicaSec + $abisop;
                    $totalUSD = $diezmosUSD + $poderUnoUSD + $sedeNacUSD + $convencionUSD + $unicaSecUSD + $abisopUSD;
                    $totalCOP = $diezmosCOP + $poderUnoCOP + $sedeNacCOP + $convencionCOP + $unicaSecCOP + $abisopCOP;

                    // Acumular SIEMPRE los Bs, pero USD/COP solo si hay tasas
                    $totalesPorCategoria[1]['bs'] += $diezmos;
                    $totalesPorCategoria[2]['bs'] += $poderUno;
                    $totalesPorCategoria[3]['bs'] += $sedeNac;
                    $totalesPorCategoria[6]['bs'] += $convencion;
                    $totalesPorCategoria[7]['bs'] += $unicaSec;
                    $totalesPorCategoria[9]['bs'] += $abisop;
                    $granTotal += $total;
                    
                    // Solo acumular USD/COP si el sector tiene tasas registradas
                    if ($tieneTaskaUSD) {
                        $totalesPorCategoria[1]['usd'] += $diezmosUSD;
                        $totalesPorCategoria[2]['usd'] += $poderUnoUSD;
                        $totalesPorCategoria[3]['usd'] += $sedeNacUSD;
                        $totalesPorCategoria[6]['usd'] += $convencionUSD;
                        $totalesPorCategoria[7]['usd'] += $unicaSecUSD;
                        $totalesPorCategoria[9]['usd'] += $abisopUSD;
                        $granTotalUSD += $totalUSD;
                    }
                    
                    if ($tieneTaskaCOP) {
                        $totalesPorCategoria[1]['cop'] += $diezmosCOP;
                        $totalesPorCategoria[2]['cop'] += $poderUnoCOP;
                        $totalesPorCategoria[3]['cop'] += $sedeNacCOP;
                        $totalesPorCategoria[6]['cop'] += $convencionCOP;
                        $totalesPorCategoria[7]['cop'] += $unicaSecCOP;
                        $totalesPorCategoria[9]['cop'] += $abisopCOP;
                        $granTotalCOP += $totalCOP;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $sector['sector'] }}</td>
                    <td class="text-center">
                        {{ $tieneTaskaUSD ? number_format($tasaUsd, 2, ',', '.') : '—' }}
                    </td>
                    <td class="text-center">
                        {{ $tieneTaskaCOP ? number_format($tasaCop, 3, ',', '.') : '—' }}
                    </td>
                    <td class="text-right">{{ number_format($diezmos, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($poderUno, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($sedeNac, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($convencion, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($unicaSec, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($abisop, 2, ',', '.') }}</td>
                    <td class="text-right"><strong>{{ number_format($total, 2, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            {{-- Fila 1: TOTALES Bs. --}}
            <tr style="border-top: 1px solid #A9A9A9;">
                <th colspan="4" class="text-right">TOTALES Bs.:</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[1]['bs'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[2]['bs'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[3]['bs'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[6]['bs'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[7]['bs'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[9]['bs'], 2, ',', '.') }}</th>
                <th class="text-right"><strong>{{ number_format($granTotal, 2, ',', '.') }}</strong></th>
            </tr>

            {{-- Fila 2: TOTALES USD --}}
            <tr style="border-top: 1px solid #A9A9A9;">
                <th colspan="4" class="text-right">TOTALES USD:</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[1]['usd'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[2]['usd'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[3]['usd'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[6]['usd'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[7]['usd'], 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[9]['usd'], 2, ',', '.') }}</th>
                <th class="text-right"><strong>{{ number_format($granTotalUSD, 2, ',', '.') }}</strong></th>
            </tr>

            {{-- Fila 3: TOTALES COP --}}
            <tr style="border-top: 1px solid #A9A9A9;">
                <th colspan="4" class="text-right">TOTALES COP:</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[1]['cop'], 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[2]['cop'], 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[3]['cop'], 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[6]['cop'], 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[7]['cop'], 0, ',', '.') }}</th>
                <th class="text-right">{{ number_format($totalesPorCategoria[9]['cop'], 0, ',', '.') }}</th>
                <th class="text-right"><strong>{{ number_format($granTotalCOP, 0, ',', '.') }}</strong></th>
            </tr>
        </tfoot>
    </table>

    <table style="width: 100%; border: none; margin-top: 10px;">
        <tr>
            <td style="width: 45%; vertical-align: top; border: none; padding-right: 10px;">
                {{-- CUADRO 2: DESCUENTO AUTOMÁTICO DE DIEZMOS --}}
                <h3 class="text-left">1. DESCUENTO AUTOMÁTICO DIEZMOS</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                            <th class="text-center" style="width: 45%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                            <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Totales en las 3 divisas
                            $totalDiezmosBs = $totalesPorCategoria[1]['bs'] ?? 0;
                            $totalDiezmosUSD = $totalesPorCategoria[1]['usd'] ?? 0;
                            $totalDiezmosCOP = $totalesPorCategoria[1]['cop'] ?? 0;
                            
                            $descuentoSectoresBs = $totalDiezmosBs * 0.25;
                            $descuentoDistritosBs = $totalDiezmosBs * 0.075;
                            $descuentoRegionesBs = $totalDiezmosBs * 0.135;
                            
                            $descuentoSectoresUSD = $totalDiezmosUSD * 0.25;
                            $descuentoDistritosUSD = $totalDiezmosUSD * 0.075;
                            $descuentoRegionesUSD = $totalDiezmosUSD * 0.135;
                            
                            $descuentoSectoresCOP = $totalDiezmosCOP * 0.25;
                            $descuentoDistritosCOP = $totalDiezmosCOP * 0.075;
                            $descuentoRegionesCOP = $totalDiezmosCOP * 0.135;
                        @endphp
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">SECTORES</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoSectoresBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoSectoresUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoSectoresCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">2</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">DISTRITO</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoDistritosBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoDistritosUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoDistritosCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">3</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">REGIONES</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL DESCONTADO</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">
                                {{ number_format($descuentoSectoresBs + $descuentoDistritosBs + $descuentoRegionesBs, 2, ',', '.') }}
                            </th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">
                                {{ number_format($descuentoSectoresUSD + $descuentoDistritosUSD + $descuentoRegionesUSD, 2, ',', '.') }}
                            </th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">
                                {{ number_format($descuentoSectoresCOP + $descuentoDistritosCOP + $descuentoRegionesCOP, 0, ',', '.') }}
                            </th>
                        </tr>
                    </tbody>
                </table>

                {{-- CUADRO 3: DESCUENTO AUTOMÁTICO EL PODER DEL UNO --}}
                <h3 class="text-left" style="margin-top: 10px">2. DESCUENTO AUTOMÁTICO EL PODER DEL UNO</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                            <th class="text-center" style="width: 45%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                            <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalPoderUnoBs = $totalesPorCategoria[2]['bs'] ?? 0;
                            $totalPoderUnoUSD = $totalesPorCategoria[2]['usd'] ?? 0;
                            $totalPoderUnoCOP = $totalesPorCategoria[2]['cop'] ?? 0;
                            
                            $descuentoSectoresPOUBs = $totalPoderUnoBs * 0.15;
                            $descuentoDistritosPOUBs = $totalPoderUnoBs * 0.085;
                            $descuentoRegionesTesoreriaBs = $totalPoderUnoBs * 0.0765;
                            $descuentoRegionesNucleoBs = $totalPoderUnoBs * 0.1033;
                            
                            $descuentoSectoresPOUUSD = $totalPoderUnoUSD * 0.15;
                            $descuentoDistritosPOUUSD = $totalPoderUnoUSD * 0.085;
                            $descuentoRegionesTesoreriaUSD = $totalPoderUnoUSD * 0.0765;
                            $descuentoRegionesNucleoUSD = $totalPoderUnoUSD * 0.1033;
                            
                            $descuentoSectoresPOUCOP = $totalPoderUnoCOP * 0.15;
                            $descuentoDistritosPOUCOP = $totalPoderUnoCOP * 0.085;
                            $descuentoRegionesTesoreriaCOP = $totalPoderUnoCOP * 0.0765;
                            $descuentoRegionesNucleoCOP = $totalPoderUnoCOP * 0.1033;
                        @endphp
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">SECTORES</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoSectoresPOUBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoSectoresPOUUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoSectoresPOUCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">2</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">DISTRITOS</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoDistritosPOUBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoDistritosPOUUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoDistritosPOUCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">3</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">REGIONES TESORERÍA REGIONAL</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesTesoreriaBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesTesoreriaUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesTesoreriaCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">4</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">REGIONES NÚCLEO DE ESTUDIO</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesNucleoBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesNucleoUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoRegionesNucleoCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL DESCONTADO</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">
                                {{ number_format($descuentoSectoresPOUBs + $descuentoDistritosPOUBs + $descuentoRegionesTesoreriaBs + $descuentoRegionesNucleoBs, 2, ',', '.') }}
                            </th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">
                                {{ number_format($descuentoSectoresPOUUSD + $descuentoDistritosPOUUSD + $descuentoRegionesTesoreriaUSD + $descuentoRegionesNucleoUSD, 2, ',', '.') }}
                            </th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">
                                {{ number_format($descuentoSectoresPOUCOP + $descuentoDistritosPOUCOP + $descuentoRegionesTesoreriaCOP + $descuentoRegionesNucleoCOP, 0, ',', '.') }}
                            </th>
                        </tr>
                    </tbody>
                </table>

                {{-- CUADRO 4: DESCUENTO AUTOMÁTICO ÚNICA SECTORIAL --}}
                <h3 class="text-left" style="margin-top: 10px">3. DESCUENTO AUTOMÁTICO ÚNICA SECTORIAL</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                            <th class="text-center" style="width: 45%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                            <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalUnicaSectorialBs = $totalesPorCategoria[7]['bs'] ?? 0;
                            $totalUnicaSectorialUSD = $totalesPorCategoria[7]['usd'] ?? 0;
                            $totalUnicaSectorialCOP = $totalesPorCategoria[7]['cop'] ?? 0;
                            
                            $descuentoTesoreriaBs = $totalUnicaSectorialBs * 0.5;
                            $descuentoPastoresBs = $totalUnicaSectorialBs * 0.5;
                            
                            $descuentoTesoreriaUSD = $totalUnicaSectorialUSD * 0.5;
                            $descuentoPastoresUSD = $totalUnicaSectorialUSD * 0.5;
                            
                            $descuentoTesoreriaCOP = $totalUnicaSectorialCOP * 0.5;
                            $descuentoPastoresCOP = $totalUnicaSectorialCOP * 0.5;
                        @endphp
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">SECTOR - TESORERÍA SECTORIAL</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoTesoreriaBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoTesoreriaUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoTesoreriaCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">2</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">SECTOR - PASTORES</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoPastoresBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoPastoresUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($descuentoPastoresCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL DESCONTADO</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalUnicaSectorialBs, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalUnicaSectorialUSD, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalUnicaSectorialCOP, 0, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>

                {{-- CUADRO 5: DESCUENTO AUTOMÁTICO ABISOP --}}
                <h3 class="text-left" style="margin-top: 10px">4. DESCUENTO AUTOMÁTICO ABISOP</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                            <th class="text-center" style="width: 45%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                            <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalAbisopBs = $totalesPorCategoria[9]['bs'] ?? 0;
                            $totalAbisopUSD = $totalesPorCategoria[9]['usd'] ?? 0;
                            $totalAbisopCOP = $totalesPorCategoria[9]['cop'] ?? 0;
                        @endphp
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">SECTORES</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalAbisopBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalAbisopUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalAbisopCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL DESCONTADO</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalAbisopBs, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalAbisopUSD, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalAbisopCOP, 0, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>
            </td>

            {{-- SEGUNDA COLUMNA - Continúa igual pero actualizando los cálculos para usar las 3 divisas --}}
            <td style="width: 45%; vertical-align: top; border: none; padding-right: 10px;">
                {{-- CUADRO 6: INGRESOS GLOBALES EN TESORERÍA NACIONAL --}}
                <h3 class="text-left">5. INGRESOS GLOBALES EN TESORERÍA NACIONAL</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                            <th class="text-center" style="width: 45%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                            <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                            <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $ingresoDiezmosBs = ($totalesPorCategoria[1]['bs'] ?? 0) * 0.54;
                            $ingresoPoderUnoBs = ($totalesPorCategoria[2]['bs'] ?? 0) * 0.5852;
                            $ingresoSedeBs = $totalesPorCategoria[3]['bs'] ?? 0;
                            $ingresoConvencionBs = $totalesPorCategoria[6]['bs'] ?? 0;
                            
                            $ingresoDiezmosUSD = ($totalesPorCategoria[1]['usd'] ?? 0) * 0.54;
                            $ingresoPoderUnoUSD = ($totalesPorCategoria[2]['usd'] ?? 0) * 0.5852;
                            $ingresoSedeUSD = $totalesPorCategoria[3]['usd'] ?? 0;
                            $ingresoConvencionUSD = $totalesPorCategoria[6]['usd'] ?? 0;
                            
                            $ingresoDiezmosCOP = ($totalesPorCategoria[1]['cop'] ?? 0) * 0.54;
                            $ingresoPoderUnoCOP = ($totalesPorCategoria[2]['cop'] ?? 0) * 0.5852;
                            $ingresoSedeCOP = $totalesPorCategoria[3]['cop'] ?? 0;
                            $ingresoConvencionCOP = $totalesPorCategoria[6]['cop'] ?? 0;

                            $totalIngresoNacionalBs = $ingresoDiezmosBs + $ingresoPoderUnoBs + $ingresoSedeBs + $ingresoConvencionBs;
                            $totalIngresoNacionalUSD = $ingresoDiezmosUSD + $ingresoPoderUnoUSD + $ingresoSedeUSD + $ingresoConvencionUSD;
                            $totalIngresoNacionalCOP = $ingresoDiezmosCOP + $ingresoPoderUnoCOP + $ingresoSedeCOP + $ingresoConvencionCOP;
                        @endphp
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">DIEZMOS</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoDiezmosBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoDiezmosUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoDiezmosCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">2</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">EL PODER DEL UNO</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoPoderUnoBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoPoderUnoUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoPoderUnoCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">3</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">SEDE NACIONAL</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoSedeBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoSedeUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoSedeCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">4</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">CONVENCIÓN NACIONAL</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoConvencionBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoConvencionUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($ingresoConvencionCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL INGRESOS EN TESORERÍA NACIONAL</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalIngresoNacionalBs, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalIngresoNacionalUSD, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalIngresoNacionalCOP, 0, ',', '.') }}</th>
                        </tr>
                </tbody>
            </table>

            {{-- CUADRO 7: TASA DE CAMBIO OFICIAL USD --}}
            <h3 class="text-left" style="margin-top: 10px">6. TASA DE CAMBIO OFICIAL USD</h3>
            <table style="width: 100%; margin-top: 5px; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50%; border: 1px solid #A9A9A9; padding: 3px;">MONEDA</th>
                        <th class="text-center" style="width: 50%; border: 1px solid #A9A9A9; padding: 3px;">TASA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">USD</td>
                        <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($usdRate ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- CUADRO 8: DEDUCCIÓN DEL FONDO NACIONAL --}}
            <h3 class="text-left" style="margin-top: 10px">7. DEDUCCIÓN DEL FONDO NACIONAL</h3>
            @php
                $diezmosTotalBs = $totalesPorCategoria[1]['bs'] ?? 0;
                $diezmosTotalUSD = $totalesPorCategoria[1]['usd'] ?? 0;
                $diezmosTotalCOP = $totalesPorCategoria[1]['cop'] ?? 0;
                
                $baseTesoreriaNacionalBs = $diezmosTotalBs * 0.54; // 54% para Tesorería Nacional
                $baseTesoreriaNacionalUSD = $diezmosTotalUSD * 0.54;
                $baseTesoreriaNacionalCOP = $diezmosTotalCOP * 0.54;

                $deduccionAbisopBs = $baseTesoreriaNacionalBs * 0.2;
                $deduccionAyudasBs = $baseTesoreriaNacionalBs * 0.05;
                $totalDeduccionesNacionalesBs = $deduccionAbisopBs + $deduccionAyudasBs;
                
                $deduccionAbisopUSD = $baseTesoreriaNacionalUSD * 0.2;
                $deduccionAyudasUSD = $baseTesoreriaNacionalUSD * 0.05;
                $totalDeduccionesNacionalesUSD = $deduccionAbisopUSD + $deduccionAyudasUSD;
                
                $deduccionAbisopCOP = $baseTesoreriaNacionalCOP * 0.2;
                $deduccionAyudasCOP = $baseTesoreriaNacionalCOP * 0.05;
                $totalDeduccionesNacionalesCOP = $deduccionAbisopCOP + $deduccionAyudasCOP;
            @endphp
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                        <th class="text-center" style="width: 40%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                        <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                        <th class="text-center" style="width: 18%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                        <th class="text-center" style="width: 17%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                        <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">ABISOP</td>
                        <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($deduccionAbisopBs, 2, ',', '.') }}</td>
                        <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($deduccionAbisopUSD, 2, ',', '.') }}</td>
                        <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($deduccionAbisopCOP, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">2</td>
                        <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">Ayudas Directas por Presidencia</td>
                        <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($deduccionAyudasBs, 2, ',', '.') }}</td>
                        <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($deduccionAyudasUSD, 2, ',', '.') }}</td>
                        <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($deduccionAyudasCOP, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="background-color: #f9f9f9;">
                        <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL DEDUCCIONES DEL FONDO NACIONAL</th>
                        <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalDeduccionesNacionalesBs, 2, ',', '.') }}</th>
                        <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalDeduccionesNacionalesUSD, 2, ',', '.') }}</th>
                        <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalDeduccionesNacionalesCOP, 0, ',', '.') }}</th>
                    </tr>
                </tbody>
            </table>

            {{-- CUADRO 9: DEDUCCIÓN DE EL PODER DEL UNO EN TESORERÍA NACIONAL --}}
            <h3 class="text-left" style="margin-top: 10px">8. DEDUCCIÓN DEL PODER DEL UNO</h3>
            @php
                $poderUnoTotalBs = $totalesPorCategoria[2]['bs'] ?? 0;
                $poderUnoTotalUSD = $totalesPorCategoria[2]['usd'] ?? 0;
                $poderUnoTotalCOP = $totalesPorCategoria[2]['cop'] ?? 0;
                
                $baseTesoreriaNacionalPOUBs = $poderUnoTotalBs * 0.5852;
                $baseTesoreriaNacionalPOUUSD = $poderUnoTotalUSD * 0.5852;
                $baseTesoreriaNacionalPOUCOP = $poderUnoTotalCOP * 0.5852;

                $departamentosBs = $baseTesoreriaNacionalPOUBs * 0.75;
                $reservaBs = $baseTesoreriaNacionalPOUBs * 0.25;
                $totalPOUDeduccionesBs = $departamentosBs + $reservaBs;
                
                $departamentosUSD = $baseTesoreriaNacionalPOUUSD * 0.75;
                $reservaUSD = $baseTesoreriaNacionalPOUUSD * 0.25;
                $totalPOUDeduccionesUSD = $departamentosUSD + $reservaUSD;
                
                $departamentosCOP = $baseTesoreriaNacionalPOUCOP * 0.75;
                $reservaCOP = $baseTesoreriaNacionalPOUCOP * 0.25;
                $totalPOUDeduccionesCOP = $departamentosCOP + $reservaCOP;
            @endphp
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 3px;">#</th>
                            <th class="text-center" style="width: 40%; border: 1px solid #A9A9A9; padding: 3px;">DESCRIPCIÓN</th>
                            <th class="text-center" style="width: 20%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">Bs.</th>
                            <th class="text-center" style="width: 18%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">USD</th>
                            <th class="text-center" style="width: 17%; border: 1px solid #A9A9A9; padding: 3px; background-color: #e8f4f8;">COP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">1</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">Departamentos Nacionales</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($departamentosBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($departamentosUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($departamentosCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-center" style="border: 1px solid #A9A9A9; padding: 3px;">2</td>
                            <td class="text-left" style="border: 1px solid #A9A9A9; padding: 3px;">Fondo de Reserva</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($reservaBs, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($reservaUSD, 2, ',', '.') }}</td>
                            <td class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($reservaCOP, 0, ',', '.') }}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <th colspan="2" class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">TOTAL DEDUCIDO DE EL PODER DEL UNO</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalPOUDeduccionesBs, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalPOUDeduccionesUSD, 2, ',', '.') }}</th>
                            <th class="text-right" style="border: 1px solid #A9A9A9; padding: 3px;">{{ number_format($totalPOUDeduccionesCOP, 0, ',', '.') }}</th>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- CUADRO 9: TABLA DE DEDUCCIÓN DEPARTAMENTAL --}}
    <h3 class="text-left" style="margin-top: 10px">9. TABLA DE DEDUCCIÓN DEPARTAMENTAL</h3>
    @php
        $porcentajesDeptos = [
            'INFRAESTRUCTURA' => 12,
            'FAMILIA PASTORAL' => 12,
            'MISIONES' => 8,
            'IBLC' => 8,
            'JÓVENES' => 7,
            'DAMAS' => 7,
            'ORACIÓN' => 7,
            'EDNNA' => 7,
            'EVANGELISMO' => 7,
            'COMUNICACIONES' => 7,
            'ALABANZA' => 7,
            'HIJOS DE PASTORES' => 7,
            'JURÍDICA Y CONTABLE' => 4,
        ];

        $totalDeptosBs = 0;
        $totalDeptosUSD = 0;
        $totalDeptosCOP = 0;
    @endphp

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%; border: 1px solid #A9A9A9; padding: 4px;">#</th>
                <th class="text-center" style="width: 40%; border: 1px solid #A9A9A9; padding: 4px;">DEPARTAMENTO</th>
                <th class="text-center" style="width: 10%; border: 1px solid #A9A9A9; padding: 4px;">PORCENTAJE</th>
                <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8;">TOTAL Bs.</th>
                <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8;">TOTAL USD</th>
                <th class="text-center" style="width: 15%; border: 1px solid #A9A9A9; padding: 4px; background-color: #e8f4f8;">TOTAL COP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($porcentajesDeptos as $nombre => $porc)
                @php
                    // Calcular en las 3 divisas
                    $bs = $departamentosBs * ($porc / 100);
                    $usd = $departamentosUSD * ($porc / 100);
                    $cop = $departamentosCOP * ($porc / 100);
                    
                    $totalDeptosBs += $bs;
                    $totalDeptosUSD += $usd;
                    $totalDeptosCOP += $cop;
                @endphp
                <tr>
                    <td class="text-center" style="border: 1px solid #A9A9A9; padding: 4px;">{{ $loop->iteration }}</td>
                    <td class="text-left" style="border: 1px solid #A9A9A9; padding: 4px;">{{ $nombre }}</td>
                    <td class="text-center" style="border: 1px solid #A9A9A9; padding: 4px;">{{ $porc }}%</td>
                    <td class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">{{ number_format($bs, 2, ',', '.') }}</td>
                    <td class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">{{ number_format($usd, 2, ',', '.') }}</td>
                    <td class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">{{ number_format($cop, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <tr style="background-color: #f9f9f9;">
                <th colspan="3" class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">TOTAL DEDUCCIÓN =</th>
                <th class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">{{ number_format($totalDeptosBs, 2, ',', '.') }}</th>
                <th class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">{{ number_format($totalDeptosUSD, 2, ',', '.') }}</th>
                <th class="text-right" style="border: 1px solid #A9A9A9; padding: 4px;">{{ number_format($totalDeptosCOP, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>









    {{-- Puedes agregar luego el detalle de deducción por sector y categoría si lo deseas --}}

</body>

</html>
