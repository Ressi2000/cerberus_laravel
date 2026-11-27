// resources/js/modules/grafico-bot.js
//
// Gráficos de Cerberus Bot dentro del chat. Los datos (etiquetas y valores)
// los calcula Cerberus en PHP; aquí se dibujan con Chart.js, se permite
// ampliarlos en un modal y descargarlos como PNG de alta nitidez (2x HD).
import Chart from 'chart.js/auto'

const PALETA = ['#0EA5E9', '#1E40AF', '#22C55E', '#F59E0B', '#EF4444', '#A855F7', '#14B8A6', '#F97316', '#64748B', '#EC4899']

/** Plugin que pinta un fondo sólido (evita PNG transparente al exportar). */
const pluginFondoSolido = {
    id: 'cerberusFondoSolido',
    beforeDraw(chart, _args, opciones) {
        if (!opciones?.color) return
        const { ctx, width, height } = chart
        ctx.save()
        ctx.fillStyle = opciones.color
        ctx.fillRect(0, 0, width, height)
        ctx.restore()
    },
}

/** Plugin que dibuja los valores numéricos sobre barras y puntos de línea. */
const pluginValores = {
    id: 'cerberusValores',
    afterDatasetsDraw(chart, _args, opciones) {
        if (!opciones?.activo) return
        const tipo = chart.config.type
        if (tipo !== 'bar' && tipo !== 'line') return

        const { ctx } = chart
        const horizontal = chart.options.indexAxis === 'y'
        const meta = chart.getDatasetMeta(0)
        const valores = chart.data.datasets[0]?.data ?? []
        if (!meta?.data) return

        ctx.save()
        ctx.fillStyle = opciones.color ?? '#0F172A'
        ctx.font = `600 ${opciones.fontSize ?? 14}px system-ui, -apple-system, "Segoe UI", sans-serif`

        meta.data.forEach((punto, idx) => {
            const val = valores[idx]
            if (val === null || val === undefined) return
            const texto = Number(val).toLocaleString('es-VE')

            if (horizontal) {
                ctx.textAlign = 'left'
                ctx.textBaseline = 'middle'
                ctx.fillText(texto, punto.x + 8, punto.y)
            } else {
                ctx.textAlign = 'center'
                ctx.textBaseline = 'bottom'
                ctx.fillText(texto, punto.x, punto.y - 6)
            }
        })
        ctx.restore()
    },
}

function nombreArchivo(titulo) {
    const base = String(titulo || 'grafico-cerberus')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 60) || 'grafico-cerberus'
    const hoy = new Date().toISOString().slice(0, 10)
    return `${base}-${hoy}.png`
}

