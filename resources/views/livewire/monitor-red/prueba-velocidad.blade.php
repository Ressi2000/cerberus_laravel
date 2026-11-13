<div class="space-y-6">
    @php
        $panel = 'bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/60 shadow-sm dark:shadow-none rounded-xl';
        $fmt   = fn ($v) => $v === null ? '—' : number_format($v, $v >= 100 ? 0 : 1);
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-[#1E293B] dark:text-white">Test de velocidad de internet</h1>
            <p class="text-sm text-gray-500 dark:text-cerberus-accent mt-0.5">
                Mide la conexión del equipo desde el que abres esta página (no la del servidor), contra speed.cloudflare.com.
            </p>
        </div>
        @if ($this->empresasOpciones->isNotEmpty())
            <select wire:model.live="empresa_id"
                    class="text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white">
                @foreach ($this->empresasOpciones as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ── Medidor (Alpine, fuera del ciclo de render de Livewire) ─────────── --}}
    <div wire:ignore x-data="pruebaVelocidad(@js($ultima), @js($mejor))" class="{{ $panel }} p-5">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-center">
            <div>
                <p class="text-xs text-gray-500 dark:text-cerberus-light">Proveedor en uso</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5" x-text="proveedor?.isp || 'Identificando…'"></p>
                <p class="text-xs text-gray-500 dark:text-cerberus-light mt-1" x-text="detalleProveedor()"></p>
            </div>

            <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-3">
                <template x-for="t in [['bajada', 'Bajada', 'text-[#1E40AF] dark:text-cerberus-accent'], ['subida', 'Subida', 'text-green-500']]" :key="t[0]">
                    <div class="rounded-xl bg-gray-50 dark:bg-cerberus-dark/50 p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-cerberus-light" x-text="t[1]"></p>
                        <div class="relative w-28 h-28 mx-auto my-1">
                            <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                <circle cx="50" cy="50" r="44" fill="none" stroke-width="9" class="stroke-gray-200 dark:stroke-cerberus-steel/40"></circle>
                                <circle cx="50" cy="50" r="44" fill="none" stroke-width="9" stroke-linecap="round" stroke="currentColor"
                                        :class="t[2]" stroke-dasharray="276.46" :stroke-dashoffset="offset(t[0])"
                                        style="transition: stroke-dashoffset .25s ease"></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <b class="text-xl text-gray-900 dark:text-white" x-text="porcentaje(t[0]) == null ? '—' : Math.round(porcentaje(t[0])) + '%'"></b>
                                <small class="text-[11px] text-gray-500 dark:text-cerberus-light" x-text="res[t[0]] == null ? '' : fmt(res[t[0]]) + ' Mbps'"></small>
                            </div>
                        </div>
                        <small class="block text-[11px] text-gray-400 dark:text-cerberus-steel min-h-[28px]" x-text="referencia(t[0]).txt"></small>
                    </div>
                </template>
                <div class="rounded-xl bg-gray-50 dark:bg-cerberus-dark/50 p-3 text-center flex flex-col justify-center">
                    <p class="text-xs text-gray-500 dark:text-cerberus-light">Latencia</p>
                    <b class="text-3xl text-gray-900 dark:text-white" x-text="fmt(res.ping)"></b>
                    <small class="text-xs text-gray-500 dark:text-cerberus-light">ms</small>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-cerberus-dark/50 p-3 text-center flex flex-col justify-center">
                    <p class="text-xs text-gray-500 dark:text-cerberus-light">Jitter</p>
                    <b class="text-3xl text-gray-900 dark:text-white" x-text="fmt(res.jitter)"></b>
                    <small class="text-xs text-gray-500 dark:text-cerberus-light">ms</small>
                </div>
            </div>
        </div>

        <div class="h-2 rounded-full overflow-hidden bg-gray-100 dark:bg-cerberus-steel/30 mt-5">
            <span class="block h-full bg-[#1E40AF] dark:bg-cerberus-accent transition-all" :style="`width: ${progreso}%`"></span>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-4">
            <button type="button" @click="iniciar()" :disabled="enCurso"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                           bg-[#1E40AF] hover:bg-[#1E3A8A] text-white shadow-sm transition disabled:opacity-60">
                <span class="material-icons text-base" :class="enCurso && 'animate-spin'" x-text="enCurso ? 'autorenew' : 'play_arrow'"></span>
                <span x-text="enCurso ? 'Midiendo…' : 'Iniciar test'"></span>
            </button>
            <select x-model="duracion" :disabled="enCurso"
                    class="text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white">
                <option value="5">Rápido (5 s)</option>
                <option value="8">Normal (8 s)</option>
                <option value="15">Preciso (15 s)</option>
            </select>
            <input type="number" min="0" step="any" x-model="planB" @change="guardarPlan()" placeholder="Plan bajada (Mbps)"
                   title="Velocidad de bajada contratada, para calcular el porcentaje"
                   class="w-40 text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white">
            <input type="number" min="0" step="any" x-model="planS" @change="guardarPlan()" placeholder="Plan subida (Mbps)"
                   title="Velocidad de subida contratada, para calcular el porcentaje"
                   class="w-40 text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white">
            <span class="text-sm" :class="error ? 'text-red-500' : 'text-gray-500 dark:text-cerberus-light'" x-text="estado"></span>
        </div>
    </div>

    {{-- ── Historial ───────────────────────────────────────────────────────── --}}
    <x-table.crud-table :headers="['Fecha', 'Proveedor', 'Bajada', 'Subida', 'Latencia', 'Jitter', 'Medido por']" :paginated="$pruebas">
        @forelse ($pruebas as $p)
            <tr wire:key="vel-{{ $p->id }}" class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light whitespace-nowrap">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-sm">
                    <p class="text-gray-900 dark:text-white">{{ $p->isp ?: '—' }}</p>
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel">{{ collect([$p->asn ? 'AS' . $p->asn : null, $p->ip_publica, $p->ciudad])->filter()->implode(' · ') }}</p>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">{{ $fmt($p->bajada_mbps) }} Mbps</td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">{{ $fmt($p->subida_mbps) }} Mbps</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light whitespace-nowrap">{{ $fmt($p->ping_ms) }} ms</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light whitespace-nowrap">{{ $fmt($p->jitter_ms) }} ms</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light">{{ $p->usuario?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">speed</span>
                    Aún no hay tests guardados. Inicia el primero para ver el historial.
                </td>
            </tr>
        @endforelse
    </x-table.crud-table>
</div>
