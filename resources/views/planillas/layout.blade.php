<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo ?? 'Planilla' }} — Cerberus</title>
    <style>
        /* ── Reset y tipografía ─────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.4;
        }

        /* ── Layout de página ───────────────────────────────────────────────── */
        .page {
            padding: 18mm 18mm 22mm 18mm;
            min-height: 100vh;
        }

        /* ── Encabezado corporativo ─────────────────────────────────────────── */
        .header {
            border-bottom: 2.5pt solid #1E3A8A;
            padding-bottom: 10pt;
            margin-bottom: 14pt;
        }

        .header-inner {
            display: table;
            width: 100%;
        }

        .header-logo {
            display: table-cell;
            width: 90pt;
            vertical-align: middle;
        }

        .header-logo img {
            width: 80pt;
            height: auto;
        }

        .header-titles {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .header-titles .grupo {
            font-size: 7pt;
            font-weight: bold;
            color: #1E3A8A;
            letter-spacing: 1pt;
            text-transform: uppercase;
        }

        .header-titles .empresa {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a2e;
            line-height: 1.2;
        }

        .header-titles .gerencia {
            font-size: 8pt;
            color: #374151;
            margin-top: 2pt;
        }

        .header-badge {
            display: table-cell;
            width: 90pt;
            vertical-align: middle;
            text-align: right;
        }

        .header-badge .badge-box {
            background: #1E3A8A;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            padding: 4pt 8pt;
            border-radius: 4pt;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            display: inline-block;
        }

        /* ── Título del documento ───────────────────────────────────────────── */
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            color: #1E3A8A;
            margin-bottom: 12pt;
            padding-bottom: 6pt;
            border-bottom: 0.5pt solid #CBD5E1;
        }

        /* ── Sección: etiqueta + contenido ─────────────────────────────────── */
        .section {
            margin-bottom: 12pt;
        }

        .section-title {
            font-size: 8pt;
            font-weight: bold;
            color: #1E3A8A;
            text-transform: uppercase;
            letter-spacing: 0.8pt;
            background: #EFF6FF;
            padding: 4pt 8pt;
            border-left: 3pt solid #1E3A8A;
            margin-bottom: 8pt;
        }

        /* ── Grid de campos ─────────────────────────────────────────────────── */
        .fields-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .fields-row {
            display: table-row;
        }

        .field-cell {
            display: table-cell;
            padding: 3pt 0;
            vertical-align: top;
            width: 50%;
        }

        .field-cell.full {
            width: 100%;
        }

        .field-label {
            font-size: 7.5pt;
            color: #6B7280;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .field-value {
            font-size: 9pt;
            color: #1a1a2e;
            font-weight: bold;
            margin-top: 1pt;
        }

        /* ── Tabla de datos ─────────────────────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
        }

        .data-table thead th {
            background: #1E3A8A;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            padding: 5pt 8pt;
            text-align: left;
            border: none;
        }

        .data-table tbody tr:nth-child(even) {
            background: #F8FAFC;
        }

        .data-table tbody tr:nth-child(odd) {
            background: #ffffff;
        }

        .data-table tbody td {
            font-size: 8.5pt;
            padding: 5pt 8pt;
            color: #1a1a2e;
            border-bottom: 0.3pt solid #E2E8F0;
            vertical-align: top;
        }

        .data-table tbody tr.devuelto td {
            color: #6B7280;
            text-decoration: line-through;
        }

        .data-table tbody tr.pendiente td {
            color: #DC2626;
            font-weight: bold;
        }

        /* ── Atributos EAV ──────────────────────────────────────────────────── */
        .attrs-list {
            font-size: 8pt;
            color: #374151;
            margin-top: 3pt;
        }

        .attr-item {
            display: inline;
        }

        .attr-label {
            font-weight: bold;
            color: #1E3A8A;
        }

        /* ── Periféricos anidados ───────────────────────────────────────────── */
        .sub-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
            background: #F8FAFC;
        }

        .sub-table thead th {
            background: #374151;
            color: #ffffff;
            font-size: 7pt;
            padding: 3pt 8pt;
            text-align: left;
        }

        .sub-table tbody td {
            font-size: 7.5pt;
            padding: 3pt 8pt;
            color: #374151;
            border-bottom: 0.2pt solid #E2E8F0;
        }

        /* ── Bloque de firma ────────────────────────────────────────────────── */
        .firmas {
            margin-top: 28pt;
            display: table;
            width: 100%;
        }

        .firma-cell {
            display: table-cell;
            text-align: center;
            width: 50%;
            padding: 0 20pt;
        }

        .firma-linea {
            border-top: 1pt solid #374151;
            margin-bottom: 4pt;
        }

        .firma-label {
            font-size: 8pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        /* ── Pie de página ──────────────────────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 18mm;
            right: 18mm;
            border-top: 0.5pt solid #CBD5E1;
            padding-top: 5pt;
        }

        .footer-inner {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            font-size: 7pt;
            color: #6B7280;
            vertical-align: middle;
        }

        .footer-center {
            display: table-cell;
            font-size: 7pt;
            color: #6B7280;
            text-align: center;
            vertical-align: middle;
        }

        .footer-right {
            display: table-cell;
            font-size: 7pt;
            color: #6B7280;
            text-align: right;
            vertical-align: middle;
        }

        /* ── Utilidades ─────────────────────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 1.5pt 6pt;
            border-radius: 3pt;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-activa   { background: #D1FAE5; color: #065F46; }
        .badge-cerrada  { background: #F3F4F6; color: #6B7280; }
        .badge-devuelto { background: #FEF3C7; color: #92400E; }

        .divider {
            border: none;
            border-top: 0.5pt solid #E2E8F0;
            margin: 10pt 0;
        }

        .text-muted { color: #6B7280; }
        .text-blue  { color: #1E3A8A; }
        .font-bold  { font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 4pt; }
        .mb-8 { margin-bottom: 8pt; }
    </style>
</head>
<body>

{{-- Pie de página fijo (aparece en todas las páginas) --}}
<div class="footer">
    <div class="footer-inner">
        <div class="footer-left">
            {{ $codigoDoc ?? '' }} &nbsp;|&nbsp; Grupo de Empresas Sindoni
        </div>
        <div class="footer-center">
            Gerencia Corporativa de Tecnología · Servicios Tecnológicos
        </div>
        <div class="footer-right">
            Página <span class="pagenum"></span>
        </div>
    </div>
</div>

<div class="page">

    {{-- Encabezado corporativo --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-logo">
                {{-- Logo corporativo --}}
                @php
                    $logoPath = public_path('images/logo-sindoni.png');
                    $logoBase64 = file_exists($logoPath)
                        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                        : null;
                @endphp
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Grupo Sindoni">
                @endif
            </div>
            <div class="header-titles">
                <div class="grupo">Grupo de Empresas Sindoni</div>
                <div class="empresa">{{ $empresaSede ?? ($asignacion->empresa->nombre ?? $usuario->empresaNomina->nombre ?? 'Cerberus') }}</div>
                <div class="gerencia">Gerencia Corporativa de Tecnología — Servicios Tecnológicos</div>
            </div>
            <div class="header-badge">
                <div class="badge-box">Servicios<br>Tecnológicos</div>
            </div>
        </div>
    </div>

    {{-- Contenido de la planilla --}}
    @yield('contenido')

    {{-- Firmas --}}
    <div class="firmas">
        <div class="firma-cell">
            <div class="firma-linea"></div>
            <div class="firma-label">Firma del Técnico</div>
        </div>
        <div class="firma-cell">
            <div class="firma-linea"></div>
            <div class="firma-label">Firma del Trabajador</div>
        </div>
    </div>

</div>

</body>
</html>