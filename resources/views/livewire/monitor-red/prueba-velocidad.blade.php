<div class="space-y-6">
    @php
        $panel = 'bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/60 shadow-sm dark:shadow-none rounded-xl';
        $fmt   = fn ($v) => $v === null ? '—' : number_format($v, $v >= 100 ? 0 : 1);
        $gwEj  = $this->enlaces->whereNotNull('gateway')->first()?->gateway ?? '192.168.1.1';
        $sel   = 'text-sm rounded-lg border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white';
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-[#1E293B] dark:text-white">Test de velocidad de internet</h1>
            <p class="text-sm text-gray-500 dark:text-cerberus-accent mt-0.5">
                Mide la conexión del equipo desde el que abres esta página (no la del servidor), contra speed.cloudflare.com.
            </p>
        </div>
        @if ($this->empresasOpciones->isNotEmpty())
            <select wire:model.live="empresa_id" class="{{ $sel }}">
                @foreach ($this->empresasOpciones as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ── Medidor (Alpine, fuera del ciclo de render de Livewire) ─────────── --}}
    <div wire:ignore wire:key="medidor-{{ $empresa_id }}"
         x-data="pruebaVelocidad(@js($ultima), @js($mejor), @js($enlacesJs))" class="{{ $panel }} p-5">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-center">
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500 dark:text-cerberus-light">Proveedor en uso</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5" x-text="proveedor?.isp || 'Identificando…'"></p>
                    <p class="text-xs text-gray-500 dark:text-cerberus-light mt-1" x-text="detalleProveedor()"></p>
                    <button type="button" @click="redetectar()" :disabled="enCurso || detectando"
                            class="mt-1 inline-flex items-center gap-1 text-xs text-[#1E40AF] dark:text-cerberus-accent hover:underline disabled:opacity-50">
                        <span class="material-icons text-sm" :class="detectando && 'animate-spin'">refresh</span> Volver a detectar
                    </button>
                </div>

                <template x-if="enlaces.length">
                    <div class="space-y-2">
                        <p class="text-xs" x-show="proveedor && !proveedor.delUltimo">
                            <template x-if="enlaceDetectado()">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30">
                                    <span class="material-icons text-xs">router</span>
                                    Saliendo por: <b x-text="enlaceDetectado().nombre"></b>
                                </span>
                            </template>
                            <template x-if="!enlaceDetectado()">
                                <span class="text-amber-600 dark:text-amber-400">No se reconoce el enlace (registra su IP pública o ASN, o elígelo abajo).</span>
                            </template>
                        </p>

                        <label class="block text-xs text-gray-500 dark:text-cerberus-light">Atribuir el test a</label>
                        <select x-model="enlaceSel" :disabled="enCurso" class="w-full {{ $sel }}">
                            <option value="">Detectar automáticamente</option>
                            <template x-for="e in enlaces" :key="e.id">
                                <option :value="e.id" x-text="`${e.nombre} (${e.proveedor})`"></option>
                            </template>
                        </select>
                        <p x-show="desajuste()" class="text-xs text-red-600 dark:text-red-400">
                            La conexión NO está saliendo por <b x-text="enlaceElegido()?.nombre"></b>.
                            <span x-show="enlaceElegido()?.gateway">Cambia la puerta de enlace de tu equipo a <b class="font-mono" x-text="enlaceElegido()?.gateway"></b> (ver la guía abajo).</span>
                        </p>
                    </div>
                </template>
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
            <select x-model="duracion" :disabled="enCurso" class="{{ $sel }}">
                <option value="5">Rápido (5 s)</option>
                <option value="8">Normal (8 s)</option>
                <option value="15">Preciso (15 s)</option>
            </select>
            <template x-if="!enlaceActual()">
                <span class="flex gap-3">
                    <input type="number" min="0" step="any" x-model="planB" @change="guardarPlan()" placeholder="Plan bajada (Mbps)"
                           title="Velocidad de bajada contratada, para calcular el porcentaje" class="w-40 {{ $sel }}">
                    <input type="number" min="0" step="any" x-model="planS" @change="guardarPlan()" placeholder="Plan subida (Mbps)"
                           title="Velocidad de subida contratada, para calcular el porcentaje" class="w-40 {{ $sel }}">
                </span>
            </template>
            <span class="text-sm" :class="error ? 'text-red-500' : 'text-gray-500 dark:text-cerberus-light'" x-text="estado"></span>
        </div>
    </div>

    {{-- ── Resumen por enlace ─────────────────────────────────────────────── --}}
    @if ($this->enlaces->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            @foreach ($this->enlaces as $e)
                @php
                    $u  = $e->ultimaPrueba;
                    $pB = $u ? $e->porcentajeDelPlan($u->bajada_mbps, 'bajada') : null;
                @endphp
                <button type="button" wire:key="res-enl-{{ $e->id }}"
                        wire:click="$set('enlace_filtro', '{{ $enlace_filtro === (string) $e->id ? '' : $e->id }}')"
                        title="Filtrar el historial por este enlace"
                        @class([$panel, 'p-4 text-left hover:shadow-md transition', 'ring-2 ring-[#1E40AF] dark:ring-cerberus-accent' => $enlace_filtro === (string) $e->id])>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $e->nombre }}</p>
                    <p class="text-xs text-gray-500 dark:text-cerberus-light truncate">{{ $e->proveedor }}@if ($e->gateway) · GW <span class="font-mono">{{ $e->gateway }}</span>@endif</p>
                    @if ($u)
                        <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                            ↓ {{ $fmt($u->bajada_mbps) }} <small class="text-xs font-normal">/ ↑ {{ $fmt($u->subida_mbps) }} Mbps</small>
                        </p>
                        <p class="text-xs">
                            @if ($pB !== null)
                                <span @class(['font-semibold', 'text-red-500' => $pB < 50, 'text-amber-500' => $pB >= 50 && $pB < 80, 'text-green-600 dark:text-green-400' => $pB >= 80])>{{ $pB }} % del plan</span> ·
                            @endif
                            <span class="text-gray-400 dark:text-cerberus-steel">{{ $u->created_at->format('d/m/Y H:i') }}</span>
                        </p>
                    @else
                        <p class="mt-2 text-xs text-gray-400 dark:text-cerberus-steel">Sin tests todavía.</p>
                    @endif
                </button>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
            Esta localidad no tiene enlaces registrados. <a href="{{ route('admin.monitor-red.enlaces') }}" class="underline font-medium">Registra sus proveedores</a>
            para que cada test quede asociado al enlace por el que salió.
        </div>
    @endif

    {{-- ── Guía: medir cada proveedor estando en la localidad ─────────────── --}}
    <details class="{{ $panel }} p-4 group">
        <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2 list-none">
            <span class="material-icons text-base text-cerberus-accent">help_outline</span>
            ¿Cómo medir cada proveedor estando en la localidad?
            <span class="material-icons text-base ml-auto transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <div class="mt-3 text-sm text-gray-600 dark:text-cerberus-light space-y-3">
            <p>
                El navegador sale por la ruta que decide la red. Para probar un proveedor concreto, tu equipo tiene que salir por
                <b>la puerta de enlace de ese proveedor</b>. Funciona cuando cada proveedor tiene su propio router/módem en la LAN con una IP distinta
                @if ($this->enlaces->whereNotNull('gateway')->isNotEmpty())
                    (en esta localidad: {!! $this->enlaces->whereNotNull('gateway')->map(fn ($e) => '<b>' . e($e->nombre) . '</b> → <span class="font-mono">' . e($e->gateway) . '</span>')->implode(', ') !!}).
                @else
                    (registra la puerta de enlace de cada enlace para verla aquí).
                @endif
            </p>
            <ol class="list-decimal pl-5 space-y-1">
                <li>Cambia temporalmente la puerta de enlace de tu equipo a la del proveedor que vas a medir (comandos abajo).</li>
                <li>Pulsa <b>«Volver a detectar»</b>: arriba debe decir <b>«Saliendo por: …»</b> con ese enlace. Si no, el cambio no tomó efecto.</li>
                <li>Inicia el test. Se guarda en ese enlace y se compara con su plan contratado.</li>
                <li>Repite con el siguiente proveedor y al terminar <b>vuelve tu equipo a automático (DHCP)</b>.</li>
            </ol>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-cerberus-accent mb-1">Windows (Símbolo del sistema como administrador)</p>
                    <pre class="text-xs font-mono bg-gray-50 dark:bg-cerberus-dark rounded-lg p-3 overflow-x-auto">route change 0.0.0.0 mask 0.0.0.0 {{ $gwEj }}
