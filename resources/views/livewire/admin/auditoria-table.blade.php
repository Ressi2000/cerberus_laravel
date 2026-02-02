<div class="space-y-6">

    {{-- MODAL --}}
    <livewire:admin.auditoria-modal />

    {{-- HEADER + FILTROS --}}

    <x-crud-header :title="$isProfileView ? 'Mi actividad' : 'Auditoría del sistema'" :subtitle="$isProfileView
        ? 'Registro de acciones realizadas por mi'
        : 'Registro de acciones realizadas en el sistema'">

        <x-slot name="filters">

            <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4">

                {{-- BADGE FILTROS --}}
                @if ($this->activeFiltersCount > 0)
                    <div class="mb-3">
                        <span class="px-3 py-1 text-xs rounded-md bg-cerberus-primary/60 text-white">
                            Filtros activos: {{ $this->activeFiltersCount }}
                        </span>
                    </div>
                @endif

                {{-- FILA 1 --}}
                <div class="flex flex-wrap gap-4">

                    @if (!$isProfileView)
                        <x-select name="usuario_id" label="Usuario" :options="$usuarios" wire:model.live="usuario_id" />
                    @endif
                    <x-select name="accion" label="Acción" :options="$acciones" wire:model.live="accion" />

                    <x-select name="tabla" label="Tabla" :options="$tablas" wire:model.live="tabla" />

                    <x-form.input type="date" name="fecha_desde" label="Desde" wire:model.live="fecha_desde" />

                    <x-form.input type="date" name="fecha_hasta" label="Hasta" wire:model.live="fecha_hasta" />

                </div>

                {{-- FILA 2 --}}
                <div class="flex items-center gap-4 mt-4 text-sm text-cerberus-light">
                    <button wire:click="resetFilters"
                        class="ml-auto bg-red-600/20 border border-red-700 text-red-300 px-3 py-2 rounded-lg hover:bg-red-700/40">
                        Limpiar filtros
                    </button>
                </div>

            </div>
        </x-slot>

    </x-crud-header>

    @php
        $headers = $isProfileView
            ? ['Fecha', 'Acción', 'Tabla', 'Detalle']
            : ['Fecha', 'Usuario', 'Acción', 'Tabla', 'Detalle'];
    @endphp

    {{-- TABLA --}}
    <x-crud-table :headers="$headers" :paginated="$auditorias" :export="!$isProfileView" exportRoute="export.auditoria"
        :filters="$this->filterParams">
        @foreach ($auditorias as $log)
            <tr wire:key="auditoria-{{ $log->id }}" class="hover:bg-cerberus-darkest">
                <td class="px-4 py-3 text-sm">
                    {{ $log->created_at ? \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i') : '—' }}
                </td>
                @if (!$isProfileView)
                    <td class="px-4 py-3 text-white">
                        {{ $log->usuario->name ?? 'Sistema' }}
                    </td>
                @endif
                <td class="px-4 py-3">
                    <x-audit-action-badge :accion="$log->accion" />
                </td>
                <td class="px-4 py-3 text-cerberus-light">
                    {{ $log->tabla }}
                </td>
                {{-- <td class="px-4 py-3">

                    @if (count($log->cambios))
                        <button wire:click="openModal({{ $log->id }})"
                            class="text-cerberus-accent hover:underline text-sm">
                            {{ count($log->cambios) }} cambio(s)
                        </button>
                    @else
                        <span class="text-xs text-cerberus-light">—</span>
                    @endif

                </td> --}}
                {{-- <td>
                    @if ($log->cambios)
                        <button wire:click="openModal({{ $log->id }})"
                            class="text-cerberus-accent hover:underline text-sm">
                            {{ count($log->cambios) }} cambio(s)
                        </button>
                    @else
                        <span class="text-xs text-cerberus-light">—</span>
                    @endif
                </td> --}}
                <td class="px-4 py-3">
                    @if ($log->cambios)
                        <button wire:click="$dispatch('openAuditoriaModal', { logId: {{ $log->id }} })"
                            class="text-cerberus-accent hover:underline text-sm">
                            {{ count($log->cambios) }} cambio(s)
                        </button>
                    @else
                        <span class="text-xs text-cerberus-light">—</span>
                    @endif
                </td>

            </tr>
            {{-- Fila expandida con detalles --}}
            @if ($expandedLogId === $log->id)
                <tr class="bg-cerberus-dark/20">
                    <td colspan="{{ $isProfileView ? 4 : 5 }}" class="p-4">
                        <div class="space-y-2">
                            @foreach ($log->cambios as $campo => $values)
                                <div class="border border-cerberus-steel rounded-lg p-3">
                                    <div class="text-cerberus-accent font-semibold mb-1">{{ $campo }}</div>
                                    <div class="grid grid-cols-2 gap-4 text-xs">
                                        <div>
                                            <div class="text-red-400 mb-1">Antes</div>
                                            <div class="bg-black/60 p-2 rounded font-mono break-all">
                                                {{ is_array($values['before']) ? json_encode($values['before'], JSON_UNESCAPED_UNICODE) : $values['before'] }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-green-400 mb-1">Después</div>
                                            <div class="bg-black/60 p-2 rounded font-mono break-all">
                                                {{ is_array($values['after']) ? json_encode($values['after'], JSON_UNESCAPED_UNICODE) : $values['after'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
    </x-crud-table>
</div>
