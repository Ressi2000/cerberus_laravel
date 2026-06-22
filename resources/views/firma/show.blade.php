<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Firmar documento — Cerberus 2.0</title>
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
    }
    .card-header h1 { font-size: 16px; margin: 0 0 2px; }
    .card-header p { font-size: 12px; margin: 0; color: #94A3B8; }
    .card-body { padding: 24px; }
    .field { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #E2E8F0; font-size: 14px; }
    .field .label { color: #64748B; }
    .field .value { font-weight: 600; text-align: right; }
    .canvas-wrap {
        margin-top: 20px;
        border: 1px dashed #CBD5E1;
        border-radius: 8px;
        background: #F8FAFC;
        touch-action: none;
    }
    canvas { display: block; width: 100%; height: 220px; cursor: crosshair; }
    .actions { display: flex; gap: 10px; margin-top: 14px; }
    button {
        flex: 1;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }
    #btn-limpiar { background: #E2E8F0; color: #1E293B; }
    #btn-confirmar { background: #1B3A5C; color: #fff; }
    #btn-confirmar:disabled { background: #94A3B8; cursor: not-allowed; }
    .nota {
        margin-top: 16px;
        font-size: 12px;
        color: #94A3B8;
        line-height: 1.5;
    }
    .error { color: #DC2626; font-size: 13px; margin-top: 10px; display: none; }
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h1>Firma digital — {{ $tituloDocumento }}</h1>
        <p>Cerberus 2.0 — Sistema de Inventario y Asignaciones</p>
    </div>
    <div class="card-body">
        <div class="field">
            <span class="label">Folio</span>
            <span class="value">{{ $folio }}</span>
        </div>
        <div class="field">
            <span class="label">Firmante</span>
            <span class="value">{{ $firmante?->name ?? '—' }}</span>
        </div>
        <div class="field">
            <span class="label">Rol</span>
            <span class="value">{{ ucfirst($rol) }}</span>
        </div>

        <p style="font-size: 13px; color: #475569; margin-top: 16px;">
            Dibuja tu firma dentro del recuadro con el dedo o el mouse.
        </p>

        <div class="canvas-wrap">
            <canvas id="lienzo"></canvas>
        </div>

        <p class="error" id="error-vacio">Debes firmar antes de continuar.</p>

        <div class="actions">
            <button type="button" id="btn-limpiar">Limpiar</button>
            <button type="button" id="btn-confirmar" disabled>Confirmar firma</button>
        </div>

        <form id="form-firma" method="POST" action="{{ url()->full() }}">
            @csrf
            <input type="hidden" name="imagen" id="input-imagen">
        </form>

        <p class="nota">
            Esta firma es un trazo simple (no certificada digitalmente) y se
            registra junto con tu dirección IP y fecha/hora. No reemplaza la
            firma manuscrita en papel cuando esta sea exigida.
        </p>
    </div>
</div>

<script>
(function () {
    const canvas = document.getElementById('lienzo');
    const ctx = canvas.getContext('2d');
    const btnLimpiar = document.getElementById('btn-limpiar');
    const btnConfirmar = document.getElementById('btn-confirmar');
    const errorVacio = document.getElementById('error-vacio');
    const form = document.getElementById('form-firma');
    const inputImagen = document.getElementById('input-imagen');

    let dibujando = false;
    let trazado = false;

    function ajustarTamano() {
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.strokeStyle = '#1E293B';
        ctx.lineWidth = 2.2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    function posicion(evento) {
        const rect = canvas.getBoundingClientRect();
        const punto = evento.touches ? evento.touches[0] : evento;
        return {
            x: punto.clientX - rect.left,
            y: punto.clientY - rect.top,
        };
    }

    function iniciar(evento) {
        evento.preventDefault();
        dibujando = true;
        const { x, y } = posicion(evento);
        ctx.beginPath();
        ctx.moveTo(x, y);
    }

    function mover(evento) {
        if (! dibujando) return;
        evento.preventDefault();
        const { x, y } = posicion(evento);
        ctx.lineTo(x, y);
        ctx.stroke();
        if (! trazado) {
            trazado = true;
            btnConfirmar.disabled = false;
            errorVacio.style.display = 'none';
        }
    }

    function terminar() {
        dibujando = false;
    }

    canvas.addEventListener('mousedown', iniciar);
    canvas.addEventListener('mousemove', mover);
    window.addEventListener('mouseup', terminar);
    canvas.addEventListener('touchstart', iniciar, { passive: false });
    canvas.addEventListener('touchmove', mover, { passive: false });
    canvas.addEventListener('touchend', terminar);

    btnLimpiar.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        trazado = false;
        btnConfirmar.disabled = true;
    });

    btnConfirmar.addEventListener('click', function () {
        if (! trazado) {
            errorVacio.style.display = 'block';
            return;
        }
        btnConfirmar.disabled = true;
        inputImagen.value = canvas.toDataURL('image/png');
        form.submit();
    });

    window.addEventListener('resize', function () {
        const datos = trazado ? canvas.toDataURL('image/png') : null;
        ajustarTamano();
        if (datos) {
            const img = new Image();
            img.onload = function () {
                ctx.drawImage(img, 0, 0, canvas.width / (window.devicePixelRatio || 1), canvas.height / (window.devicePixelRatio || 1));
            };
            img.src = datos;
        }
    });

    ajustarTamano();
})();
</script>

</body>
</html>
