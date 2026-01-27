<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contraseña restablecida | Cerberus</title>
</head>
<body style="background-color:#0D1B2A; font-family: Arial, sans-serif; padding:40px;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="600" style="background:#1B263B; border-radius:12px; padding:32px; color:#E0E1DD;">
                    
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <img src="{{ asset('images/cerberus-logo.png') }}"
                                 alt="Cerberus"
                                 width="120">
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <h2 style="color:#A9D6E5;">Contraseña restablecida</h2>

                            <p>Hola <strong>{{ $user->name }}</strong>,</p>

                            <p>
                                Te informamos que la contraseña de tu cuenta en
                                <strong>Cerberus</strong> fue restablecida correctamente.
                            </p>

                            <p>
                                Si realizaste esta acción, no necesitas hacer nada más.
                            </p>

                            <p style="color:#FCA5A5;">
                                ⚠️ Si no reconoces esta acción, comunícate de inmediato
                                con el administrador del sistema.
                            </p>

                            <p style="margin-top:32px;">
                                — Equipo Cerberus
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
