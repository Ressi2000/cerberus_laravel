<div>
    @if ($open && $this->pieza)
        @php
            $pieza = $this->pieza;
            $colorEstado = match ($pieza->estado) {
                'en_almacen'  => 'bg-blue-50 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/30',
                'instalada'   => 'bg-green-50 dark:bg-green-500/15 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
                'en_deposito' => 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/30',
                default       => 'bg-gray-100 dark:bg-cerberus-steel/20 text-gray-600 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/40',
            };
            $iconoMovimiento = [
                'extraccion'        => 'call_split',
                'ingreso_almacen'   => 'move_to_inbox',
                'instalacion'       => 'build',
                'desinstalacion'    => 'eject',
                'envio_deposito'    => 'warehouse',
                'traslado_deposito' => 'local_shipping',
                'salida_ajuste'     => 'remove_circle_outline',
                'agrupada'          => 'stacks',
            ];
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative z-50 w-full max-w-xl mx-4 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl
                        flex flex-col" style="max-height: 85vh;">

                {{-- Encabezado: quién es --}}
                <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-cerberus-light">Ficha de la pieza</p>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white font-mono break-all">
                            {{ $pieza->codigo() }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-cerberus-light mt-0.5">
                            {{ $pieza->componenteAlmacen?->nombre ?? $pieza->descripcion() }}
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition flex-shrink-0">
                        <span class="material-icons">close</span>
                    </button>
                </div>

                <div class="px-6 py-4 overflow-y-auto flex-1 space-y-5">

                    {{-- Dónde está hoy --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2 py-0.5 text-xs rounded-full border font-medium {{ $colorEstado }}">
                            {{ $pieza->labelEstado() }}
                        </span>
                        <span class="px-2 py-0.5 text-xs rounded-full border font-medium bg-gray-50 dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-light border-gray-200 dark:border-cerberus-steel/50">
                            {{ $pieza->labelCondicion() }}
                        </span>
                        <span class="text-sm text-gray-700 dark:text-white">
                            @if ($pieza->estado === 'instalada' && $pieza->equipoDestino)
                                Instalada en
                                <a href="{{ route('admin.equipos.show', $pieza->equipoDestino) }}" wire:navigate
                                   class="font-medium text-cerberus-primary dark:text-cerberus-accent hover:underline">{{ $pieza->equipoDestino->codigo_interno }}</a>
                            @elseif ($pieza->estado !== 'en_almacen')
                                {{ $pieza->ubicacion() }}
                            @endif
                        </span>
                    </div>

                    {{-- Datos --}}
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-cerberus-light">Familia</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $pieza->componenteAlmacen?->familia?->nombre ?? 'Sin familia' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-cerberus-light">Origen</dt>
                            <dd class="text-gray-900 dark:text-white">
                                {{ \App\Models\PiezaExtraida::MOTIVOS[$pieza->motivo] ?? $pieza->motivo }}
                                @if ($pieza->equipoOrigen)
                                    · <a href="{{ route('admin.equipos.show', $pieza->equipoOrigen) }}" wire:navigate
                                         class="text-cerberus-primary dark:text-cerberus-accent hover:underline">{{ $pieza->equipoOrigen->codigo_interno }}</a>
                                @endif
                            </dd>
                        </div>
                        @foreach ($this->fichaTecnica as $campo => $valor)
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-cerberus-light">{{ $campo }}</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $valor }}</dd>
                            </div>
                        @endforeach
                        @if (auth()->user()->hasRole('Administrador'))
                            <div>
                                <dt class="text-xs text-gray-500 dark:text-cerberus-light">Empresa</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $pieza->empresa?->nombre ?? '—' }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-cerberus-light">N.º interno</dt>
                            <dd class="text-gray-900 dark:text-white font-mono">#{{ $pieza->id }}</dd>
                        </div>
                    </dl>

                    @if ($pieza->observaciones)
                        <p class="text-sm text-gray-600 dark:text-cerberus-light italic">"{{ $pieza->observaciones }}"</p>
                    @endif

                    {{-- Recorrido --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-cerberus-light mb-3">Recorrido</h3>

                        @if ($this->recorrido->isEmpty())
                            <p class="text-sm text-gray-400 dark:text-cerberus-steel">Esta pieza no tiene movimientos registrados.</p>
                        @else
                            <ol class="relative border-s border-gray-200 dark:border-cerberus-steel/60 ms-3 space-y-4">
                                @foreach ($this->recorrido as $mov)
                                    <li wire:key="rec-{{ $mov->id }}" class="ms-6">
                                        <span class="absolute -start-3 flex items-center justify-center w-6 h-6 rounded-full
                                                     bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel">
                                            <span class="material-icons text-sm text-cerberus-accent">{{ $iconoMovimiento[$mov->tipo] ?? 'circle' }}</span>
                                        </span>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $mov->labelTipo() }}
                                            @if ($mov->equipoRelacionado)
                                                · <a href="{{ route('admin.equipos.show', $mov->equipoRelacionado) }}" wire:navigate
                                                     class="text-cerberus-primary dark:text-cerberus-accent hover:underline">{{ $mov->equipoRelacionado->codigo_interno }}</a>
                                            @endif
                                            @if ($mov->depositoRelacionado)
                                                · {{ $mov->depositoRelacionado->nombre }}
                                            @endif
                                            @if ($mov->mantenimiento)
                                                · <a href="{{ route('admin.mantenimientos.show', $mov->mantenimiento) }}" wire:navigate
                                                     class="text-cerberus-primary dark:text-cerberus-accent hover:underline">Caso #{{ $mov->mantenimiento->id }}</a>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                            {{ $mov->created_at->format('d/m/Y H:i') }} · {{ $mov->registradoPor?->name ?? 'Sistema' }}
                                        </p>
                                        @if ($mov->observaciones)
                                            <p class="text-xs text-gray-400 dark:text-cerberus-steel italic mt-0.5">{{ $mov->observaciones }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>

                <div class="flex justify-between gap-3 px-6 py-4 border-t border-gray-100 dark:border-cerberus-steel flex-shrink-0">
                    @if ($pieza->componente_almacen_id)
                        <button wire:click="verVariante"
                            class="text-sm text-cerberus-primary dark:text-cerberus-accent hover:underline flex items-center gap-1">
                            <span class="material-icons text-base">history</span> Historial de la variante
                        </button>
                    @else
                        <span></span>
                    @endif
                    <button wire:click="close"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
