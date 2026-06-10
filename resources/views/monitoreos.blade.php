<x-app-layout title="Monitoreos Web" header="Monitoreos Web">
    <x-slot name="title">Monitoreos Web</x-slot>

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="material-icons text-emerald-400">monitor_heart</span>
                Monitoreos Web
            </h1>
            <p class="text-cerberus-accent text-xs mt-1">
                Datos en tiempo real · actualización cada 5 min
                <span class="ml-2 inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-emerald-400">Live</span>
                </span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Selector de rango de tiempo --}}
            <div x-data="{ rango: '7d' }" class="flex items-center gap-1">
                @foreach(['1d' => '1d', '7d' => '7d', '30d' => '30d'] as $val => $label)
                    <button x-on:click="rango = '{{ $val }}'; $refs.iframe.src = buildUrl('{{ $val }}')"
                            :class="rango === '{{ $val }}'
                                ? 'bg-cerberus-light/20 text-cerberus-light border-cerberus-light/40'
                                : 'text-cerberus-accent border-cerberus-steel hover:border-cerberus-light/40 hover:text-white'"
                            class="px-2.5 py-1 text-xs border rounded-lg transition-colors bg-cerberus-mid">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Abrir en Grafana --}}
            <a href="https://grafana-monitoreos.gesindoni.com/d/monitoreos-web/monitoreos-web-gesindoni?orgId=1"
               target="_blank"
               class="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-cerberus-mid border border-cerberus-steel
                      rounded-lg text-cerberus-accent hover:text-cerberus-light hover:border-cerberus-light/40 transition-colors">
                <span class="material-icons text-sm">open_in_new</span>
                Ver en Grafana
            </a>
        </div>
    </div>

    {{-- ── iframe Grafana ──────────────────────────────────────────────────── --}}
    <div class="rounded-xl overflow-hidden border border-cerberus-steel shadow-cerberus bg-cerberus-mid"
         style="height: calc(100vh - 190px); min-height: 500px;">

        <iframe
            x-ref="iframe"
            id="grafana-frame"
            src="https://grafana-monitoreos.gesindoni.com/d/monitoreos-web/monitoreos-web-gesindoni?orgId=1&theme=dark&kiosk&from=now-7d&to=now&refresh=5m"
            class="w-full h-full border-0"
            allowfullscreen
            loading="lazy"
            title="Monitoreos Web — GesIndoni">
        </iframe>
    </div>

    <script>
        function buildUrl(rango) {
            return 'https://grafana-monitoreos.gesindoni.com/d/monitoreos-web/monitoreos-web-gesindoni'
                + '?orgId=1&theme=dark&kiosk&refresh=5m'
                + '&from=now-' + rango + '&to=now';
        }
    </script>
</x-app-layout>
