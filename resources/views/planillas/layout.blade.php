<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDoc ?? 'Planilla' }} — Cerberus</title>
    <style>

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.4;
            /* Márgenes de página controlados aquí, no con @page */
            margin: 14mm 16mm 14mm 16mm;
        }

        /* ══════════════════════════════════════════════════════════════════════
           ENCABEZADO CORPORATIVO — flujo normal, primera página
        ══════════════════════════════════════════════════════════════════════ */
        .header-principal {
            width: 100%;
            border-bottom: 2.5pt solid #1E3A8A;
            padding-bottom: 8pt;
            margin-bottom: 5pt;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo-cell {
            width: 60pt;
            vertical-align: middle;
        }

        .header-logo-cell img { width: 52pt; height: auto; }

        .header-center-cell {
            vertical-align: middle;
            text-align: center;
            padding: 0 8pt;
        }

        .header-grupo {
            font-size: 6pt;
            font-weight: bold;
            color: #1E3A8A;
            letter-spacing: 1pt;
            text-transform: uppercase;
            margin-bottom: 2pt;
        }

        .header-empresa {
            font-size: 12pt;
            font-weight: bold;
            color: #0D1B2A;
            line-height: 1.2;
        }

        .header-gerencia {
            font-size: 7pt;
            color: #374151;
            margin-top: 2pt;
        }

        .header-badge-cell {
            width: 70pt;
            vertical-align: middle;
            text-align: right;
        }

        .header-badge {
            display: inline-block;
            background: #1E3A8A;
            color: #ffffff;
            font-size: 6pt;
            font-weight: bold;
            text-align: center;
            padding: 5pt 8pt;
            border-radius: 3pt;
            text-transform: uppercase;
            line-height: 1.5;
        }

        /* Meta del documento */
        .doc-meta {
            width: 100%;
            margin-top: 5pt;
            margin-bottom: 10pt;
        }

        .doc-meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-meta-left  { font-size: 7pt; color: #64748B; }
        .doc-meta-right { font-size: 7pt; color: #64748B; text-align: right; }

        /* ══════════════════════════════════════════════════════════════════════
           TÍTULOS
        ══════════════════════════════════════════════════════════════════════ */
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1E3A8A;
            text-align: center;
            margin-bottom: 4pt;
        }

        .doc-subtitle {
            font-size: 7.5pt;
            color: #64748B;
            text-align: center;
            margin-bottom: 12pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           SECCIONES DE DATOS (receptor / trabajador)
        ══════════════════════════════════════════════════════════════════════ */
        .section {
            margin-bottom: 10pt;
            border: 0.5pt solid #CBD5E1;
            border-radius: 4pt;
            overflow: hidden;
        }

        .section-title {
            background: #1E3A8A;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.7pt;
            text-transform: uppercase;
            padding: 4pt 10pt;
        }

        .section-title.area       { background: #0F6E56; }
        .section-title.devolucion { background: #92400E; }
        .section-title.egreso     { background: #6B21A8; }

        .fields-grid { padding: 6pt 10pt 5pt; }

        /* Tabla de campos 3 columnas */
        .fields-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fields-table tr { border-bottom: 0.3pt solid #E2E8F0; }
        .fields-table tr:last-child { border-bottom: none; }

        .fields-table td {
            width: 33.33%;
            padding: 3pt 6pt 3pt 0;
            vertical-align: top;
        }

        .field-label {
            font-size: 6.5pt;
            font-weight: bold;
            color: #1E3A8A;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            margin-bottom: 1pt;
        }

        .field-value {
            font-size: 8.5pt;
            color: #1a1a2e;
            font-weight: bold;
        }

        .receptor-badge {
            display: inline-block;
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
            text-transform: uppercase;
            padding: 2pt 7pt;
            border-radius: 8pt;
            margin-bottom: 5pt;
        }

        .receptor-badge.usuario {
            background: #DBEAFE; color: #1E40AF;
            border: 0.5pt solid #93C5FD;
        }

        .receptor-badge.area {
            background: #D1FAE5; color: #065F46;
            border: 0.5pt solid #6EE7B7;
        }

        /* ══════════════════════════════════════════════════════════════════════
           TABLA DE EQUIPOS
           thead con display:table-header-group → DomPDF repite en cada página
           El primer thead tiene el título de sección + encabezados de columna.
        ══════════════════════════════════════════════════════════════════════ */
        .equipos-section {
            margin-bottom: 10pt;
            border: 0.5pt solid #CBD5E1;
            border-radius: 4pt;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        /* thead repetible en cada página */
        .data-table thead {
            display: table-header-group;
        }

        /* Primera fila del thead: banner de sección (se repite como continuación) */
        .thead-banner td {
            background: #1E3A8A;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
            text-transform: uppercase;
            padding: 4pt 10pt;
        }

        .thead-banner.devolucion td { background: #92400E; }
        .thead-banner.peligro    td { background: #7F1D1D; }
        .thead-banner.verde      td { background: #065F46; }
        .thead-banner.egreso     td { background: #6B21A8; }

        /* Segunda fila del thead: encabezados de columna */
        .thead-cols th {
            background: #2D4E9E;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            padding: 4pt 6pt;
            text-align: left;
            letter-spacing: 0.2pt;
            border-right: 0.3pt solid #4A6FBF;
        }

        .thead-cols th:last-child { border-right: none; }

        /* Celdas del cuerpo */
        .data-table tbody td {
            font-size: 7.5pt;
            padding: 4pt 6pt;
            color: #1a1a2e;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Alternancia de filas */
        .tr-par td   { background: #F8FAFC; }
        .tr-impar td { background: #ffffff; }

        .cod-interno { font-weight: bold; color: #1E3A8A; }

        .cell-sub {
            font-size: 6.5pt;
            color: #6B7280;
            display: block;
            margin-top: 1pt;
        }

        .garantia-vencida {
            color: #DC2626;
            font-size: 6pt;
            font-weight: bold;
            display: block;
            margin-top: 1pt;
        }

        /* page-break-inside:avoid → equipo + su EAV no se separan */
        .equipo-group    { page-break-inside: avoid; }
        .periferico-group { page-break-inside: avoid; }

        /* ══════════════════════════════════════════════════════════════════════
           FILA EAV — equipo principal
        ══════════════════════════════════════════════════════════════════════ */
        .tr-eav td {
            background: #EEF2FF !important;
            border-top: 0.3pt solid #C7D2FE;
            border-bottom: 0.5pt solid #C7D2FE;
            padding: 5pt 10pt 5pt 12pt !important;
        }

        .eav-titulo {
            font-size: 6pt;
            font-weight: bold;
            color: #3730A3;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin-bottom: 4pt;
        }

        /* Sub-tabla EAV de 3 columnas */
        .eav-tabla {
            width: 100%;
            border-collapse: collapse;
        }

        .eav-tabla td {
            width: 33.33%;
            padding: 2pt 6pt 2pt 0 !important;
            vertical-align: top;
            border: none !important;
            background: transparent !important;
        }

        .eav-attr-label {
            font-size: 6pt;
            font-weight: bold;
            color: #4338CA;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .eav-attr-valor {
            font-size: 7.5pt;
            color: #1a1a2e;
            font-weight: bold;
        }

        /* ══════════════════════════════════════════════════════════════════════
           FILA EAV — periférico
        ══════════════════════════════════════════════════════════════════════ */
        .tr-eav-per td {
            background: #FEF9C3 !important;
            border-top: 0.3pt solid #FDE68A;
            border-bottom: 0.5pt solid #FDE68A;
            padding: 5pt 10pt 5pt 22pt !important;
        }

        .eav-titulo-per {
            font-size: 6pt;
            font-weight: bold;
            color: #92400E;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin-bottom: 4pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           PERIFÉRICO — fila completa con indicador ↳
        ══════════════════════════════════════════════════════════════════════ */
        .tr-periferico td {
            background: #FFFBEB !important;
            border-top: 0.3pt solid #FDE68A;
            border-bottom: 0.3pt solid #FDE68A;
            font-size: 7.5pt;
            color: #78350F;
        }

        .periferico-prefix {
            color: #B45309;
            font-weight: bold;
            font-size: 9pt;
            margin-right: 2pt;
        }

        .periferico-cod { font-weight: bold; color: #92400E; }

        /* ══════════════════════════════════════════════════════════════════════
           ESTADOS ESPECIALES
        ══════════════════════════════════════════════════════════════════════ */
        .tr-devuelto td { color: #9CA3AF !important; }
        .tr-pendiente td { color: #DC2626 !important; font-weight: bold !important; }

        /* ══════════════════════════════════════════════════════════════════════
           OBSERVACIONES
        ══════════════════════════════════════════════════════════════════════ */
        .obs-box {
            margin-top: 8pt;
            padding: 6pt 10pt;
            background: #FFFBEB;
            border: 0.5pt solid #FDE68A;
            border-radius: 3pt;
            font-size: 7.5pt;
            color: #78350F;
        }

        .obs-label {
            font-weight: bold;
            font-size: 6.5pt;
            color: #92400E;
            text-transform: uppercase;
            margin-bottom: 2pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           BADGES
        ══════════════════════════════════════════════════════════════════════ */
        .badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            border-radius: 2pt;
            padding: 1.5pt 5pt;
            text-transform: uppercase;
        }

        .badge-pendiente { background: #FEE2E2; color: #991B1B; }
        .badge-devuelto  { background: #FEF9C3; color: #713F12; }
        .badge-ok        { background: #D1FAE5; color: #065F46; }

        /* ══════════════════════════════════════════════════════════════════════
           FIRMAS — flujo normal al final del documento
           Se usan con margin-top para separar del contenido.
           Al estar en flujo normal, DomPDF las coloca donde corresponde
           sin romper nada.
        ══════════════════════════════════════════════════════════════════════ */
        .firmas-wrapper {
            margin-top: 28pt;
            border-top: 0.8pt solid #CBD5E1;
            padding-top: 8pt;
        }

        .firmas-meta {
            font-size: 6pt;
            color: #94A3B8;
            text-align: center;
            margin-bottom: 10pt;
        }

        .firmas-table {
            width: 100%;
            border-collapse: collapse;
        }

        .firma-cell {
            width: 33.33%;
            text-align: center;
            padding: 0 14pt;
            vertical-align: bottom;
        }

        .firma-espacio { height: 32pt; }

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

        .firma-cargo {
            font-size: 6.5pt;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        /* Footer de página */
        .page-footer {
            margin-top: 12pt;
            border-top: 0.3pt solid #E2E8F0;
            padding-top: 4pt;
            width: 100%;
        }

        .page-footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .page-footer-left  { font-size: 6pt; color: #94A3B8; }
        .page-footer-right { font-size: 6pt; color: #94A3B8; text-align: right; }

    </style>
</head>
<body>

    {{-- ══ ENCABEZADO CORPORATIVO ══════════════════════════════════════════ --}}
    <div class="header-principal">
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    @php
                        $logoPath   = public_path('images/cerberus.png');
                        $logoBase64 = file_exists($logoPath)
                            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
                            : null;
                    @endphp
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Cerberus">
                    @endif
                </td>
                <td class="header-center-cell">
                    <div class="header-grupo">Grupo de Empresas Sindoni</div>
                    <div class="header-empresa">{{ $empresaSede ?? '—' }}</div>
                    <div class="header-gerencia">Gerencia Corporativa de Tecnología — Servicios Tecnológicos</div>
                </td>
                <td class="header-badge-cell">
                    <div class="header-badge">Servicios<br>Tecnológicos</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-meta">
        <table class="doc-meta-table">
            <tr>
                <td class="doc-meta-left">
                    Código: <strong>{{ $codigoDoc ?? '—' }}</strong>
                    &nbsp;·&nbsp; Uso: Actividades Inherentes al Cargo
                </td>
                <td class="doc-meta-right">
                    Generado: <strong>{{ $fecha ?? now()->format('d/m/Y') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ CONTENIDO ESPECÍFICO DE CADA PLANILLA ═══════════════════════════ --}}
    @yield('contenido')

    {{-- ══ FIRMAS — al final del flujo, siempre visibles ══════════════════ --}}
    <div class="firmas-wrapper">
        <div class="firmas-meta">
            Cerberus 2.0 &nbsp;·&nbsp; {{ $codigoDoc ?? '' }} &nbsp;·&nbsp; {{ $fecha ?? now()->format('d/m/Y') }}
        </div>
        <table class="firmas-table">
            <tr>
                @yield('firmas')
            </tr>
        </table>
    </div>

    {{-- Footer informativo --}}
    <div class="page-footer">
        <table class="page-footer-table">
            <tr>
                <td class="page-footer-left">Cerberus 2.0 — Sistema de Inventario y Asignaciones Tecnológicas</td>
                <td class="page-footer-right">{{ $codigoDoc ?? '' }} · {{ $fecha ?? now()->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

</body>
</html>