<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña | Cerberus</title>
</head>
<body style="margin:0;padding:0;background-color:#0D1B2A;font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0D1B2A;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#1B263B;border-radius:12px;padding:32px;color:#E0E1DD;">
                
                {{-- Logo --}}
                <tr>
                    <td align="center" style="padding-bottom:24px;">
                        <img src="{{ asset('images/cerberus-logo.png') }}" alt="Cerberus" height="60">
                    </td>
                </tr>

                {{-- Título --}}
                <tr>
                    <td style="font-size:22px;font-weight:bold;color:#A9D6E5;padding-bottom:16px;">
                        Restablecer contraseña
                    </td>
                </tr>

                {{-- Saludo --}}
                <tr>
                    <td style="font-size:15px;line-height:1.6;padding-bottom:20px;">
                        Hola <strong>{{ $user->name }}</strong>,<br><br>
                        Recibimos una solicitud para restablecer tu contraseña en <strong>Cerberus</strong>.
                    </td>
                </tr>

                {{-- Botón --}}
                <tr>
                    <td align="center" style="padding:24px 0;">
                        <a href="{{ $url }}"
                           style="
                               background-color:#1E40AF;
                               color:#FFFFFF;
                               text-decoration:none;
                               padding:14px 28px;
                               border-radius:8px;
                               font-weight:bold;
                               display:inline-block;
                           ">
                            Restablecer contraseña
                        </a>
                    </td>
                </tr>

                {{-- Info --}}
                <tr>
                    <td style="font-size:14px;line-height:1.6;color:#CBD5E1;padding-bottom:20px;">
                        Este enlace expirará en {{ $expire }} minutos.<br>
                        Si no solicitaste este cambio, puedes ignorar este mensaje.
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="font-size:13px;color:#94A3B8;border-top:1px solid #334155;padding-top:16px;">
                        © {{ now()->year }} Cerberus · Sistema de Gestión de Activos Tecnológicos
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
