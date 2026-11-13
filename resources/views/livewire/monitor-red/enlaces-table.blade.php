<div class="space-y-6">

    @livewire('monitor-red.enlace-modal')

    <x-ui.stats-cards :items="[
        ['title' => 'Enlaces registrados',    'value' => $total,  'icon' => 'router'],
        ['title' => 'Con IP pública vigilada', 'value' => $conWan, 'icon' => 'travel_explore'],
        ['title' => 'Enlaces caídos ahora',   'value' => $caidos, 'icon' => 'wifi_off'],
    ]" />

    <x-table.crud-header
        title="Enlaces de internet"
        subtitle="Proveedores de cada localidad: puerta de enlace, IP pública y plan contratado"
        :buttonLabel="auth()->user()->can('create', \App\Models\MonitorEnlace::class) ? 'Nuevo enlace' : null"
        buttonEvent="openEnlaceCrear">

        <x-slot name="filters">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel shadow-sm dark:shadow-cerberus rounded-xl p-4">
                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    @if ($this->empresasOpciones->isNotEmpty())
                        <div class="sm:w-72">
                            <x-form.select label="Empresa / localidad" placeholder="Todas" wire:model.live="empresa_id" :options="$this->empresasOpciones" />
                        </div>
                    @endif
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-cerberus-light select-none mb-4">
                        <input type="checkbox" wire:model.live="mostrar_inactivos" class="rounded border-gray-300 dark:border-cerberus-steel">
                        Mostrar enlaces inactivos
                    </label>
                </div>
            </div>
        </x-slot>
    </x-table.crud-header>

    @forelse ($grupos as $empresa => $enlaces)
        <section wire:key="emp-{{ md5($empresa) }}">
            @if ($this->empresasOpciones->isNotEmpty())
                <h2 class="text-sm font-semibold text-gray-500 dark:text-cerberus-accent uppercase tracking-wide mb-2 flex items-center gap-1.5">
                    <span class="material-icons text-base">apartment</span> {{ $empresa }}
                </h2>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($enlaces as $e)
                    @php
                        $wan = $e->hostWan();
                        $u   = $e->ultimaPrueba;
                        $pB  = $u ? $e->porcentajeDelPlan($u->bajada_mbps, 'bajada') : null;
                        $pS  = $u ? $e->porcentajeDelPlan($u->subida_mbps, 'subida') : null;
                    @endphp
                    <div wire:key="enlace-{{ $e->id }}"
                         @class([
                            'bg-white dark:bg-cerberus-mid rounded-xl p-4 shadow-sm border border-gray-200 dark:border-cerberus-steel/60 border-l-4',
                            'border-l-red-500'   => $wan?->estaCaido(),
                            'border-l-green-500' => $wan?->estaActivo(),
                            'border-l-gray-300 dark:border-l-cerberus-steel' => ! $wan || (! $wan->estaCaido() && ! $wan->estaActivo()),
                            'opacity-60' => ! $e->activo,
                         ])>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                    {{ $e->nombre }}
                                    @if ($e->principal)
                                        <span class="px-1.5 py-0.5 text-[10px] rounded bg-[#1E40AF]/10 dark:bg-cerberus-primary/20 text-[#1E40AF] dark:text-cerberus-accent">Principal</span>
                                    @endif
                                    @if ($e->con_cuota)
                                        <span class="px-1.5 py-0.5 text-[10px] rounded bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400" title="Tiene límite de datos: evita tests de velocidad frecuentes">Con cuota</span>
                                    @endif
                                    @if (! $e->activo)
                                        <span class="px-1.5 py-0.5 text-[10px] rounded bg-gray-100 dark:bg-cerberus-steel/30 text-gray-500 dark:text-cerberus-light">Inactivo</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-cerberus-light">
                                    {{ $e->proveedor }} · {{ $e->tipo_conexion }}@if ($e->asn) · AS{{ $e->asn }}@endif
                                </p>
                            </div>
                            <x-table.table-actions :model="$e" :editEvent="auth()->user()->can('update', $e) ? 'openEnlaceEditar' : null">
                                <x-slot name="acciones">
                                    @can('update', $e)
                                        @if ($e->ip_publica && ! $wan)
                                            <li>
                                                <button wire:click="monitorearWan({{ $e->id }})" @click="close()"
                                                        class="flex items-center gap-3 px-4 py-2.5 w-full text-left text-gray-600 dark:text-cerberus-light hover:bg-gray-50 dark:hover:bg-cerberus-steel/20 transition-colors duration-100">
                                                    <span class="material-icons text-base text-cerberus-accent">travel_explore</span>
                                                    Vigilar IP pública
                                                </button>
                                            </li>
                                        @endif
                                    @endcan
                                    <li>
                                        <a href="{{ route('admin.monitor-red.velocidad') }}" @click="close()"
                                           class="flex items-center gap-3 px-4 py-2.5 w-full text-gray-600 dark:text-cerberus-light hover:bg-gray-50 dark:hover:bg-cerberus-steel/20 transition-colors duration-100">
                                            <span class="material-icons text-base text-cerberus-accent">speed</span>
                                            Medir velocidad
                                        </a>
                                    </li>
                                    @can('delete', $e)
                                        <li><div class="my-1 mx-3 border-t border-gray-100 dark:border-cerberus-steel/30"></div></li>
                                        <li>
                                            <button wire:click="$dispatch('confirmar', {
                                                        titulo: 'Eliminar enlace',
                                                        mensaje: '¿Eliminar el enlace «{{ addslashes($e->nombre) }}»? Los tests de velocidad ya registrados se conservan.',
                                                        accionEvento: 'enlaceEliminarConfirmado',
                                                        accionParams: [{{ $e->id }}],
                                                        confirmLabel: 'Eliminar',
                                                    })"
                                                    @click="close()"
                                                    class="flex items-center gap-3 px-4 py-2.5 w-full text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors duration-100">
                                                <span class="material-icons text-base">delete</span>
                                                Eliminar
                                            </button>
                                        </li>
                                    @endcan
                                </x-slot>
                            </x-table.table-actions>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
                            <dt class="text-gray-400 dark:text-cerberus-steel">Puerta de enlace</dt>
                            <dd class="font-mono text-gray-700 dark:text-white text-right">{{ $e->gateway ?: '—' }}</dd>
                            <dt class="text-gray-400 dark:text-cerberus-steel">IP pública</dt>
                            <dd class="font-mono text-gray-700 dark:text-white text-right">{{ $e->ip_publica ?: 'dinámica / no registrada' }}</dd>
                            <dt class="text-gray-400 dark:text-cerberus-steel">Plan contratado</dt>
                            <dd class="text-gray-700 dark:text-white text-right">
                                @if ($e->bajada_contratada_mbps || $e->subida_contratada_mbps)
                                    ↓ {{ $e->bajada_contratada_mbps + 0 ?: '—' }} / ↑ {{ $e->subida_contratada_mbps + 0 ?: '—' }} Mbps
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="text-gray-400 dark:text-cerberus-steel">Disponibilidad</dt>
                            <dd class="text-right">
                                @if ($wan)
                                    @include('livewire.monitor-red._estado', ['h' => $wan])
                                @else
                                    <span class="text-gray-400 dark:text-cerberus-steel">sin vigilar</span>
                                @endif
                            </dd>
                        </dl>

                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-cerberus-steel/30 text-xs">
                            @if ($u)
                                <p class="text-gray-400 dark:text-cerberus-steel mb-1">Último test · {{ $u->created_at->format('d/m/Y H:i') }}</p>
                                <div class="flex justify-between gap-2">
                                    <span class="text-gray-700 dark:text-white">↓ <b>{{ number_format($u->bajada_mbps, 1) }}</b> Mbps
                                        @if ($pB !== null)<span @class(['ml-1', 'text-red-500' => $pB < 50, 'text-amber-500' => $pB >= 50 && $pB < 80, 'text-green-600 dark:text-green-400' => $pB >= 80])>({{ $pB }} %)</span>@endif
                                    </span>
                                    <span class="text-gray-700 dark:text-white">↑ <b>{{ number_format($u->subida_mbps, 1) }}</b> Mbps
                                        @if ($pS !== null)<span @class(['ml-1', 'text-red-500' => $pS < 50, 'text-amber-500' => $pS >= 50 && $pS < 80, 'text-green-600 dark:text-green-400' => $pS >= 80])>({{ $pS }} %)</span>@endif
                                    </span>
                                </div>
                            @else
                                <p class="text-gray-400 dark:text-cerberus-steel">Sin tests de velocidad todavía.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/60 rounded-xl py-12 text-center text-gray-400 dark:text-cerberus-accent">
            <span class="material-icons text-4xl block mb-2 opacity-40">router</span>
            Aún no hay enlaces registrados. Agrega cada proveedor de internet de la localidad con «Nuevo enlace».
        </div>
    @endforelse
</div>
