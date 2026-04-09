<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Planilla' }} — Cerberus</title>
    <style>

        /* ── Reset ──────────────────────────────────────────────────────────── */
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.45;
        }

        /* ── Página ─────────────────────────────────────────────────────────── */
        .page {
            padding: 14mm 16mm 22mm 16mm;
        }

        /* ══════════════════════════════════════════════════════════════════════
           ENCABEZADO CORPORATIVO
        ══════════════════════════════════════════════════════════════════════ */

        .header {
            display: table;
            width: 100%;
            border-bottom: 3pt solid #1E3A8A;
            padding-bottom: 10pt;
            margin-bottom: 4pt;
        }

        .header-logo-cell {
            display: table-cell;
            width: 75pt;
            vertical-align: middle;
        }

        .header-logo-cell img {
            width: 62pt;
            height: auto;
        }

        .header-center-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 0 8pt;
        }

        .header-grupo {
            font-size: 6.5pt;
            font-weight: bold;
            color: #1E3A8A;
            letter-spacing: 1.2pt;
            text-transform: uppercase;
            margin-bottom: 2pt;
        }

        .header-empresa {
            font-size: 13pt;
            font-weight: bold;
            color: #0D1B2A;
            line-height: 1.2;
        }

        .header-gerencia {
            font-size: 7.5pt;
            color: #374151;
            margin-top: 3pt;
        }

        .header-badge-cell {
            display: table-cell;
            width: 75pt;
            vertical-align: middle;
            text-align: right;
        }

        .header-badge {
            display: inline-block;
            background: #1E3A8A;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
            text-align: center;
            padding: 5pt 8pt;
            border-radius: 4pt;
            text-transform: uppercase;
            line-height: 1.4;
        }

        /* ── Código de documento bajo el header ─────────────────────────────── */
        .doc-meta {
            display: table;
            width: 100%;
            margin-top: 6pt;
            margin-bottom: 14pt;
        }

        .doc-meta-left {
            display: table-cell;
            font-size: 7.5pt;
            color: #64748B;
        }

        .doc-meta-right {
            display: table-cell;
            text-align: right;
            font-size: 7.5pt;
            color: #64748B;
        }

        /* ══════════════════════════════════════════════════════════════════════
           TÍTULO DEL DOCUMENTO
        ══════════════════════════════════════════════════════════════════════ */

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1E3A8A;
            text-align: center;
            margin-bottom: 12pt;
            letter-spacing: 0.3pt;
        }

        .doc-subtitle {
            font-size: 8pt;
            color: #64748B;
            text-align: center;
            margin-top: -10pt;
            margin-bottom: 14pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           SECCIONES
        ══════════════════════════════════════════════════════════════════════ */

        .section {
            margin-bottom: 14pt;
            border: 0.5pt solid #CBD5E1;
            border-radius: 5pt;
            overflow: hidden;
        }

        .section-title {
            background: #1E3A8A;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
            padding: 5pt 10pt;
        }

        .section-title.area {
            background: #0F6E56;
        }

        .section-title.devolucion {
            background: #92400E;
        }

        .section-title.egreso {
            background: #6B21A8;
        }

        /* ── Grilla de campos ───────────────────────────────────────────────── */
        .fields-grid {
            padding: 8pt 10pt;
        }

        .fields-row {
            display: table;
            width: 100%;
            border-bottom: 0.3pt solid #E2E8F0;
        }

        .fields-row:last-child {
            border-bottom: none;
        }

        .field-cell {
            display: table-cell;
            width: 50%;
            padding: 4pt 6pt 4pt 0;
            vertical-align: top;
        }

        .field-cell.full {
            width: 100%;
            display: table-cell;
        }

        .field-label {
            font-size: 7pt;
            font-weight: bold;
            color: #1E3A8A;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin-bottom: 1.5pt;
        }

        .field-value {
            font-size: 9pt;
            color: #1a1a2e;
            font-weight: bold;
        }

        /* ── Badge de tipo de receptor ──────────────────────────────────────── */
        .receptor-badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
            text-transform: uppercase;
            padding: 2pt 7pt;
            border-radius: 10pt;
            margin-bottom: 6pt;
        }

        .receptor-badge.usuario {
            background: #DBEAFE;
            color: #1E40AF;
            border: 0.5pt solid #93C5FD;
        }

        .receptor-badge.area {
            background: #D1FAE5;
            color: #065F46;
            border: 0.5pt solid #6EE7B7;
        }

        /* ══════════════════════════════════════════════════════════════════════
           TABLA DE EQUIPOS
        ══════════════════════════════════════════════════════════════════════ */

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead tr {
            background: #1E3A8A;
        }

        .data-table thead th {
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 5pt 8pt;
            text-align: left;
            letter-spacing: 0.3pt;
            white-space: nowrap;
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

        /* ── Código interno resaltado en tabla ──────────────────────────────── */
        .cod-interno {
            font-weight: bold;
            color: #1E3A8A;
            font-size: 8.5pt;
        }

        /* ── Fila de atributos EAV (debajo de cada equipo) ──────────────────── */
        .eav-row td {
            background: #EFF6FF !important;
            border-bottom: 0.3pt solid #BFDBFE !important;
            padding: 3pt 8pt 3pt 20pt !important;
            font-size: 7.5pt !important;
            color: #374151 !important;
        }

        .eav-pill {
            display: inline-block;
            background: #DBEAFE;
            color: #1E40AF;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3pt;
            padding: 1pt 4pt;
            margin-right: 4pt;
        }

        /* ── Periférico (fila anidada) ──────────────────────────────────────── */
        .periferico-row td {
            background: #FEFCE8 !important;
            border-bottom: 0.3pt solid #FDE68A !important;
            padding: 4pt 8pt 4pt 22pt !important;
            font-size: 8pt !important;
            color: #78350F !important;
        }

        .periferico-prefix {
            color: #92400E;
            font-weight: bold;
            margin-right: 4pt;
        }

        /* ── Periférico devuelto (en tabla de devolución) ───────────────────── */
        .periferico-row.devuelto td {
            background: #F1F5F9 !important;
            color: #94A3B8 !important;
            text-decoration: line-through;
        }

        /* ══════════════════════════════════════════════════════════════════════
           OBSERVACIONES
        ══════════════════════════════════════════════════════════════════════ */

        .obs-box {
            margin-top: 8pt;
            padding: 7pt 10pt;
            background: #FFFBEB;
            border: 0.5pt solid #FDE68A;
            border-radius: 4pt;
            font-size: 8.5pt;
            color: #78350F;
        }

        .obs-label {
            font-weight: bold;
            font-size: 7pt;
            color: #92400E;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin-bottom: 3pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           FIRMAS
        ══════════════════════════════════════════════════════════════════════ */

        .firmas {
            margin-top: 30pt;
            display: table;
            width: 100%;
            border-top: 0.5pt solid #CBD5E1;
            padding-top: 16pt;
        }

        .firma-cell {
            display: table-cell;
            text-align: center;
            width: 33.33%;
            padding: 0 12pt;
        }

        .firma-espacio {
            height: 32pt;
        }

        .firma-linea {
            border-top: 1pt solid #1E3A8A;
            margin-bottom: 5pt;
        }

        .firma-nombre {
            font-size: 8pt;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 2pt;
        }

        .firma-label {
            font-size: 7pt;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           FOOTER DE PÁGINA
        ══════════════════════════════════════════════════════════════════════ */

        .page-footer {
            position: fixed;
            bottom: 8mm;
            left: 16mm;
            right: 16mm;
            border-top: 0.5pt solid #CBD5E1;
            padding-top: 4pt;
            display: table;
            width: calc(100% - 32mm);
        }

        .page-footer-left {
            display: table-cell;
            font-size: 6.5pt;
            color: #94A3B8;
        }

        .page-footer-right {
            display: table-cell;
            text-align: right;
            font-size: 6.5pt;
            color: #94A3B8;
        }

        /* ══════════════════════════════════════════════════════════════════════
           BADGES DE ESTADO
        ══════════════════════════════════════════════════════════════════════ */

        .badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3pt;
            padding: 1.5pt 5pt;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .badge-activa { background: #D1FAE5; color: #065F46; }
        .badge-cerrada { background: #F1F5F9; color: #475569; }
        .badge-devuelto { background: #FEF9C3; color: #713F12; }
        .badge-pendiente { background: #FEE2E2; color: #991B1B; }

    </style>
</head>
<body>

<div class="page">

    {{-- ── ENCABEZADO ─────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-logo-cell">
            @php
                $logoPath = public_path('images/cerberus.png');
                $logoBase64 = file_exists($logoPath)
                    ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                    : null;
            @endphp
            @if ($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Cerberus">
            @endif
        </div>
        <div class="header-center-cell">
            <div class="header-grupo">Grupo de Empresas Sindoni</div>
            <div class="header-empresa">
                {{ $empresaSede ?? '—' }}
            </div>
            <div class="header-gerencia">Gerencia Corporativa de Tecnología — Servicios Tecnológicos</div>
        </div>
        <div class="header-badge-cell">
            <div class="header-badge">Servicios<br>Tecnológicos</div>
        </div>
    </div>

    {{-- ── Meta del documento ──────────────────────────────────────────────── --}}
    <div class="doc-meta">
        <div class="doc-meta-left">
            Código: <strong>{{ $codigoDoc ?? '—' }}</strong>
            &nbsp;·&nbsp;
            Uso: Actividades Inherentes al Cargo
        </div>
        <div class="doc-meta-right">
            Generado: <strong>{{ $fecha ?? now()->format('d/m/Y') }}</strong>
        </div>
    </div>

    {{-- ── Contenido de cada planilla ──────────────────────────────────────── --}}
    @yield('contenido')

    {{-- ── Firmas ───────────────────────────────────────────────────────────── --}}
    @yield('firmas')

    {{-- ── Footer de página ────────────────────────────────────────────────── --}}
    <div class="page-footer">
        <div class="page-footer-left">
            Cerberus 2.0 — Sistema de Inventario y Asignaciones Tecnológicas
        </div>
        <div class="page-footer-right">
            {{ $codigoDoc ?? '' }} · {{ $fecha ?? now()->format('d/m/Y') }}
        </div>
    </div>

</div>
</body>
</html>