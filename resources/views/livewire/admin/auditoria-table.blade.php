<div class="space-y-6">

    {{-- MODAL --}}
    <livewire:admin.auditoria-modal />

    {{-- HEADER + FILTROS --}}

    <x-table.crud-header :title="$isProfileView ? 'Mi actividad' : 'Auditoría del sistema'" :subtitle="$isProfileView
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
                        <x-form.select name="usuario_id" label="Usuario" :options="$usuarios" wire:model.live="usuario_id" />
                    @endif
                    <x-form.select name="accion" label="Acción" :options="$acciones" wire:model.live="accion" />

                    <x-form.select name="tabla" label="Tabla" :options="$tablas" wire:model.live="tabla" />

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
    <x-table.crud-table :headers="$headers" :paginated="$auditorias" :export="!$isProfileView" exportRoute="export.auditoria"
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
                    <x-table.audit-action-badge :accion="$log->accion" />
                </td>
                <td class="px-4 py-3 text-cerberus-light">
                    {{ $log->tabla }}
                </td>
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
        @endforeach
    </x-crud-table>
</div>
