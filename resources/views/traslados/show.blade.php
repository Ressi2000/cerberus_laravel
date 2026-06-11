<x-app-layout title="Traslado {{ $traslado->numero }}" header="Detalle del Traslado">

    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard',  'url' => route('dashboard')],
        ['label' => 'Traslados',  'url' => route('admin.traslados.index')],
        ['label' => $traslado->numero, 'url' => '#'],
    ]" />

    <livewire:traslados.revertir-traslado-modal />

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('trasladoRevertido', () => {
                window.location.reload();
            });
        });
    </script>

    <div class="space-y-5">

        {{-- Header con número y botones --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Traslado
                        <span class="text-cerberus-primary dark:text-cerberus-accent">{{ $traslado->numero }}</span>
                    </h1>
                    @if ($traslado->estado === 'revertido')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                     bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                            <span class="material-icons text-xs">undo</span>
                            Revertido
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                     bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                            <span class="material-icons text-xs">check_circle</span>
                            Activo
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-cerberus-steel mt-0.5">
                    Registrado el {{ $traslado->created_at->format('d/m/Y H:i') }}
                    por {{ $traslado->realizadoPor?->name ?? '—' }}
                </p>
                @if ($traslado->estado === 'revertido')
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        Revertido el {{ $traslado->revertido_at?->format('d/m/Y H:i') ?? '—' }}
                        por {{ $traslado->revertidoPor?->name ?? '—' }}
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @can('revertir', $traslado)
                    <button
                        onclick="Livewire.dispatch('revertirTraslado', { id: {{ $traslado->id }} })"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg
                               bg-amber-500 hover:bg-amber-600
                               text-white text-sm font-medium transition-colors">
                        <span class="material-icons text-base">undo</span>
                        Regresar traslado
                    </button>
                @endcan
                <a href="{{ route('admin.traslados.planilla', $traslado) }}"
                    target="_blank"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-lg
                           bg-red-600 hover:bg-red-700
                           text-white text-sm font-medium transition-colors">
                    <span class="material-icons text-base">picture_as_pdf</span>
                    Descargar planilla
                </a>
            </div>
        </div>

        {{-- Datos principales --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Origen / Destino --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-cerberus-primary dark:text-cerberus-accent mb-4">
                    Ruta del traslado
                </h2>
                <div class="flex items-center gap-4">
                    <div class="flex-1 text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700/30 flex items-center justify-center mx-auto mb-2">
                            <span class="material-icons text-slate-600 dark:text-slate-300">location_on</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-cerberus-steel uppercase tracking-wide">Origen</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                            {{ $traslado->ubicacionOrigen?->nombre ?? '—' }}
                        </p>
                    </div>
                    <div class="flex flex-col items-center gap-1">
                        <span class="material-icons text-2xl text-cerberus-primary dark:text-cerberus-accent">east</span>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto mb-2">
                            <span class="material-icons text-blue-600 dark:text-blue-400">location_on</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-cerberus-steel uppercase tracking-wide">Destino</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                            {{ $traslado->ubicacionDestino?->nombre ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-cerberus-steel/30">
                    <p class="text-xs text-gray-500 dark:text-cerberus-steel">Fecha del traslado</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $traslado->fecha_traslado->format('d/m/Y') }}
                    </p>
                </div>
            </div>

            {{-- Responsables --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-cerberus-primary dark:text-cerberus-accent mb-4">
                    Responsables
                </h2>
                <div class="space-y-3">
                    @foreach ([
                        ['label' => 'Recibe', 'icon' => 'person', 'user' => $traslado->recibe],
                        ['label' => 'Autoriza', 'icon' => 'verified_user', 'user' => $traslado->autoriza],
                        ['label' => 'Realizado por', 'icon' => 'manage_accounts', 'user' => $traslado->realizadoPor],
                    ] as $resp)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-cerberus-dark flex items-center justify-center flex-shrink-0">
                                <span class="material-icons text-gray-500 dark:text-cerberus-steel text-sm">{{ $resp['icon'] }}</span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-cerberus-steel">{{ $resp['label'] }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $resp['user']?->name ?? '—' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Motivo y observaciones --}}
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-cerberus-primary dark:text-cerberus-accent mb-3">
                Motivo
            </h2>
            <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $traslado->motivo }}</p>

            @if ($traslado->observaciones)
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-cerberus-steel/30">
                    <p class="text-xs text-gray-500 dark:text-cerberus-steel mb-1">Observaciones</p>
                    <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $traslado->observaciones }}</p>
                </div>
            @endif

            @if ($traslado->estado === 'revertido' && $traslado->motivo_reversion)
                <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-800/40">
                    <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                        <span class="material-icons text-xs">undo</span>
                        Motivo de la reversión
                    </p>
                    <p class="text-sm text-gray-700 dark:text-cerberus-light">{{ $traslado->motivo_reversion }}</p>
                </div>
            @endif
        </div>

        {{-- Equipos trasladados --}}
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-cerberus-steel flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="material-icons text-cerberus-accent text-base">devices</span>
                    Equipos trasladados
                </h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                             bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                             text-cerberus-primary dark:text-cerberus-accent">
                    {{ $traslado->items->count() }} equipo(s)
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/20">
                @forelse ($traslado->items->load(['equipo.categoria', 'equipo.estado', 'equipo.ubicacion']) as $item)
                    <div class="px-5 py-3 flex items-center gap-4">
                        <div class="w-9 h-9 rounded-lg bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                                    flex items-center justify-center flex-shrink-0">
                            <span class="material-icons text-cerberus-primary dark:text-cerberus-accent text-base">devices</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $item->equipo?->codigo_interno ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-cerberus-steel">
                                {{ $item->equipo?->categoria?->nombre ?? '—' }}
                                @if ($item->equipo?->serial)
                                    · S/N: {{ $item->equipo->serial }}
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded-full
                                         {{ $item->equipo?->estado?->nombre === 'Disponible'
                                             ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                                             : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' }}">
                                {{ $item->equipo?->estado?->nombre ?? '—' }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                                {{ $item->equipo?->ubicacion?->nombre ?? '—' }}
                            </span>
                            @if ($item->equipo)
                                @can('update', $item->equipo)
                                    <a href="{{ route('admin.equipos.edit', $item->equipo) }}"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg
                                               text-gray-400 hover:text-cerberus-primary
                                               hover:bg-gray-100 dark:hover:bg-cerberus-dark transition">
                                        <span class="material-icons text-sm">open_in_new</span>
                                    </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-500 dark:text-cerberus-steel">
                        Sin equipos registrados.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</x-app-layout>
