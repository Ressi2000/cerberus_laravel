/**
 * Monitor de red — componentes Alpine
 *
 *  monitorRedAlertas(): sonido + notificación de escritorio cuando un host
 *    cae o se recupera mientras la página está abierta (preferencia guardada
 *    en este navegador). Las alertas por campana/correo las envía el servidor.
 *
 *  pruebaVelocidad(ultima, mejor): test de velocidad medido por el navegador
 *    contra speed.cloudflare.com (latencia, jitter, bajada y subida) e
 *    identificación del proveedor (Cloudflare /meta). El resultado se guarda
 *    con $wire.guardar().
 */

const PREF = 'cerberus_monitor_red'
const leerPref = () => { try { return JSON.parse(localStorage.getItem(PREF) || '{}') } catch (e) { return {} } }
const guardarPref = (p) => { try { localStorage.setItem(PREF, JSON.stringify({ ...leerPref(), ...p })) } catch (e) {} }

let audioCtx = null
function beep(ok) {
    try {
        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)()
        const o = audioCtx.createOscillator(), g = audioCtx.createGain()
        o.frequency.value = ok ? 880 : 330
        g.gain.value = 0.08
        o.connect(g); g.connect(audioCtx.destination)
        o.start(); o.stop(audioCtx.currentTime + (ok ? 0.15 : 0.5))
    } catch (e) {}
}

export function monitorRedAlertas() {
    return {
        activas: !!leerPref().alertas,

        alternar() {
            this.activas = !this.activas
            guardarPref({ alertas: this.activas })
            if (this.activas) {
                beep(true)
                try { if (window.Notification && Notification.permission === 'default') Notification.requestPermission() } catch (e) {}
            }
        },

        avisar(cambios) {
            if (!this.activas || !Array.isArray(cambios)) return
            cambios.forEach((c) => {
                beep(c.recuperado)
                try {
                    if (window.Notification && Notification.permission === 'granted') {
                        new Notification((c.recuperado ? '✅ Recuperado: ' : '🔴 Caído: ') + c.nombre, { body: c.host })
                    }
                } catch (e) {}
            })
        },
    }
}

// ─── Test de velocidad ──────────────────────────────────────────────────────

const CF = 'https://speed.cloudflare.com'
const ANILLO = 276.46 // 2·π·44 (radio del círculo SVG)
const sleep = (ms) => new Promise((r) => setTimeout(r, ms))

async function fetchT(url, ms, opts = {}) {
    const ctl = new AbortController(), t = setTimeout(() => ctl.abort(), ms)
    try { return await fetch(url, { cache: 'no-store', ...opts, signal: ctl.signal }) } finally { clearTimeout(t) }
}

async function detectarISP() {
    try {
        const d = await (await fetchT(CF + '/meta', 6000)).json()
        if (!d.clientIp) return null
        return { ip: d.clientIp, isp: d.asOrganization || '', asn: d.asn ? String(d.asn) : '', ciudad: d.city || '', pais: d.country || '' }
    } catch (e) {
        return null
    }
}

/** Velocímetro: descarta el primer segundo (arranque TCP) para no subestimar. */
function crearMedidor(seg, onProg) {
    const t0 = performance.now()
    let total = 0, marca = null
    const s = () => (performance.now() - t0) / 1000
    const vel = () => {
        const x = s()
        return marca ? (total - marca.b) * 8 / 1e6 / Math.max(x - marca.t, 0.001) : total * 8 / 1e6 / Math.max(x, 0.001)
    }
    const tick = setInterval(() => {
        if (!marca && s() >= 1) marca = { b: total, t: s() }
        onProg(vel(), Math.min(1, s() / seg))
    }, 250)
    return { add: (n) => { total += n }, parar: () => { clearInterval(tick); return { mbps: vel(), bytes: total } } }
}

async function medirPing() {
    const t = []
    for (let i = 0; i < 10; i++) {
        const a = performance.now()
        try { await (await fetchT(`${CF}/__down?bytes=0&r=${Math.random()}`, 5000)).arrayBuffer() } catch (e) { continue }
        t.push(performance.now() - a)
    }
    t.shift() // la primera incluye DNS + TLS
    if (t.length < 3) throw new Error('El servidor de pruebas no responde (¿sin internet o bloqueado por el firewall?)')
    const orden = [...t].sort((a, b) => a - b)
    const jitter = t.slice(1).reduce((s, x, i) => s + Math.abs(x - t[i]), 0) / (t.length - 1)
    return { ping: orden[Math.floor(orden.length / 2)], jitter }
}

async function medirBajada(seg, onProg) {
    const ctl = new AbortController(), m = crearMedidor(seg, onProg)
    const parar = setTimeout(() => ctl.abort(), seg * 1000)
    const worker = async () => {
        let fallos = 0
        while (!ctl.signal.aborted && fallos < 4) {
            try {
                const r = await fetch(`${CF}/__down?bytes=100000000&r=${Math.random()}`, { signal: ctl.signal, cache: 'no-store' })
                const rd = r.body.getReader()
                for (;;) { const { done, value } = await rd.read(); if (done) break; m.add(value.length) }
            } catch (e) { if (ctl.signal.aborted) break; fallos++; await sleep(300) }
        }
    }
    await Promise.all(Array.from({ length: 4 }, worker))
    clearTimeout(parar)
    const r = m.parar()
    if (!r.bytes) throw new Error('No se pudo medir la descarga')
    return r.mbps
}

