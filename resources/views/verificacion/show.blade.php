<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificación de Documento — Cerberus 2.0</title>
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
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .badge-icon.ok { background: #16A34A; }
    .badge-icon.alterado { background: #DC2626; }
    .card-header h1 {
        font-size: 16px;
        margin: 0 0 2px;
    }
    .card-header p {
        font-size: 12px;
        margin: 0;
        color: #94A3B8;
    }
    .card-body {
        padding: 24px;
    }
    .status-line {
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 14px;
        color: #065F46;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .status-line.alterado {
        background: #FEF2F2;
        border-color: #FECACA;
        color: #991B1B;
    }
    .field {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #E2E8F0;
        font-size: 14px;
    }
    .field:last-child { border-bottom: none; }
    .field .label { color: #64748B; }
    .field .value { font-weight: 600; text-align: right; }
    .folio-box {
        margin-top: 20px;
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 12px;
        color: #64748B;
        text-align: center;
    }
    .folio-box strong {
        color: #1E293B;
        letter-spacing: 1px;
        font-size: 13px;
    }
    .footer-note {
        text-align: center;
        font-size: 11px;
        color: #94A3B8;
        margin-top: 20px;
    }
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <div class="badge-icon {{ $esVigente ? 'ok' : 'alterado' }}">{{ $esVigente ? '✓' : '!' }}</div>
        <div>
            <h1>{{ $esVigente ? 'Documento verificado' : 'Documento no vigente' }}</h1>
            <p>Cerberus 2.0 — Sistema de Inventario y Asignaciones</p>
        </div>
    </div>
    <div class="card-body">

        <div class="status-line {{ $esVigente ? '' : 'alterado' }}">
            @if ($esVigente)
                ✓ Este documento corresponde a un registro vigente del sistema
            @else
                ! Este documento ya no corresponde a un registro vigente del sistema
            @endif
        </div>

        <div class="field">
            <span class="label">Tipo de documento</span>
            <span class="value">{{ $tituloDocumento }}</span>
        </div>
        <div class="field">
            <span class="label">Código</span>
            <span class="value">{{ $codigoDoc }}</span>
        </div>
        <div class="field">
            <span class="label">Receptor</span>
            <span class="value">{{ $receptor ?? '—' }}</span>
        </div>
        <div class="field">
            <span class="label">Empresa</span>
            <span class="value">{{ $empresa ?? '—' }}</span>
        </div>
        <div class="field">
            <span class="label">Fecha de generación</span>
            <span class="value">{{ $fechaDocumento ? \Illuminate\Support\Carbon::parse($fechaDocumento)->format('d/m/Y') : '—' }}</span>
        </div>
        <div class="field">
            <span class="label">Estado actual</span>
            <span class="value" style="color: {{ $esVigente ? '#16A34A' : '#DC2626' }};">{{ $estado ?? '—' }}</span>
        </div>

        <div class="folio-box">
            Folio de verificación<br>
            <strong>{{ $folio }}</strong>
        </div>

        <p class="footer-note">
            Si este documento fue modificado o ya no corresponde a un registro<br>
            vigente, esta página lo indicará en rojo.
        </p>
    </div>
</div>

</body>
</html>
