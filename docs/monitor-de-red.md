# Monitor de red

Módulo de monitoreo de disponibilidad de equipos de red (routers, switches,
servidores, enlaces de ISP…) integrado en Cerberus. Parte del `monitor.php`
standalone (v3) que hizo un compañero y lo lleva al estándar de Cerberus.

Menú: **Monitoreo → Monitor de red / Hosts / Historial de caídas / Test de velocidad**
(el iframe de Grafana sigue en **Monitoreo → Monitoreos Web**).

---

## Qué hace

| Función | Detalle |
|---|---|
| Revisión automática | Cada minuto, desde el scheduler (`cerberus:monitor-red`). Ping a todos los hosts **en paralelo**; si el ping falla y el host tiene puerto TCP de respaldo, prueba ese puerto. |
| Confirmación de caídas | Un host se declara **Caído** recién tras `MONITOR_RED_FALLOS_PARA_CAIDA` revisiones fallidas seguidas (2 por defecto). La caída se fecha en el **primer** fallo, así la duración y la disponibilidad son exactas. |
| Alertas | Notificación de Cerberus (campana + correo + broadcast) a los analistas de la empresa y a los administradores, solo al caer y al recuperarse. Opcional: sonido y notificación de escritorio mientras la página está abierta. |
| Historial | Cada caída con inicio, fin y duración. Se conserva aunque el host se quite del monitoreo. Exportable a Excel con los filtros aplicados. |
| Dashboard | KPIs, disponibilidad 24 h / 7 d / 30 d, latencia por hora, disponibilidad por tipo y por proveedor, hosts más lentos, más caídas en 30 días. |
| Hosts | Lista o cuadros (con sparkline), filtros, orden, selección múltiple, alta individual o importación masiva (pegar desde Excel), pausa por mantenimiento, vínculo opcional con un equipo del inventario. |
| Test de velocidad | Medido por el navegador contra speed.cloudflare.com: latencia, jitter, bajada, subida e ISP. Se guarda por empresa y avisa si cambió el proveedor respecto al test anterior. |

## Puesta en marcha (producción)

```bash
php artisan migrate
php artisan db:seed --class=MonitorRedPermisosSeeder
php artisan permission:cache-reset
npm run build
```

Requisitos del servidor:

- **Scheduler activo** (`* * * * * php artisan schedule:run`), ya necesario para el resto de Cerberus.
- **Worker de colas** para el envío de las notificaciones (son `ShouldQueue`, como las demás).
- PHP con `proc_open` habilitado y el binario `ping` instalado (`apt install iputils-ping`).
  Si el servidor no puede hacer ICMP, configurar un puerto TCP de respaldo en cada host.

Variables opcionales (`config/monitor_red.php`):

| Variable | Defecto | Uso |
|---|---|---|
| `MONITOR_RED_TIMEOUT` | 2 | Segundos de espera por host |
| `MONITOR_RED_FALLOS_PARA_CAIDA` | 2 | Fallos seguidos para declarar caída |
| `MONITOR_RED_CONCURRENCIA` | 20 | Pings simultáneos |
| `MONITOR_RED_INTERVALO_PANTALLA` | 15 | Refresco de pantalla (s) |
| `MONITOR_RED_RETENCION_DIAS` | 15 | Días de mediciones de latencia (las caídas no se purgan) |
| `MONITOR_RED_NOTIFICAR` | true | Enviar notificaciones de caída/recuperación |

## Diferencias con el `monitor.php` original

Lo que se conservó: ping + respaldo TCP, historial de caídas, disponibilidad
24 h / 7 d / 30 d, dashboard, vista lista/cuadros, filtros y orden,
importación masiva, alertas con sonido, exportación y test de velocidad.

Lo que se corrigió o mejoró:

1. **Seguridad.** El original quedaba abierto por defecto (usuario/clave vacíos):
   cualquiera con la URL podía agregar o borrar equipos y hacer que el servidor
   ejecutara pings (`?api=chequear` por GET, sin CSRF), y la base SQLite quedaba
   dentro de la carpeta pública. Ahora usa el login, roles, policy, CSRF y
   auditoría de Cerberus; el comando `ping` se ejecuta sin shell (arreglo de
   argumentos) y el host se valida antes de guardarse y antes de revisarse.
2. **El monitoreo dependía de tener la página abierta.** El ciclo lo hacía el
   navegador; sin la página (o sin configurar el cron aparte) no se registraban
   caídas. Ahora lo hace siempre el scheduler, y la pantalla solo lee.
3. **Revisiones en paralelo.** El cron original revisaba uno por uno: con 100
   hosts caídos y timeout de 2 s tardaba más de 3 minutos (se solapaban las
   ejecuciones). Ahora los pings van en paralelo (≈ 2 s por cada 20 hosts) y
   las conexiones TCP son asíncronas. Además hay un lock: el scheduler y
   "Revisar ahora" nunca corren a la vez ni duplican caídas.
4. **Falsos positivos.** Un solo paquete perdido generaba caída y alerta. Ahora
   se exige confirmación (N fallos seguidos), sin perder precisión en la duración.
5. **Alertas reales.** Antes solo sonaba si alguien tenía la pestaña abierta. Ahora
   llegan por campana y correo a quien corresponde, una vez por caída.
6. **Multiempresa.** Cada host pertenece a una empresa; el analista solo ve y
   gestiona los de su empresa activa.
7. **Pausa por mantenimiento.** Un host pausado no se revisa ni genera alertas.
8. **Integración con inventario.** Un host puede vincularse a un equipo de Cerberus.
9. **Privacidad del test de velocidad.** El original enviaba la IP pública a
   ipwho.is e ipapi.co; ahora solo se contacta speed.cloudflare.com, que ya
   se usaba para medir y también identifica al ISP.

Limitaciones conocidas:

- El test de velocidad mide la conexión del **navegador** (la sede desde donde
  se abre la página), no la del servidor. La pantalla lo aclara.
- Mientras un host está pausado ese tiempo no cuenta como caído.
- "Revisar ahora" corre dentro de la petición web: con cientos de hosts puede
  tardar varios segundos (el scheduler no tiene ese límite).

## Archivos

```
app/Console/Commands/MonitorRed.php               cerberus:monitor-red [--purgar]
app/Services/MonitorRed/SondaRed.php              ping en paralelo + TCP asíncrono
app/Services/MonitorRed/MonitorRedService.php     revisión, lock y notificaciones
app/Services/MonitorRed/EstadisticasMonitor.php   disponibilidad, latencia por hora, sparklines
app/Models/MonitorHost.php                        registrarResultado(): máquina de estados
app/Models/MonitorCaida.php · MonitorMedicion.php · MonitorPruebaVelocidad.php
app/Policies/MonitorHostPolicy.php
app/Livewire/MonitorRed/*                         dashboard, tabla, modales, historial, velocidad
resources/js/modules/monitor-red.js               alertas en pantalla + test de velocidad
config/monitor_red.php
tests/Feature/MonitorRed/MonitorRedTest.php
```
