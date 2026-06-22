<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDoc ?? 'Planilla' }} — Cerberus</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 8.5pt;
            color: #0F172A;
            background: #ffffff;
            line-height: 1.45;
            /* Margen inferior ampliado para reservar el espacio que ocupan
               las firmas (position:fixed) + el footer, y evitar que el
               contenido fluido se monte encima de ellos. */
            margin: 14mm 16mm 60mm 16mm;
        }

        /* ══════════════════════════════════════════════════════════════════════
           MARCA DE AGUA — fixed → DomPDF la repite en cada página
        ══════════════════════════════════════════════════════════════════════ */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 420pt;
            height: auto;
            margin-top: -210pt;
            margin-left: -210pt;
            opacity: 0.3;
            z-index: -1;
        }

        /* ══════════════════════════════════════════════════════════════════════
           ENCABEZADO CORPORATIVO — flujo normal, primera página
        ══════════════════════════════════════════════════════════════════════ */
        .header-principal {
            width: 100%;
            margin-bottom: 7pt;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo-cell {
            width: 90pt;
            vertical-align: middle;
        }

        .header-logo-cell img {
            width: 78pt;
            height: auto;
        }

        .header-center-cell {
            vertical-align: middle;
            text-align: center;
            padding: 0 8pt;
        }

        .header-grupo,
        .header-empresa,
        .header-gerencia {
            font-size: 9pt;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.3pt;
            text-transform: uppercase;
            line-height: 1.35;
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

        .doc-meta-left  { font-size: 8pt; color: #415A77; }
        .doc-meta-right { font-size: 8pt; color: #415A77; text-align: right; }

        /* Bloque de verificación QR — fixed, esquina inferior derecha,
           encima de la línea del footer y por fuera del área de firmas.
           Es independiente del doc-meta porque cada planilla reemplaza
           ese bloque con uno propio cuando oculta el genérico; fixed
           garantiza que el QR aparezca en todas sin tocar cada vista. */
        .verificacion-box {
            position: fixed;
            bottom: 13mm;
            right: 16mm;
            width: 38pt;
            text-align: center;
            font-size: 5.5pt;
            color: #94A3B8;
        }

        .verificacion-box img {
            width: 38pt;
            height: 38pt;
        }

        .verificacion-box .folio {
            display: block;
            margin-top: 2pt;
            font-weight: bold;
            color: #415A77;
            letter-spacing: 0.3pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           TÍTULOS
        ══════════════════════════════════════════════════════════════════════ */
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1E3A8A;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            margin-top: 10pt;
            margin-bottom: 6pt;
        }

        .doc-divider {
            width: 100%;
            height: 2pt;
            background: #1E3A8A;
            border-top: none;
            margin-bottom: 10pt;
        }

        .doc-subtitle {
            font-size: 7.5pt;
            color: #64748B;
            text-align: center;
            margin-bottom: 14pt;
        }

        /* ══════════════════════════════════════════════════════════════════════
           SECCIONES DE DATOS (receptor / trabajador)
        ══════════════════════════════════════════════════════════════════════ */
        .section-title-plain {
            font-size: 7.5pt;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.6pt;
            background: #1E3A8A;
            border-left: 3pt solid #778DA9;
            padding: 4pt 8pt;
            margin: 10pt 0 6pt;
        }

        .section-title-plain.area {
            background: #0F6E56;
            border-left-color: #6EE7B7;
        }

        .section-title-plain.devolucion {
            background: #92400E;
            border-left-color: #FBBF24;
        }

        .section-title-plain.egreso {
            background: #6B21A8;
            border-left-color: #C084FC;
        }

        .section {
            margin-bottom: 11pt;
            border: 0.5pt solid #CBD5E1;
            border-radius: 5pt;
            overflow: hidden;
        }

        .section-title {
            background: #1E3A8A;
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.7pt;
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

        .fields-grid {
            padding: 6pt 10pt 5pt;
        }

        /* Tabla de campos 3 columnas */
        .fields-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Helvetica', Arial, sans-serif;
        }

        .fields-table tr {
            border-bottom: 0.3pt solid #E2E8F0;
        }

        .fields-table tr:last-child {
            border-bottom: none;
        }

        .fields-table td {
            width: 33.33%;
            padding: 4pt 6pt 4pt 0;
            vertical-align: top;
        }

        .field-label {
            font-size: 6.5pt;
            font-weight: bold;
            color: #415A77;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            margin-bottom: 1pt;
        }

        .field-value {
            font-size: 8.5pt;
            color: #0F172A;
            font-weight: normal;
        }

        /* Resalta los datos clave (nombre, código interno, etc.) */
        .field-value.valor-clave {
            color: #1E3A8A;
            font-weight: bold;
            font-size: 9.5pt;
            letter-spacing: 0.2pt;
        }

        /* Código interno del equipo — el dato de identificación más importante */
        .field-value.valor-codigo {
            display: inline-block;
            background: #1E3A8A;
            color: #ffffff;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.3pt;
            padding: 2pt 8pt;
            border-radius: 3pt;
        }

        /* Referencia al equipo padre — distinta del código principal, en tono ámbar */
        .field-value.valor-referencia {
            display: inline-block;
            background: #FEF3C7;
            color: #92400E;
            font-weight: bold;
            font-size: 8.5pt;
            letter-spacing: 0.2pt;
            padding: 2pt 8pt;
            border-radius: 3pt;
            border: 0.5pt solid #FDE68A;
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
           thead con display:table-header-group → DomPDF repite en cada página
           El primer thead tiene el título de sección + encabezados de columna.
        ══════════════════════════════════════════════════════════════════════ */
        .equipos-section {
            margin-bottom: 11pt;
            border: 0.5pt solid #CBD5E1;
            border-radius: 5pt;
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

        .thead-banner.devolucion td {
            background: #92400E;
        }

        .thead-banner.peligro td {
            background: #7F1D1D;
        }

        .thead-banner.verde td {
            background: #065F46;
        }

        .thead-banner.egreso td {
            background: #6B21A8;
        }

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

        .thead-cols th:last-child {
            border-right: none;
        }

        /* Celdas del cuerpo */
        .data-table tbody td {
            font-size: 7.5pt;
            padding: 5pt 6pt;
            color: #1a1a2e;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            border-bottom: 0.3pt solid #EEF2F7;
        }

        /* Alternancia de filas */
        .tr-par td {
            background: #F8FAFC;
        }

        .tr-impar td {
            background: #ffffff;
        }

        .cod-interno {
            font-weight: bold;
            color: #1E3A8A;
        }

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
        .equipo-group {
            page-break-inside: avoid;
        }

        .periferico-group {
            page-break-inside: avoid;
        }

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
           PERIFÉRICO — fila completa con indicador »
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

        .periferico-cod {
            font-weight: bold;
            color: #92400E;
        }

        /* ══════════════════════════════════════════════════════════════════════
           ESTADOS ESPECIALES
        ══════════════════════════════════════════════════════════════════════ */
        .tr-devuelto td {
            color: #9CA3AF !important;
        }

        .tr-pendiente td {
            color: #DC2626 !important;
            font-weight: bold !important;
        }

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
            border-radius: 8pt;
            padding: 2pt 7pt;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
        }

        .badge-pendiente {
            background: #FEE2E2;
            color: #991B1B;
            border: 0.4pt solid #FCA5A5;
        }

        .badge-devuelto {
            background: #FEF9C3;
            color: #713F12;
            border: 0.4pt solid #FDE68A;
        }

        .badge-ok {
            background: #D1FAE5;
            color: #065F46;
            border: 0.4pt solid #6EE7B7;
        }

        /* ══════════════════════════════════════════════════════════════════════
           FIRMAS — fijas al fondo de la página (position:fixed)
           Coordenadas alineadas con los márgenes del body (14mm/16mm).
           DomPDF repite los elementos fixed en cada página generada.
        ══════════════════════════════════════════════════════════════════════ */
        .firmas-wrapper {
            position: fixed;
            bottom: 32mm;
            left: 16mm;
            right: 16mm;
        }

        .firmas-table {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .firma-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 14pt;
            vertical-align: bottom;
        }

        .firma-espacio {
            height: 54pt;
        }

        .firma-linea {
            border-top: 1pt solid #1E3A8A;
            margin-bottom: 5pt;
        }

        .firma-nombre {
            font-size: 8pt;
            font-weight: bold;
            color: #1E3A8A;
            margin-bottom: 2pt;
        }

        .firma-cargo {
            font-size: 6.5pt;
            color: #415A77;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        /* Línea separadora del footer — fija, el texto del footer se dibuja
           aparte vía script PHP de DomPDF (necesario para el contador
           "Página X de Y", que no funciona con counter(pages) en CSS). */
        .page-footer-line {
            position: fixed;
            bottom: 12mm;
            left: 16mm;
            right: 16mm;
            border-top: 0.3pt solid #E2E8F0;
        }
    </style>
</head>

<body>

    {{-- ══ MARCA DE AGUA — se repite en cada página ══════════════════════════ --}}
    @php
        $watermarkBase64 = \App\Services\PlanillaAssetCache::base64('images/CBRS2.0S_frW.png');
    @endphp
    @if ($watermarkBase64)
        <img src="{{ $watermarkBase64 }}" class="watermark" alt="">
    @endif

    {{-- ══ ENCABEZADO CORPORATIVO ══════════════════════════════════════════ --}}
    <div class="header-principal">
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    @php
                        $stBase64 = \App\Services\PlanillaAssetCache::base64('images/st.png');
                    @endphp
                    @if ($stBase64)
                        <img src="{{ $stBase64 }}" alt="Servicios Tecnológicos">
                    @endif
                </td>
                <td class="header-center-cell">
                    <div class="header-empresa">{{ $empresaSede ?? '—' }}</div>
                    <div class="header-grupo">Grupo de Empresas Sindoni</div>
                    <div class="header-gerencia">Gerencia Corporativa de Tecnología</div>
                    <div class="header-gerencia">Servicios Tecnológicos</div>
                </td>
                <td class="header-logo-cell">
                    @php
                        $sindoniBase64 = \App\Services\PlanillaAssetCache::base64('images/logo-sindoni.png');
                    @endphp
                    @if ($sindoniBase64)
                        <img src="{{ $sindoniBase64 }}" alt="Empresas Sindoni">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @unless ($ocultarMeta ?? false)
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
    @endunless

    @if (isset($qrBase64, $folio))
        <div class="verificacion-box">
            <img src="{{ $qrBase64 }}" alt="Verificar">
            <span class="folio">{{ $folio }}</span>
        </div>
    @endif

    {{-- ══ CONTENIDO ESPECÍFICO DE CADA PLANILLA ═══════════════════════════ --}}
    @yield('contenido')

    {{-- ══ FIRMAS — al final del flujo, siempre visibles ══════════════════ --}}
    @hasSection('firmas')
        <div class="firmas-wrapper">
            <div class="firmas-table">
                @yield('firmas')
            </div>
        </div>
    @endif

    {{-- Línea separadora del footer — fija, se repite en cada página --}}
    <div class="page-footer-line"></div>

    {{-- ══ TEXTO DEL FOOTER — dibujado vía script PHP de DomPDF ════════════
         Necesario porque counter(pages) (CSS) no calcula el total real de
         páginas en este motor; $pdf->page_text() con {PAGE_COUNT} sí lo hace. --}}
    @php
        $piePrimero = 'Cerberus 2.0 — Sistema de Inventario y Asignaciones Tecnológicas';
        $piePropio  = ($codigoDoc ?? '') . ' · ' . ($fecha ?? now()->format('d/m/Y')) . ' · Página {PAGE_NUM} de {PAGE_COUNT}';
    @endphp
    <script type="text/php">
        if (isset($pdf)) {
            $font  = $fontMetrics->getFont('Helvetica', 'normal');
            $size  = 6;
            $color = array(0.58, 0.65, 0.71);

            $mmToPt   = 72 / 25.4;
            $marginPt = 16 * $mmToPt;
            $y        = $pdf->get_height() - (8 * $mmToPt);

            $textoIzquierda = {!! json_encode($piePrimero, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
            $pdf->page_text($marginPt, $y, $textoIzquierda, $font, $size, $color);

            $textoDerecha = {!! json_encode($piePropio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
            $anchoDerecha = $fontMetrics->getTextWidth($textoDerecha, $font, $size);
            $xDerecha     = $pdf->get_width() - $marginPt - $anchoDerecha;
            $pdf->page_text($xDerecha, $y, $textoDerecha, $font, $size, $color);
        }
    </script>

</body>

</html>
