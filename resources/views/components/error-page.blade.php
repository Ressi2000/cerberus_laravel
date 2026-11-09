@props([
    'badge',   // Nombre del archivo dentro de images/banner-error/
    'code',    // Código HTTP, ej. 404
    'title',
    'message',
])

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {{ $code }} — Cerberus 2.0</title>

    <link rel="icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">

    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            background: url('{{ asset('images/banner-error/fondo-error.jpg') }}') center center / cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        /*
         * El fondo trae mitad clara / mitad oscura en diagonal, así que el
         * texto no puede depender de qué mitad le toque detrás: la tarjeta
         * lleva su propio vidrio oscuro translúcido para garantizar
         * contraste sin importar el tema del sistema ni el tamaño de pantalla.
         */
        .error-card {
            width: 100%;
            max-width: 420px;
            text-align: center;
            padding: 2.5rem 2rem;
            border-radius: 24px;
            background: rgba(6, 9, 19, 0.68);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
        }

        .error-badge {
            width: min(100%, 260px);
            height: auto;
            margin: 0 auto 1.25rem;
            display: block;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 0.5rem;
        }

        .error-message {
            font-size: 0.95rem;
            color: #cbd5e1;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="error-card">
        <img src="{{ asset('images/banner-error/' . $badge) }}" alt="Error {{ $code }}" class="error-badge">
        <p class="error-title">{{ $title }}</p>
        <p class="error-message">{{ $message }}</p>
    </div>
</body>

</html>
