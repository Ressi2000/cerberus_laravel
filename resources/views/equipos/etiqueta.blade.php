<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta — {{ $equipo->codigo_interno }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 24px;
        }

        .etiqueta {
            background: #fff;
            border: 2px solid #1e40af;
            border-radius: 12px;
            width: 380px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .header {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header-logo {
            font-size: 22px;
            font-weight: 900;
            color: #1e40af;
            letter-spacing: -0.5px;
        }

        .header-sub {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .codigo {
            font-size: 24px;
            font-weight: 900;
            color: #111827;
            letter-spacing: 1px;
            text-align: center;
            margin-bottom: 4px;
        }

        .categoria {
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .codes-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 14px;
        }

        .qr-section, .barcode-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .code-label {
            font-size: 9px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }

        .info-item label {
            font-size: 9px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .info-item span {
            font-size: 11px;
            color: #374151;
            font-weight: 600;
        }

        .print-btn {
            display: block;
            margin: 20px auto 0;
            padding: 8px 24px;
            background: #1e40af;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-btn:hover { background: #1e3a8a; }

        @media print {
            body { background: #fff; padding: 0; }
            .etiqueta { box-shadow: none; border-color: #000; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<div class="etiqueta">

    <div class="header">
        <div>
            <div class="header-logo">CERBERUS</div>
            <div class="header-sub">Inventario Tecnológico</div>
        </div>
    </div>

    <div class="codigo">{{ $equipo->codigo_interno }}</div>
    <div class="categoria">{{ $equipo->categoria->nombre }}</div>

    <div class="codes-row">

        <div class="qr-section">
            <div class="code-label">Historial / Ficha</div>
            <canvas id="qr-canvas"></canvas>
        </div>

        <div class="barcode-section">
            <div class="code-label">ID de equipo</div>
            <svg id="barcode"></svg>
        </div>

    </div>

    <div class="info-grid">
        @if ($equipo->empresa)
            <div class="info-item">
                <label>Empresa</label>
                <span>{{ $equipo->empresa->nombre }}</span>
            </div>
        @endif
        @if ($equipo->ubicacion)
            <div class="info-item">
                <label>Ubicación</label>
                <span>{{ $equipo->ubicacion->nombre }}</span>
            </div>
        @endif
        @if ($equipo->fecha_adquisicion)
            <div class="info-item">
                <label>Adquisición</label>
                <span>{{ \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('d/m/Y') }}</span>
            </div>
        @endif
        @if ($equipo->fecha_garantia_fin)
            <div class="info-item">
                <label>Garantía hasta</label>
                <span>{{ \Carbon\Carbon::parse($equipo->fecha_garantia_fin)->format('d/m/Y') }}</span>
            </div>
        @endif
    </div>

</div>

<button class="print-btn" onclick="window.print()">Imprimir etiqueta</button>

{{-- QR code (enlaza al historial del equipo) --}}
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
{{-- Barcode --}}
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

<script>
    const historialUrl = "{{ route('admin.equipos.show', $equipo) }}"
    const equipoId     = "{{ $equipo->id }}"

    // Generar QR
    QRCode.toCanvas(document.getElementById('qr-canvas'), historialUrl, {
        width: 120,
        margin: 1,
        color: { dark: '#111827', light: '#ffffff' }
    })

    // Generar código de barras (CODE128, encode del ID numérico)
    JsBarcode('#barcode', equipoId, {
        format:      'CODE128',
        width:       1.5,
        height:      50,
        displayValue: true,
        fontSize:    11,
        margin:      4,
        background:  '#ffffff',
        lineColor:   '#111827',
    })
</script>

</body>
</html>
