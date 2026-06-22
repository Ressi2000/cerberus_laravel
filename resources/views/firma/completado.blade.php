<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Firma registrada — Cerberus 2.0</title>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #F1F5F9;
        margin: 0;
        padding: 40px 16px;
        color: #1E293B;
    }
    .card {
        max-width: 480px;
        margin: 0 auto;
        background: #FFFFFF;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .card-header {
        background: #0F172A;
        color: #fff;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .card-header .badge-icon {
        background: #16A34A;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .card-header h1 { font-size: 16px; margin: 0 0 2px; }
    .card-header p { font-size: 12px; margin: 0; color: #94A3B8; }
    .card-body { padding: 24px; font-size: 14px; color: #475569; line-height: 1.6; }
    .field { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #E2E8F0; font-size: 14px; }
    .field:last-child { border-bottom: none; }
    .field .label { color: #64748B; }
    .field .value { font-weight: 600; text-align: right; }
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="badge-icon">✓</div>
        <div>
            <h1>Firma registrada</h1>
            <p>Cerberus 2.0 — Sistema de Inventario y Asignaciones</p>
        </div>
    </div>
    <div class="card-body">
        <p>Tu firma digital ha sido registrada correctamente.</p>
        <div class="field">
            <span class="label">Documento</span>
            <span class="value">{{ $tituloDocumento }}</span>
        </div>
        <div class="field">
            <span class="label">Folio</span>
            <span class="value">{{ $folio }}</span>
        </div>
        <p style="margin-top: 16px;">
            Cuando todas las partes hayan firmado, recibirás por correo el
            documento final en formato PDF.
        </p>
    </div>
</div>

</body>
</html>