async function medirSubida(seg, onProg) {
    const buf = new Uint8Array(4 * 1024 * 1024)
    for (let i = 0; i < buf.length; i += 65536) crypto.getRandomValues(buf.subarray(i, i + 65536))
    const m = crearMedidor(seg, onProg), xhrs = new Set()
    let fin = false
    const parar = setTimeout(() => { fin = true; xhrs.forEach((x) => x.abort()) }, seg * 1000)
    const worker = async () => {
        let fallos = 0
        while (!fin && fallos < 4) {
            await new Promise((res) => {
                const x = new XMLHttpRequest(); let previo = 0
                xhrs.add(x)
                x.open('POST', `${CF}/__up?r=${Math.random()}`)
                x.upload.onprogress = (ev) => { m.add(ev.loaded - previo); previo = ev.loaded }
                x.onload = () => { xhrs.delete(x); res() }
                x.onerror = () => { xhrs.delete(x); fallos++; res() }
                x.onabort = () => { xhrs.delete(x); res() }
                x.send(buf)
            })
        }
    }
    await Promise.all(Array.from({ length: 3 }, worker))
    clearTimeout(parar)
    const r = m.parar()
    if (!r.bytes) throw new Error('No se pudo medir la subida')
    return r.mbps
}

export function pruebaVelocidad(ultima = null, mejor = {}) {
    const pref = leerPref()

    return {
        enCurso: false,
        duracion: 8,
        estado: 'Listo para medir',
        error: false,
        progreso: 0,
        proveedor: ultima ? { isp: ultima.isp, asn: ultima.asn, ip: ultima.ip_publica, ciudad: ultima.ciudad, pais: ultima.pais, delUltimo: true } : null,
        res: {
            bajada: ultima?.bajada_mbps ?? null,
            subida: ultima?.subida_mbps ?? null,
            ping: ultima?.ping_ms ?? null,
            jitter: ultima?.jitter_ms ?? null,
        },
        mejor,
        planB: +pref.planB || '',
        planS: +pref.planS || '',

        init() {
            detectarISP().then((p) => { if (p) this.proveedor = p })
        },

        fmt(v) { return v == null || isNaN(v) ? '—' : (v >= 100 ? (+v).toFixed(0) : (+v).toFixed(1)) },

        detalleProveedor() {
            const p = this.proveedor
            if (!p) return 'No se pudo identificar (sin acceso a speed.cloudflare.com)'
            return [p.asn ? 'AS' + String(p.asn).replace(/\D/g, '') : '', p.ip, [p.ciudad, p.pais].filter(Boolean).join(', '), p.delUltimo ? '(del último test)' : '']
                .filter(Boolean).join(' · ')
        },

        guardarPlan() { guardarPref({ planB: +this.planB || 0, planS: +this.planS || 0 }) },

        referencia(tipo) {
            const plan = tipo === 'bajada' ? +this.planB : +this.planS
            if (plan > 0) return { v: plan, txt: `del plan contratado (${this.fmt(plan)} Mbps)` }
            const m = +this.mejor[tipo] || 0
            return m ? { v: m, txt: `del mejor registro (${this.fmt(m)} Mbps)` } : { v: 0, txt: 'Indica tu plan para ver el %' }
        },
        porcentaje(tipo) {
            const v = this.res[tipo], ref = this.referencia(tipo)
            if (v == null) return null
            return ref.v > 0 ? (v / ref.v) * 100 : 100
        },
        offset(tipo) {
            const p = this.porcentaje(tipo)
            return p == null ? ANILLO : ANILLO * (1 - Math.min(p, 100) / 100)
        },

        async iniciar() {
            if (this.enCurso) return
            this.enCurso = true
            this.error = false
            this.res = { bajada: null, subida: null, ping: null, jitter: null }
            const seg = +this.duracion
            try {
                this.estado = 'Identificando proveedor…'; this.progreso = 2
                const info = await detectarISP()
                if (info) this.proveedor = info

                this.estado = 'Midiendo latencia…'; this.progreso = 6
                const { ping, jitter } = await medirPing()
                this.res.ping = ping; this.res.jitter = jitter

                this.estado = 'Midiendo descarga…'; this.progreso = 12
                this.res.bajada = await medirBajada(seg, (v, f) => { this.res.bajada = v; this.progreso = 12 + f * 48 })

                this.estado = 'Midiendo subida…'
                this.res.subida = await medirSubida(Math.max(4, Math.round(seg * 0.75)), (v, f) => { this.res.subida = v; this.progreso = 60 + f * 38 })
                this.progreso = 100

                this.estado = 'Guardando…'
                const r = await this.$wire.guardar({ ...this.res, ...(info || {}) })
                this.mejor = { bajada: Math.max(+this.mejor.bajada || 0, this.res.bajada), subida: Math.max(+this.mejor.subida || 0, this.res.subida) }
                this.estado = r && r.ok ? 'Listo · ' + new Date().toLocaleTimeString() : 'Medición completa (no se guardó)'
            } catch (e) {
                this.error = true
                this.estado = 'Error: ' + (e.message || 'no se pudo completar el test')
            }
            this.enCurso = false
            setTimeout(() => { if (!this.enCurso) this.progreso = 0 }, 1500)
        },
    }
}
