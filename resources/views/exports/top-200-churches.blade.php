<table>
    <thead>
        <tr>
            <th colspan="4">
                IGLESIA PENTECOSTAL UNIDA DE VENEZUELA
            </th>
        </tr>
        <tr>
            <th colspan="4">
                TOP 200 IGLESIAS CON MAYOR APORTE
            </th>
        </tr>
        <tr>
            <th colspan="4">
                Categoría: {{ strtoupper($categoria) }}
            </th>
        </tr>
        <tr>
            <th colspan="4">
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
            </th>
        </tr>
        <tr></tr> {{-- Fila vacía para separación --}}
        <tr>
            <th>#</th>
            <th>IGLESIA</th>
            <th>SECTOR</th>
            <th>MONTO ENVIADO (Bs.)</th>
        </tr>
    </thead>
    <tbody>
        @php $total = 0; @endphp
        @foreach ($datos as $index => $fila)
            @php $total += $fila['monto']; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $fila['church_nombre'] ?? '—' }}</td>
                <td>{{ $fila['sector_nombre'] ?? '—' }}</td>
                <td>{{ number_format($fila['monto'], 2, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3"><strong>TOTAL GENERAL</strong></td>
            <td><strong>{{ number_format($total, 2, ',', '.') }}</strong></td>
        </tr>
    </tbody>
</table>
