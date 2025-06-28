<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            margin: 0.75in; /* Márgenes optimizados para carta */
            size: letter;
        }
        
        body {
            font-family: sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #333;
            padding: 0;
            margin: 0;
        }
        
        /* ENCABEZADO MEJORADO */
        .main-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
        }
        
        .church-name {
            font-size: 18px;
            font-weight: bold;
            color: #070707;
            margin-top: 16px;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .system-name {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2px;
        }
        
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 12px;
        }
        
        .report-info {
            font-size: 12px;
            color: #1f2937;
            margin-bottom: 2px;
            line-height: 1.2;
        }
        
        .generation-info {
            font-size: 10px;
            color: #53555a;
            font-style: italic;
        }
        
        /* RESTO DE ESTILOS */
        .seccion {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .seccion-titulo {
            font-size: 12px;
            font-weight: bold;
            color: #ffffff;
            padding: 4px 6px;
            margin-bottom: 0;
            text-align: center;
            margin-left: 1cm; /* Centrar con márgenes laterales de 1cm */
            margin-right: 1cm;
        }
        
        .seccion-titulo.ingresos {
            background-color: #16a34a;
        }
        
        .seccion-titulo.egresos {
            background-color: #dc2626;
        }
        
        .seccion-titulo.saldo {
            background-color: #2563eb;
        }
        
        .tabla {
            width: calc(100% - 2cm); /* Dejar 1cm de margen a cada lado */
            margin: 0 1cm; /* Centrar con márgenes laterales de 1cm */
            border-collapse: collapse;
            border: 1px solid #d1d5db;
            font-size: 9px;
        }
        
        .tabla th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 4px 2px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }
        
        .tabla td {
            border: 1px solid #d1d5db;
            padding: 4px 2px;
            font-size: 9px;
        }
        
        .tabla .codigo {
            text-align: center;
            width: 12%;
            font-weight: bold;
        }
        
        .tabla .descripcion {
            text-align: left;
            width: 40%;
        }
        
        .tabla .moneda {
            text-align: right;
            width: 16%;
        }
        
        .tabla .total {
            background-color: #f9fafb;
            font-weight: bold;
        }
        
        .tabla .gran-total {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 10px;
        }
        
        .saldo-final {
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        
        .saldo-positivo {
            color: #16a34a;
            font-weight: bold;
        }
        
        .saldo-negativo {
            color: #dc2626;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            page-break-inside: avoid;
        }
        
        .sin-datos {
            text-align: center;
            font-style: italic;
            color: #6b7280;
            padding: 15px;
        }
    </style>
</head>
<body>
    {{-- ENCABEZADO PRINCIPAL --}}
    <div class="main-header">
        <h1 class="church-name">
            IGLESIA PENTECOSTAL UNIDA DE VENEZUELA
        </h1>
        
        <h2 class="system-name">
            Sistema Administrativo y Financiero (SAFIPUV)
        </h2>
        
        <h3 class="report-title">
            Resumen de Contabilidad {{ $nivel }}
        </h3>
        
        <div class="report-info">
            <strong>Período:</strong> {{ $periodo }}@if($nivel !== 'NACIONAL') | <strong>{{ $nivel_texto }}:</strong> {{ $contabilidad }}@endif
        </div>
        
        <div class="generation-info">
            Generado el {{ $fecha_generacion }} por {{ $usuario }}
        </div>
    </div>

    {{-- SECCIÓN INGRESOS --}}
    <div class="seccion">
        <div class="seccion-titulo ingresos">INGRESOS</div>
        <table class="tabla">
            <thead>
                <tr>
                    <th class="codigo">Código</th>
                    <th class="descripcion">Descripción</th>
                    <th class="moneda">Bolívares (VES)</th>
                    <th class="moneda">Dólares (USD)</th>
                    <th class="moneda">Pesos (COP)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ingresos as $ingreso)
                <tr>
                    <td class="codigo">{{ $ingreso['codigo'] }}</td>
                    <td class="descripcion">{{ $ingreso['descripcion'] }}</td>
                    <td class="moneda">
                        @if($ingreso['VES'] != 0)
                            {{ number_format($ingreso['VES'], 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="moneda">
                        @if($ingreso['USD'] != 0)
                            {{ number_format($ingreso['USD'], 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="moneda">
                        @if($ingreso['COP'] != 0)
                            {{ number_format($ingreso['COP'], 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="sin-datos">No hay ingresos registrados en el período seleccionado</td>
                </tr>
                @endforelse
                
                {{-- TOTAL INGRESOS --}}
                @if(count($ingresos) > 0)
                <tr class="gran-total">
                    <td colspan="2" class="descripcion" style="text-align: right;"><strong>TOTAL INGRESOS</strong></td>
                    <td class="moneda">
                        <strong>{{ number_format($totales_ingresos['VES'], 2, ',', '.') }}</strong>
                    </td>
                    <td class="moneda">
                        <strong>{{ number_format($totales_ingresos['USD'], 2, ',', '.') }}</strong>
                    </td>
                    <td class="moneda">
                        <strong>{{ number_format($totales_ingresos['COP'], 2, ',', '.') }}</strong>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- SECCIÓN EGRESOS --}}
    <div class="seccion">
        <div class="seccion-titulo egresos">EGRESOS</div>
        <table class="tabla">
            <thead>
                <tr>
                    <th class="codigo">Código</th>
                    <th class="descripcion">Descripción</th>
                    <th class="moneda">Bolívares (VES)</th>
                    <th class="moneda">Dólares (USD)</th>
                    <th class="moneda">Pesos (COP)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($egresos as $egreso)
                <tr>
                    <td class="codigo">{{ $egreso['codigo'] }}</td>
                    <td class="descripcion">{{ $egreso['descripcion'] }}</td>
                    <td class="moneda">
                        @if($egreso['VES'] != 0)
                            {{ number_format($egreso['VES'], 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="moneda">
                        @if($egreso['USD'] != 0)
                            {{ number_format($egreso['USD'], 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="moneda">
                        @if($egreso['COP'] != 0)
                            {{ number_format($egreso['COP'], 2, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="sin-datos">No hay egresos registrados en el período seleccionado</td>
                </tr>
                @endforelse
                
                {{-- TOTAL EGRESOS --}}
                @if(count($egresos) > 0)
                <tr class="gran-total">
                    <td colspan="2" class="descripcion" style="text-align: right;"><strong>TOTAL EGRESOS</strong></td>
                    <td class="moneda">
                        <strong>{{ number_format($totales_egresos['VES'], 2, ',', '.') }}</strong>
                    </td>
                    <td class="moneda">
                        <strong>{{ number_format($totales_egresos['USD'], 2, ',', '.') }}</strong>
                    </td>
                    <td class="moneda">
                        <strong>{{ number_format($totales_egresos['COP'], 2, ',', '.') }}</strong>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- SECCIÓN SALDO DISPONIBLE --}}
    <div class="saldo-final">
        <div class="seccion-titulo saldo">SALDO DISPONIBLE (INGRESOS - EGRESOS)</div>
        <table class="tabla">
            <thead>
                <tr>
                    <th colspan="2" class="descripcion">CONCEPTO</th>
                    <th class="moneda">Bolívares (VES)</th>
                    <th class="moneda">Dólares (USD)</th>
                    <th class="moneda">Pesos (COP)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="gran-total">
                    <td colspan="2" class="descripcion" style="text-align: right;"><strong>SALDO DISPONIBLE</strong></td>
                    <td class="moneda">
                        <strong class="{{ $saldos['VES'] >= 0 ? 'saldo-positivo' : 'saldo-negativo' }}">
                            {{ number_format($saldos['VES'], 2, ',', '.') }}
                        </strong>
                    </td>
                    <td class="moneda">
                        <strong class="{{ $saldos['USD'] >= 0 ? 'saldo-positivo' : 'saldo-negativo' }}">
                            {{ number_format($saldos['USD'], 2, ',', '.') }}
                        </strong>
                    </td>
                    <td class="moneda">
                        <strong class="{{ $saldos['COP'] >= 0 ? 'saldo-positivo' : 'saldo-negativo' }}">
                            {{ number_format($saldos['COP'], 2, ',', '.') }}
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <p>Resumen Contable {{ $nivel }} | Sistema Administrativo y Financiero SAFIPUV</p>
        <p>Este documento fue generado automáticamente y es válido para efectos contables internos</p>
    </div>
</body>
</html>