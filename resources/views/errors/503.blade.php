<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>En mantenimiento — Cerberus 2.0</title>

    <link rel="icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/CBRS2.0favicon.ico') }}">

    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #05070b;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-mantenimiento {
            width: min(100%, 1100px);
            height: auto;
            display: block;
        }
    </style>
</head>

<body>
    <img
        src="{{ asset('images/banner-error/banner-mantenimiento1.jpg') }}"
        alt="Cerberus 2.0 en mantenimiento. Por favor espere 1 hora, la aplicación se encuentra en una actualización."
        class="banner-mantenimiento"
    >
</body>

</html>