function construirConfiguracion(config, { oscuro = false, modo = 'compacto' } = {}) {
    const exportando = modo === 'export'
    const amplio = modo === 'grande' || exportando
    const texto = exportando ? '#0F172A' : (oscuro ? '#CBD5E1' : '#334155')
    const subtexto = exportando ? '#475569' : (oscuro ? '#94A3B8' : '#64748B')
    const rejilla = exportando ? 'rgba(15,23,42,.10)' : (oscuro ? 'rgba(148,163,184,.16)' : 'rgba(100,116,139,.15)')
    const tipo = { dona: 'doughnut', lineas: 'line' }[config.tipo] ?? 'bar'
    const circular = tipo === 'doughnut'
    const labels = Array.isArray(config.labels) ? config.labels : []
    const values = Array.isArray(config.values) ? config.values : []
    const horizontal = tipo === 'bar' && (labels.length > 6 || labels.some(l => String(l).length > 14))
    const total = values.reduce((acc, n) => acc + Number(n || 0), 0)

    const etiquetasMostradas = circular && amplio
        ? labels.map((l, i) => {
            const v = Number(values[i] || 0)
            const pct = total > 0 ? Math.round((v / total) * 100) : 0
            return `${l} (${v.toLocaleString('es-VE')} · ${pct}%)`
        })
        : labels

    const fontTicks = exportando ? 15 : (amplio ? 12 : 10)
    const fontLegend = exportando ? 15 : (amplio ? 12 : 10)
    const fecha = new Date().toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' })

    return {
        type: tipo,
        data: {
            labels: etiquetasMostradas,
            datasets: [{
                label: config.serie ?? 'Cantidad',
                data: values,
                backgroundColor: circular ? PALETA : (tipo === 'line' ? 'rgba(14,165,233,.20)' : PALETA[0]),
                borderColor: circular ? (exportando ? '#FFFFFF' : (oscuro ? '#1B263B' : '#FFFFFF')) : PALETA[0],
                borderWidth: circular ? (exportando ? 3 : 2) : (tipo === 'line' ? (exportando ? 3 : 2) : 0),
                borderRadius: tipo === 'bar' ? (exportando ? 6 : 4) : 0,
                pointRadius: tipo === 'line' ? (exportando ? 5 : 3) : undefined,
                fill: tipo === 'line',
                tension: .3,
            }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: !exportando,
            maintainAspectRatio: false,
            animation: exportando ? false : undefined,
            devicePixelRatio: exportando ? 2 : Math.max(window.devicePixelRatio || 1, 2),
            layout: exportando
                ? { padding: { top: 28, right: horizontal ? 64 : 36, bottom: 28, left: 28 } }
                : { padding: { top: amplio ? 16 : 6, right: horizontal && amplio ? 36 : 8, bottom: 4, left: 4 } },
            plugins: {
                title: exportando ? {
                    display: true,
                    text: config.titulo ?? 'Gráfico',
                    color: '#0F172A',
                    font: { size: 22, weight: 'bold', family: 'system-ui, -apple-system, "Segoe UI", sans-serif' },
                    padding: { top: 0, bottom: 6 },
                    align: 'start',
                } : { display: false },
                subtitle: exportando ? {
                    display: true,
                    text: `Cerberus · Generado el ${fecha} · Total: ${total.toLocaleString('es-VE')}`,
                    color: subtexto,
                    font: { size: 13, family: 'system-ui, -apple-system, "Segoe UI", sans-serif' },
                    padding: { top: 0, bottom: 22 },
                    align: 'start',
                } : { display: false },
                legend: {
                    display: circular,
                    position: circular && exportando ? 'right' : 'bottom',
                    labels: {
                        color: texto,
                        boxWidth: exportando ? 16 : 10,
                        padding: exportando ? 16 : 10,
                        font: { size: fontLegend },
                    },
                },
                cerberusFondoSolido: exportando ? { color: '#FFFFFF' } : { color: null },
                cerberusValores: {
                    activo: amplio && !circular,
                    color: texto,
                    fontSize: exportando ? 14 : 11,
                },
            },
            scales: circular ? {} : {
                x: {
                    ticks: { color: texto, font: { size: fontTicks }, precision: 0 },
                    grid: { color: rejilla },
                    beginAtZero: horizontal,
                    grace: horizontal && amplio ? '12%' : undefined,
                },
                y: {
                    ticks: { color: texto, font: { size: fontTicks }, precision: 0 },
                    grid: { color: rejilla },
                    beginAtZero: !horizontal,
                    grace: !horizontal && amplio ? '14%' : undefined,
                },
            },
        },
        plugins: [pluginFondoSolido, pluginValores],
    }
}

export function graficoBot(config, grande = false) {
    return {
        grafico: null,
        graficoAmpliado: null,
        ampliado: false,

        init() {
            const oscuro = document.documentElement.classList.contains('dark')
            const modo = grande ? 'grande' : 'compacto'
            this.grafico = new Chart(this.$refs.lienzo, construirConfiguracion(config, { oscuro, modo }))
        },

        abrirAmpliado() {
            this.ampliado = true
            this.$nextTick(() => {
                if (!this.$refs.lienzoAmpliado) return
                this.graficoAmpliado?.destroy()
                const oscuro = document.documentElement.classList.contains('dark')
                this.graficoAmpliado = new Chart(
                    this.$refs.lienzoAmpliado,
                    construirConfiguracion(config, { oscuro, modo: 'grande' })
                )
            })
        },

        cerrarAmpliado() {
            this.ampliado = false
            this.graficoAmpliado?.destroy()
            this.graficoAmpliado = null
        },

        descargar() {
            const labels = Array.isArray(config.labels) ? config.labels : []
            const tipo = { dona: 'doughnut', lineas: 'line' }[config.tipo] ?? 'bar'
            const horizontal = tipo === 'bar' && (labels.length > 6 || labels.some(l => String(l).length > 14))

            const ancho = 1400
            const alto = horizontal
                ? Math.max(780, labels.length * 52 + 220)
                : (tipo === 'doughnut' ? 820 : 800)

            const canvas = document.createElement('canvas')
            canvas.width = ancho
            canvas.height = alto
            canvas.style.width = `${ancho}px`
            canvas.style.height = `${alto}px`

            const chartExport = new Chart(
                canvas.getContext('2d'),
                construirConfiguracion(config, { oscuro: false, modo: 'export' })
            )

            try {
                const url = canvas.toDataURL('image/png', 1.0)
                const enlace = document.createElement('a')
                enlace.href = url
                enlace.download = nombreArchivo(config.titulo)
                document.body.appendChild(enlace)
                enlace.click()
                enlace.remove()
            } finally {
                chartExport.destroy()
            }
        },

        destroy() {
            this.grafico?.destroy()
            this.graficoAmpliado?.destroy()
        },
    }
}
