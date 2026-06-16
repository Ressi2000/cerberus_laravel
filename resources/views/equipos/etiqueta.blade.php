<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta — {{ $equipo->codigo_interno }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --c-dark:    #0D1B2A;
            --c-mid:     #1B263B;
            --c-steel:   #415A77;
            --c-accent:  #778DA9;
            --c-light:   #A9D6E5;
            --c-primary: #1E40AF;
            --c-hover:   #1E3A8A;
        }

        body {
            font-family: 'Segoe UI', system-ui, Arial, sans-serif;
            background: var(--c-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 40px 16px;
            gap: 24px;
        }

        /* ── Tarjeta principal ──────────────────────────── */
        .label-card {
            width: 260px;
            background: var(--c-mid);
            border: 1.5px solid var(--c-steel);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }

        /* ── Header ─────────────────────────────────────── */
        .label-header {
            background: var(--c-primary);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand        { font-size: 13px; font-weight: 900; color: #fff; letter-spacing: 2px; }
        .brand-sub    { font-size: 6.5px; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: 1px; margin-top: 1px; }
        .cat-badge {
            font-size: 8px; font-weight: 600;
            color: rgba(255,255,255,.9);
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            padding: 2px 8px; border-radius: 20px;
        }

        /* ── Código interno ──────────────────────────────── */
        .label-code {
            padding: 12px 12px 10px;
            text-align: center;
            border-bottom: 1px solid var(--c-steel);
        }
        .code-value {
            font-size: 20px; font-weight: 900;
            color: #fff; letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }

        /* ── QR + Barcode ────────────────────────────────── */
        .codes-row {
            display: flex;
            align-items: center;
            justify-content: space-around;
            padding: 12px 10px;
            gap: 6px;
        }
        .code-block { display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .code-block canvas, .code-block svg { border-radius: 4px; }
        .code-label {
            font-size: 6.5px; font-weight: 600;
            color: var(--c-accent);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .v-divider { width: 1px; height: 64px; background: var(--c-steel); opacity: 0.5; }

        /* ── Info strip (fechas) ─────────────────────────── */
        .label-info {
            display: flex;
            border-top: 1px solid var(--c-steel);
        }
        .info-cell {
            flex: 1; padding: 7px 10px;
            border-right: 1px solid var(--c-steel);
        }
        .info-cell:last-child { border-right: none; }
        .info-label { font-size: 6.5px; color: var(--c-accent); text-transform: uppercase; letter-spacing: .8px; display: block; margin-bottom: 2px; }
        .info-value { font-size: 10px; color: var(--c-light); font-weight: 600; }

        /* ── Botón imprimir ──────────────────────────────── */
        .btn-print {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 28px;
            background: var(--c-primary); color: #fff;
            border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        .btn-print:hover { background: var(--c-hover); }
        .btn-print svg { width: 17px; height: 17px; }

        /* ── Print ───────────────────────────────────────── */
        @media print {
            body { background: #fff; padding: 0; min-height: unset; }
            .label-card { box-shadow: none; border: 1.5px solid #333; border-radius: 8px; width: 250px; }
            .label-header { background: #1E40AF; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .code-value   { color: #111; }
            .code-label   { color: #6b7280; }
            .label-info   { border-top-color: #d1d5db; }
            .info-cell    { border-right-color: #d1d5db; }
            .info-label   { color: #6b7280; }
            .info-value   { color: #111; }
            .btn-print    { display: none; }
        }
    </style>
</head>
<body>

<div class="label-card">

    {{-- Header --}}
    <div class="label-header">
        <div>
            <div class="brand">CERBERUS</div>
            <div class="brand-sub">Inventario Tecnológico</div>
        </div>
        <div class="cat-badge">{{ $equipo->categoria->nombre }}</div>
    </div>

    {{-- Código interno --}}
    <div class="label-code">
        <div class="code-value">{{ $equipo->codigo_interno }}</div>
    </div>

    {{-- QR + Código de barras --}}
    <div class="codes-row">
        <div class="code-block">
            <div class="code-label">Historial / Ficha</div>
            <canvas id="qr-canvas"></canvas>
        </div>
        <div class="v-divider"></div>
        <div class="code-block">
            <div class="code-label">ID de equipo</div>
            <svg id="barcode"></svg>
        </div>
    </div>

    {{-- Fechas opcionales --}}
    @if ($equipo->fecha_adquisicion || $equipo->fecha_garantia_fin)
        <div class="label-info">
            @if ($equipo->fecha_adquisicion)
                <div class="info-cell">
                    <span class="info-label">Adquisición</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('d/m/Y') }}</span>
                </div>
            @endif
            @if ($equipo->fecha_garantia_fin)
                <div class="info-cell">
                    <span class="info-label">Garantía hasta</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($equipo->fecha_garantia_fin)->format('d/m/Y') }}</span>
                </div>
            @endif
        </div>
    @endif

</div>

<button class="btn-print" onclick="window.print()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="6 9 6 2 18 2 18 9"/>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8"/>
    </svg>
    Imprimir etiqueta
</button>

<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    QRCode.toCanvas(document.getElementById('qr-canvas'), @js(\Illuminate\Support\Facades\URL::signedRoute('admin.equipos.historial.qr', ['equipo' => $equipo->id])), {
        width: 78, margin: 1,
        color: { dark: '#111827', light: '#ffffff' }
    })

    JsBarcode('#barcode', "{{ $equipo->id }}", {
        format: 'CODE128', width: 1.4, height: 40,
        displayValue: true, fontSize: 9, margin: 3,
        background: '#ffffff', lineColor: '#111827',
    })
</script>
</body>
</html>
