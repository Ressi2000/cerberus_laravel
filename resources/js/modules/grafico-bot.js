// resources/js/modules/grafico-bot.js
//
// Gráficos de Cerberus Bot dentro del chat. Los datos (etiquetas y valores)
// los calcula Cerberus en PHP; aquí solo se dibujan con Chart.js.
import Chart from 'chart.js/auto'

const PALETA = ['#0EA5E9', '#1E40AF', '#22C55E', '#F59E0B', '#EF4444', '#A855F7', '#14B8A6', '#F97316', '#64748B', '#EC4899']

export function graficoBot(config) {
    return {
        grafico: null,
        init() {
            const oscuro = document.documentElement.classList.contains('dark')
            const texto = oscuro ? '#CBD5E1' : '#334155'
            const rejilla = oscuro ? 'rgba(148,163,184,.15)' : 'rgba(100,116,139,.15)'
            const tipo = { dona: 'doughnut', lineas: 'line' }[config.tipo] ?? 'bar'
            const circular = tipo === 'doughnut'
            // Barras horizontales cuando hay muchas categorías o nombres largos.
            const horizontal = tipo === 'bar' && (config.labels.length > 6 || config.labels.some(l => String(l).length > 14))

            this.grafico = new Chart(this.$refs.lienzo, {
                type: tipo,
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: config.serie ?? 'Cantidad',
                        data: config.values,
                        backgroundColor: circular ? PALETA : (tipo === 'line' ? 'rgba(14,165,233,.2)' : PALETA[0]),
                        borderColor: circular ? (oscuro ? '#1B263B' : '#ffffff') : PALETA[0],
                        borderWidth: circular ? 2 : (tipo === 'line' ? 2 : 0),
                        borderRadius: tipo === 'bar' ? 4 : 0,
                        fill: tipo === 'line',
                        tension: .3,
                    }],
                },
                options: {
                    indexAxis: horizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: circular, position: 'bottom', labels: { color: texto, boxWidth: 10, font: { size: 10 } } },
                    },
                    scales: circular ? {} : {
                        x: { ticks: { color: texto, font: { size: 10 }, precision: 0 }, grid: { color: rejilla } },
                        y: { ticks: { color: texto, font: { size: 10 }, precision: 0 }, grid: { color: rejilla }, beginAtZero: true },
                    },
                },
            })
        },
        destroy() {
            this.grafico?.destroy()
        },
    }
}
