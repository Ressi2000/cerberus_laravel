<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas de piezas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, Arial, sans-serif; background: #e5e7eb; color: #111827; padding: 24px 16px; }

        .barra { max-width: 900px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .barra p { font-size: 14px; color: #374151; }
        .btn { display: inline-flex; align-items: center; gap: 8px; background: #1E40AF; color: #fff; border: 0; border-radius: 8px;
               padding: 10px 18px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #1E3A8A; }

        .hoja { max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fill, 64mm); gap: 4mm; justify-content: center; }

        /* Etiqueta de 64 × 30 mm (cabe en rollos y en hojas de etiquetas comunes) */
        .etiqueta { width: 64mm; height: 30mm; background: #fff; border: 0.3mm solid #9ca3af; border-radius: 2mm;
                    display: flex; align-items: center; gap: 2mm; padding: 2mm; overflow: hidden; break-inside: avoid; }
        .etiqueta img { width: 24mm; height: 24mm; flex-shrink: 0; image-rendering: pixelated; }
        .datos { min-width: 0; display: flex; flex-direction: column; gap: 0.8mm; }
        .familia { font-size: 6.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4pt; color: #1E40AF; }
        .codigo { font-family: 'Consolas', 'Courier New', monospace; font-size: 10pt; font-weight: 700; overflow-wrap: anywhere; line-height: 1.1; }
        .codigo.largo { font-size: 8pt; }
        .codigo.muy-largo { font-size: 6.5pt; }
        .variante { font-size: 6.5pt; color: #374151; line-height: 1.15; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .pie { font-size: 5.5pt; color: #6b7280; }

        @media print {
            body { background: #fff; padding: 0; }
            .barra { display: none; }
            .hoja { max-width: none; gap: 2mm; justify-content: start; }
            .etiqueta { border-color: #d1d5db; }
            @page { margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <p><b>{{ $piezas->count() }}</b> etiqueta(s). El QR tiene el mismo código: escanéalo en el buscador del Almacén para abrir la pieza.</p>
        <button class="btn" onclick="window.print()">Imprimir</button>
    </div>

    <div class="hoja">
        @foreach ($piezas as $pieza)
            <div class="etiqueta">
                <img src="{{ $pieza->qr }}" alt="QR {{ $pieza->identificador }}">
                <div class="datos">
                    <span class="familia">{{ $pieza->componenteAlmacen?->familia?->nombre ?? 'Pieza' }}</span>
                    @php $codigo = $pieza->identificador ?: 'PIEZA-' . $pieza->id; @endphp
                    <span @class(['codigo', 'largo' => mb_strlen($codigo) > 13 && mb_strlen($codigo) <= 18, 'muy-largo' => mb_strlen($codigo) > 18])>{{ $codigo }}</span>
                    <span class="variante">{{ $pieza->componenteAlmacen?->nombre ?? $pieza->descripcion() }}</span>
                    <span class="pie">{{ $pieza->empresa?->nombre }} · N.º {{ $pieza->id }}</span>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
