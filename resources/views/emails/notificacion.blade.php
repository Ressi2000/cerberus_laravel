<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0f172a; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #1e293b; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .header { background: linear-gradient(135deg, #1B3A5C 0%, #0f2744 100%); padding: 32px 40px; text-align: center; }
        .header img { height: 40px; margin-bottom: 12px; }
        .header h1 { color: #fff; font-size: 22px; margin: 0; font-weight: 700; }
        .header p { color: #94a3b8; font-size: 13px; margin: 6px 0 0; }
        .badge { display: inline-block; margin-top: 12px; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        .badge-info    { background: #0ea5e920; color: #38bdf8; border: 1px solid #0ea5e940; }
        .badge-warning { background: #f59e0b20; color: #fbbf24; border: 1px solid #f59e0b40; }
        .badge-danger  { background: #ef444420; color: #f87171; border: 1px solid #ef444440; }
        .badge-success { background: #10b98120; color: #34d399; border: 1px solid #10b98140; }
        .body { padding: 32px 40px; }
        .body p { color: #cbd5e1; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .detail-box { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 20px 24px; margin: 24px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #1e293b; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #64748b; font-size: 13px; }
        .detail-value { color: #e2e8f0; font-size: 13px; font-weight: 500; text-align: right; }
        .btn { display: inline-block; margin: 8px 0; padding: 12px 28px; background: #1B3A5C; color: #fff !important; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; border: 1px solid #2a5a8c; }
        .footer { background: #0f172a; padding: 20px 40px; text-align: center; border-top: 1px solid #1e293b; }
        .footer p { color: #475569; font-size: 12px; margin: 0; }
        .icon { font-size: 32px; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="icon">{{ $icono ?? '🔔' }}</div>
            <h1>{{ $titulo }}</h1>
            <p>{{ now()->format('d/m/Y H:i') }}</p>
            <span class="badge badge-{{ $tipo ?? 'info' }}">{{ $etiqueta ?? 'Notificación' }}</span>
        </div>

        <div class="body">
            <p>{{ $mensaje }}</p>

            @if(!empty($detalles))
            <div class="detail-box">
                @foreach($detalles as $label => $value)
                <div class="detail-row">
                    <span class="detail-label">{{ $label }}</span>
                    <span class="detail-value">{{ $value }}</span>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($url))
            <p style="text-align:center; margin-top: 24px;">
                <a href="{{ $url }}" class="btn">Ver en Cerberus →</a>
            </p>
            @endif
        </div>

        <div class="footer">
            <p>Cerberus · Sistema de Gestión de Activos IT</p>
            <p style="margin-top:4px;">Este es un mensaje automático, no responder.</p>
        </div>
    </div>
</body>
</html>
