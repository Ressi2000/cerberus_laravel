# Fase 2 — Firma digital remota en planillas (planificación)

Estado: **propuesta, sin construir**. Este documento define el diseño antes de escribir código, para que el equipo evalúe implicaciones legales, operativas y técnicas.

No confundir con la Fase 1 ya construida: el QR + ruta `verificacion.show` (firmada con `signed` middleware de Laravel) solo confirma que el documento corresponde a un registro vigente. Es un mecanismo distinto y no se toca en esta fase — la firma digital se construye **además**, no en lugar de.

---

## 1. Objetivo

Hoy: se genera el PDF → se imprime → el receptor y su jefe firman a mano → el papel se archiva físicamente.

Propuesta: el QR de la planilla (o uno adicional, ver §4) lleva a un formulario móvil donde el receptor y su jefe firman con el dedo/mouse en ~2 minutos. Al completarse ambas firmas, el sistema genera un PDF con las firmas embebidas y lo envía por correo al receptor, al jefe y lo archiva en el sistema. El papel físico pasa a ser un respaldo opcional, no obligatorio.

---

## 2. Pregunta previa — validez legal (bloqueante)

**Esto no lo resuelve el código.** Antes de construir nada hay que confirmar con legal/RRHH:

- ¿Una firma trazada en pantalla (sin certificado digital, sin proveedor acreditado) tiene el mismo valor probatorio que la firma manuscrita para estos documentos (asignación de activos, préstamos, traslados)?
- ¿La empresa exige firma electrónica **calificada/certificada** (ej. proveedor como DocuSign, Firmafy) para que el documento sea válido en una auditoría o disputa laboral, o basta una firma simple + registro de auditoría (IP, dispositivo, timestamp, geolocalización opcional)?
- ¿Aplica alguna normativa local específica (ej. ley de firma electrónica del país) que condicione el formato?

Esta decisión determina cuál de las dos alternativas técnicas del §3 aplica. El resto del documento asume que se opta por la alternativa 1 (más barata) salvo que legal exija lo contrario.

---

## 3. Alternativas técnicas

### Alternativa 1 — Firma simple in-house (recomendada para empezar)
Un canvas HTML5 captura el trazo (igual que un recibo de delivery). Se guarda como imagen PNG + metadata de auditoría (IP, user agent, timestamp, hash del documento firmado) en una tabla nueva. Costo: solo desarrollo, sin dependencias de pago. Valor legal: el de una firma simple — sirve como evidencia de consentimiento, pero no es "firma electrónica avanzada/calificada".

### Alternativa 2 — Proveedor externo de firma electrónica
Integración por API con un proveedor certificado (DocuSign, Firmafy, Adobe Sign, etc.). Mayor robustez legal y trazabilidad, pero costo recurrente (por documento o por mes) y dependencia de un servicio externo. Requiere evaluación comercial aparte.

**Recomendación**: construir la Alternativa 1 primero como prueba piloto interna; si legal determina que se necesita valor probatorio fuerte, migrar a la Alternativa 2 reutilizando el mismo flujo de UI (solo cambia el backend de captura de firma).

---

## 4. Flujo propuesto (Alternativa 1)

1. Al generar la planilla, además del QR de verificación (Fase 1), se genera un **enlace de firma** (`URL::signedRoute`, expira en N horas — sugerido 48h) y se muestra como un segundo QR o un botón "Firmar digitalmente" en el PDF, o se envía directo por correo/WhatsApp sin necesidad de imprimir.
2. El receptor abre el enlace desde su celular: ve un resumen de solo lectura del documento (tipo, equipos/activos, empresa, fecha) y un canvas para firmar.
3. Al firmar, el sistema registra: imagen de la firma, IP, user agent, timestamp.
4. Si el documento requiere firma del jefe (ver §5 — no siempre hay `jefe_id`), se notifica al jefe (reutilizando el sistema de `Notifications` ya existente en `app/Notifications/`) con su propio enlace firmado.
5. Cuando ambas firmas (o la única requerida) están completas, un job genera el PDF final con las firmas embebidas en los espacios `.firma-cell` ya existentes en el layout, y lo envía por correo al receptor + jefe (vía Notification con canal `mail`, igual que el resto del sistema) y lo adjunta/archiva en el registro (Asignacion/Prestamo/Traslado).
6. Estado intermedio visible en el sistema: "Pendiente de firma receptor" → "Pendiente de firma jefe" → "Firmado". La planilla impresa sigue funcionando igual si nadie usa el flujo digital (no es obligatorio).

---

## 5. Casos límite a resolver en el diseño

- **Sin jefe asignado** (`jefe_id` nullable en `users` — confirmado que existe gente sin jefe, ej. niveles altos). El flujo debe permitir continuar solo con la firma del receptor, o requerir que se asigne un jefe antes de habilitar la firma digital — a decidir.
- **Receptor tipo "área común"** (sin usuario personal, `tipoReceptor() === 'area'`): el firmante natural es `areaResponsable` (que sí es un `User` con email). No hay "receptor real" individual — confirmar si esto es aceptable para el negocio o si un área común simplemente no aplica para firma digital y sigue usando papel.
- **Enlace expirado o no firmado a tiempo**: definir si se reenvía automáticamente (recordatorio a las 24h) o si vuelve al flujo de papel.
- **Egreso**: ya no tiene QR de verificación (Fase 1) porque no hay un registro único persistido. Por la misma razón, tampoco aplicaría a firma digital sin antes resolver ese modelo de datos.
- **Multi-dispositivo / multi-sesión**: si el receptor abre el enlace en dos dispositivos, solo debe poder firmar una vez (enlace de un solo uso tras completarse).

---

## 6. Qué ya existe y se puede reutilizar

- **Notificaciones**: sistema completo en `app/Notifications/` con canales `database`, `broadcast`, `mail` — no hay que construir plomería de correo desde cero, solo nuevas clases de notificación ("Te toca firmar", "Documento firmado por ambas partes").
- **Rutas firmadas**: mismo patrón ya usado en `verificacion.show` y `equipos.historial.qr` — se reutiliza para el enlace de firma.
- **Layout de planillas**: los `.firma-cell` en `resources/views/planillas/layout.blade.php` ya tienen el espacio reservado para firma — ahí se insertaría la imagen de la firma capturada en vez (o además) de dejarlo en blanco para firma manuscrita.

## 7. Qué falta construir (alto nivel, sin estimar tiempos todavía)

- Migración + modelo `Firma` (o similar): documento polimórfico (`firmable_type`, `firmable_id`), firmante, imagen, IP, user agent, timestamp, estado.
- Controlador + vista pública de firma (canvas, ej. librería `signature_pad` vía npm).
- Notificaciones nuevas: solicitud de firma, recordatorio, confirmación final.
- Job para regenerar el PDF con las firmas embebidas y reenviarlo por correo.
- Estado visible en el sistema (badge "Pendiente de firma" / "Firmado") en las vistas de Asignación/Préstamo/Traslado.
- Política de expiración/reenvío de enlaces.

---

## 8. Próximo paso

Confirmar con legal/RRHH la pregunta del §2. Mientras eso se resuelve, no hay código que escribir — este documento es la base para esa conversación y para retomar la implementación una vez haya respuesta.
