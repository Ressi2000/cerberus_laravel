{{-- Informe generado desde Cerberus Bot. Mismo encabezado que las planillas;
     los datos y tablas salen de Cerberus, el resumen lo redacta el asistente. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} — Cerberus</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 8.5pt; color: #0F172A; line-height: 1.4; margin: 14mm 14mm 18mm 14mm; }
        .watermark { position: fixed; top: 50%; left: 50%; width: 380pt; margin-top: -190pt; margin-left: -190pt; opacity: 0.08; z-index: -1; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-logo { width: 80pt; vertical-align: middle; }
        .header-logo img { width: 70pt; }
        .header-center { text-align: center; vertical-align: middle; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; line-height: 1.35; }
        .meta { width: 100%; margin: 6pt 0 4pt; font-size: 7.5pt; color: #415A77; }
        .meta td:last-child { text-align: right; }
        h1 { font-size: 13pt; color: #1E3A8A; text-align: center; text-transform: uppercase; margin: 8pt 0 3pt; letter-spacing: .3pt; }
        .divider { height: 2pt; background: #1E3A8A; margin-bottom: 10pt; }
        h2 { font-size: 9.5pt; color: #1E3A8A; margin: 12pt 0 5pt; padding-bottom: 2pt; border-bottom: 1pt solid #CBD5E1; }
        .resumen { background: #F1F5F9; border-left: 3pt solid #0EA5E9; padding: 7pt 9pt; margin-bottom: 8pt; }
        .resumen .aviso { font-size: 6.5pt; color: #64748B; text-transform: uppercase; letter-spacing: .3pt; margin-bottom: 3pt; }
        .resumen p, .resumen li { font-size: 8.5pt; margin-bottom: 2pt; }
        .resumen ul, .resumen ol { padding-left: 12pt; }
        .resumen h3 { font-size: 8.5pt; margin: 4pt 0 2pt; }
        table.kv { border-collapse: collapse; width: 100%; }
        table.kv td { padding: 2.5pt 4pt; border-bottom: .5pt solid #E2E8F0; vertical-align: top; }
        table.kv td.k { width: 32%; color: #475569; font-weight: bold; }
        table.datos { border-collapse: collapse; width: 100%; }
        table.datos th { background: #1E3A8A; color: #fff; font-size: 7.5pt; text-align: left; padding: 3.5pt 4pt; }
        table.datos td { font-size: 7.5pt; padding: 3pt 4pt; border-bottom: .5pt solid #E2E8F0; vertical-align: top; }
        table.datos tr:nth-child(even) td { background: #F8FAFC; }
        table.barras { border-collapse: collapse; width: 100%; }
        table.barras td { padding: 2pt 4pt; font-size: 8pt; vertical-align: middle; }
        table.barras td.etq { width: 30%; }
        table.barras td.num { width: 10%; text-align: right; font-weight: bold; }
        .barra { height: 8pt; background: #0EA5E9; }
        .pie { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 6.5pt; color: #94A3B8; text-align: center; }
        .vacio { color: #64748B; font-style: italic; }
    </style>
</head>
<body>
    @php
        $marca = \App\Services\PlanillaAssetCache::base64('images/CBRS2.0S_frW.png');
        $st = \App\Services\PlanillaAssetCache::base64('images/st.png');
        $sindoni = \App\Services\PlanillaAssetCache::base64('images/logo-sindoni.png');
    @endphp
    @if ($marca) <img src="{{ $marca }}" class="watermark" alt=""> @endif

    <table class="header-table">
        <tr>
            <td class="header-logo">@if ($st) <img src="{{ $st }}" alt=""> @endif</td>
            <td class="header-center">
                @if ($empresa) <div>{{ $empresa }}</div> @endif
                <div>Grupo de Empresas Sindoni</div>
                <div>Gerencia Corporativa de Tecnología · Servicios Tecnológicos</div>
            </td>
            <td class="header-logo" style="text-align:right">@if ($sindoni) <img src="{{ $sindoni }}" alt=""> @endif</td>
        </tr>
    </table>

    <table class="meta"><tr>
        <td>Informe generado con Cerberus Bot · Solicitado por <strong>{{ $generadoPor }}</strong></td>
        <td>Generado: <strong>{{ $fecha }}</strong></td>
    </tr></table>

    <h1>{{ $titulo }}</h1>
    <div class="divider"></div>

    @if ($resumen)
        <div class="resumen">
            <div class="aviso">Resumen redactado por el asistente — los datos oficiales están en las tablas</div>
            {!! \Illuminate\Support\Str::markdown($resumen, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
        </div>
    @endif

    @if ($partes['datos'])
        <table class="kv">
            @foreach ($partes['datos'] as $k => $v)
                <tr><td class="k">{{ $k }}</td><td>{{ $v }}</td></tr>
            @endforeach
        </table>
    @endif

    @foreach ($partes['secciones'] as $nombreSeccion => $pares)
        <h2>{{ $nombreSeccion }}</h2>
        <table class="kv">
            @foreach ($pares as $k => $v)
                <tr>
                    @if ($k === '')
                        <td colspan="2">{!! nl2br(e($v)) !!}</td>
                    @else
                        <td class="k">{{ $k }}</td><td>{{ $v }}</td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endforeach

    @foreach ($partes['graficos'] as $nombreSeccion => $conteo)
        @php $max = max(1, max($conteo)); @endphp
        <h2>{{ $nombreSeccion }}</h2>
        <table class="barras">
            @foreach ($conteo as $etiqueta => $n)
                <tr>
                    <td class="etq">{{ $etiqueta }}</td>
                    <td><div class="barra" style="width: {{ max(1, round($n / $max * 100)) }}%"></div></td>
                    <td class="num">{{ $n + 0 }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    @foreach ($partes['tablas'] as $nombreSeccion => $tabla)
        <h2>{{ $nombreSeccion }} ({{ count($tabla['filas']) }})</h2>
        <table class="datos">
            <thead><tr>@foreach ($tabla['columnas'] as $c) <th>{{ $c }}</th> @endforeach</tr></thead>
            <tbody>
                @foreach ($tabla['filas'] as $fila)
                    <tr>@foreach ($fila as $celda) <td>{{ $celda }}</td> @endforeach</tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @if (! $partes['datos'] && ! $partes['secciones'] && ! $partes['tablas'] && ! $partes['graficos'])
        <p class="vacio">La consulta no devolvió resultados.</p>
    @endif

    <div class="pie">Cerberus 2.0 · {{ $titulo }} · {{ $fecha }}</div>
</body>
</html>
