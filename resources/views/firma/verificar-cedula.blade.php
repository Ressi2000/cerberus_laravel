<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificar identidad — Cerberus 2.0</title>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #F1F5F9;
        margin: 0;
        padding: 40px 16px;
        color: #1E293B;
    }
    .card {
        max-width: 420px;
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
    }
    .card-header h1 { font-size: 16px; margin: 0 0 2px; }
    .card-header p { font-size: 12px; margin: 0; color: #94A3B8; }
    .card-body { padding: 24px; }
    .field { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #E2E8F0; font-size: 14px; }
    .field .label { color: #64748B; }
    .field .value { font-weight: 600; text-align: right; }
    label { display: block; font-size: 13px; color: #475569; margin: 20px 0 6px; }
    input[type="text"] {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 14px;
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        font-size: 15px;
    }
    button {
        margin-top: 16px;
        width: 100%;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        background: #1B3A5C;
        color: #fff;
    }
    .error { color: #DC2626; font-size: 13px; margin-top: 8px; }
    .nota { margin-top: 16px; font-size: 12px; color: #94A3B8; line-height: 1.5; }
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h1>Verificar identidad — {{ $tituloDocumento }}</h1>
        <p>Cerberus 2.0 — Sistema de Inventario y Asignaciones</p>
    </div>
    <div class="card-body">
        <div class="field">
            <span class="label">Folio</span>
            <span class="value">{{ $folio }}</span>
        </div>
        <div class="field">
            <span class="label">Firmante esperado</span>
            <span class="value">{{ $firmante?->name ?? '—' }}</span>
        </div>

        <form method="POST" action="{{ url()->full() }}">
            @csrf
            <label for="cedula">Antes de firmar, confirma tu cédula</label>
            <input type="text" id="cedula" name="cedula" inputmode="numeric" autofocus required>
            @error('cedula')
                <p class="error">{{ $message }}</p>
            @enderror
            <button type="submit">Continuar</button>
        </form>

        <p class="nota">
            Esto confirma que eres tú quien va a firmar este documento, ya que
            el enlace puede ser visto por más personas además de ti.
        </p>
    </div>
</div>

</body>
</html>