REM al terminar, volver a lo normal:
ipconfig /renew</pre>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-cerberus-accent mb-1">Linux / macOS</p>
                    <pre class="text-xs font-mono bg-gray-50 dark:bg-cerberus-dark rounded-lg p-3 overflow-x-auto">sudo ip route replace default via {{ $gwEj }}   # Linux
sudo route change default {{ $gwEj }}           # macOS
# al terminar: reconectar la red (DHCP)</pre>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-cerberus-steel">
                Si la localidad tiene <b>un solo equipo multi-WAN</b> (MikroTik, FortiGate, pfSense…) que reparte el tráfico, cambiar la puerta de enlace no basta:
                el área de redes tiene que crear una regla de ruteo por política (por ejemplo, "esta IP de mi equipo sale siempre por el proveedor 2").
                Otra opción es conectar la laptop directo a un puerto libre del módem del proveedor. Evita medir seguido los enlaces con cuota de datos.
            </p>
        </div>
    </details>

    {{-- ── Historial ───────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-3">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Historial de tests</h2>
        @if ($this->enlaces->isNotEmpty())
            <select wire:model.live="enlace_filtro" class="{{ $sel }}">
                <option value="">Todos los enlaces</option>
                @foreach ($this->enlaces as $e)
                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                @endforeach
                <option value="sin">Sin enlace asociado</option>
            </select>
        @endif
    </div>

    <x-table.crud-table :headers="['Fecha', 'Enlace', 'Proveedor detectado', 'Bajada', 'Subida', 'Latencia', 'Jitter', 'Medido por']" :paginated="$pruebas">
        @forelse ($pruebas as $p)
            <tr wire:key="vel-{{ $p->id }}" class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition-colors duration-150">
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-cerberus-light whitespace-nowrap">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-sm">
                    @if ($p->enlace)
                        <p class="text-gray-900 dark:text-white">{{ $p->enlace->nombre }}</p>
                        <p class="text-[11px] text-gray-400 dark:text-cerberus-steel">{{ ['ip' => 'detectado por IP', 'asn' => 'detectado por ASN', 'manual' => 'elegido a mano'][$p->enlace_asignado_por] ?? '' }}</p>
                    @else
                        <span class="text-gray-400 dark:text-cerberus-steel">—</span>
                    @endif
                </td>
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
                <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-cerberus-accent">
                    <span class="material-icons text-4xl block mb-2 opacity-40">speed</span>
                    Aún no hay tests guardados. Inicia el primero para ver el historial.
                </td>
            </tr>
        @endforelse
    </x-table.crud-table>
</div>
