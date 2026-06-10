<div class="space-y-6">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('equipos.equipo-view-modal')
    @livewire('equipos.equipo-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total equipos',    'value' => $total,           'icon' => 'inventory_2'],
        ['title' => 'Activos',          'value' => $totalActivos,    'icon' => 'check_circle'],
        ['title' => 'Garantía vencida', 'value' => $garantiaVencida, 'icon' => 'warning'],
        ['title' => 'Vence en 30 días', 'value' => $garantiaProxima, 'icon' => 'schedule'],
        ['title' => 'En mantenimiento', 'value' => $enMantenimiento, 'icon' => 'build'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header
        title="Equipos"
        subtitle="Inventario tecnológico corporativo"
        buttonLabel="Registrar equipo"
        :buttonUrl="route('admin.equipos.create')">

        <x-slot name="filters">
            <div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl p-4 space-y-4">

                {{-- Badge de filtros activos --}}
                @if ($this->activeFiltersCount > 0)
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-cerberus-primary/60 text-white">
                            {{ $this->activeFiltersCount }} filtro(s) activo(s)
                        </span>
                        <button wire:click="resetFilters"
                            class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition">
                            <span class="material-icons text-xs">close</span>
                            Limpiar todos
                        </button>
                    </div>
                @endif

                {{-- FILA 1: búsqueda --}}
                <x-form.input
                    label="Buscar"
                    wire:model.live.500ms="search"
                    placeholder="Código, marca, modelo, serial..."
                    hint="Busca por código interno o cualquier atributo del equipo."
                />

                {{-- FILA 2: selects principales --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4">
                    <x-form.select searchable label="Categoría"  :options="$this->categorias"  wire:model.live="categoria_id" />
                    <x-form.select searchable label="Estado"     :options="$this->estados"     wire:model.live="estado_id" />
                    <x-form.select searchable label="Ubicación"  :options="$this->ubicaciones" wire:model.live="ubicacion_id" />
                </div>

                {{-- FILA 3: fechas + radios --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4">
                    <x-form.input type="date" label="Adquisición desde" wire:model.live="fecha_desde" />
                    <x-form.input type="date" label="Adquisición hasta" wire:model.live="fecha_hasta" />

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-cerberus-accent mb-1">Garantía</label>
                        <div class="flex items-center gap-4 h-[38px] text-sm text-cerberus-light">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="" wire:model.live="garantia"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark"> Todas
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="vigente" wire:model.live="garantia"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark"> Vigente
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="vencida" wire:model.live="garantia"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark"> Vencida
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-cerberus-accent mb-1">Condición</label>
                        <div class="flex items-center gap-4 h-[38px] text-sm text-cerberus-light">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="" wire:model.live="activo"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark"> Todos
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="1" wire:model.live="activo"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark"> Activos
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="0" wire:model.live="activo"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark"> Baja
                            </label>
                        </div>
                    </div>
                </div>

                {{-- FILA 4: atributos filtrables de la categoría (EAV) --}}
                @if ($this->atributosFiltrables->count())
                    <div>
                        <p class="text-xs text-cerberus-accent uppercase tracking-wide font-semibold mb-3">
                            Características — {{ $this->categorias[$categoria_id] ?? '' }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4">
                            @foreach ($this->atributosFiltrables as $atributo)
                                @if ($atributo->tipo === 'boolean')
                                    <x-form.select
                                        :label="$atributo->nombre"
                                        :options="['1' => 'Sí', '0' => 'No']"
                                        wire:model.live="filtros.{{ $atributo->id }}"
                                    />
                                @elseif ($atributo->tipo === 'select')
                                    <x-form.select
                                        :label="$atributo->nombre"
                                        :options="collect($atributo->opciones)->mapWithKeys(fn($v) => [$v => $v])->toArray()"
                                        wire:model.live="filtros.{{ $atributo->id }}"
                                    />
                                @else
                                    <x-form.input
                                        :label="$atributo->nombre"
                                        wire:model.live.500ms="filtros.{{ $atributo->id }}"
                                    />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </x-slot>

    </x-table.crud-header>

    {{-- ── TABLA + SELECTOR DE COLUMNAS ───────────────────────────────────── --}}
    {{--
        wire:key cambia con categoria_id: Alpine se re-monta y carga las
        preferencias de localStorage específicas de esa categoría.
    --}}
    <div wire:key="table-section-{{ $categoria_id }}"
         x-data="equiposColumnas(
             {{ $categoria_id ?: 'null' }},
             @js($this->atributosVisibles->map(fn($a) => ['id' => $a->id, 'nombre' => $a->nombre])->values())
         )"
         x-init="init()"
         class="space-y-3">

        {{-- Selector de columnas: solo aparece cuando la categoría tiene atributos visibles --}}
        @if ($this->atributosVisibles->count())
            <div class="flex justify-end">
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">

                    <button @click="open = !open"
                        class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg
                               bg-cerberus-mid border border-cerberus-steel
                               text-cerberus-light hover:text-cerberus-accent transition">
                        <span class="material-icons text-base">view_column</span>
                        Columnas
                        <span class="material-icons text-sm"
                              :class="open ? 'rotate-180' : ''"
                              style="transition: transform .15s">expand_more</span>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 z-50 mt-1 w-56
                                bg-cerberus-mid border border-cerberus-steel
                                rounded-xl shadow-cerberus overflow-hidden"
                         style="display:none">

                        <div class="px-3 py-2 border-b border-cerberus-steel">
                            <p class="text-xs font-semibold text-cerberus-accent uppercase tracking-wide">
                                Columnas adicionales
                            </p>
                        </div>

                        <ul class="py-2 text-sm text-cerberus-light max-h-72 overflow-y-auto">
                            <template x-for="(label, key) in columnLabels" :key="key">
                                <li>
                                    <label class="flex items-center gap-3 px-4 py-2
                                                  hover:bg-cerberus-dark cursor-pointer transition">
                                        <input type="checkbox"
                                               x-model="columnas[key]"
                                               @change="save()"
                                               class="rounded text-cerberus-primary
                                                      border-cerberus-steel bg-cerberus-dark">
                                        <span x-text="label"></span>
                                    </label>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabla --}}
        <x-table.crud-table
            :headers="$this->headers"
            :paginated="$equipos"
            export
            exportRoute="export.equipos"
            :filters="$this->filterParams">

            @forelse ($equipos as $equipo)
                <tr wire:key="equipo-{{ $equipo->id }}" class="hover:bg-cerberus-darkest">

                    {{-- Código: siempre visible --}}
                    <td class="px-4 py-3">
                        <span class="font-mono text-cerberus-light text-sm font-semibold">
                            {{ $equipo->codigo_interno }}
                        </span>
                    </td>

                    {{-- Categoría: solo cuando no hay filtro de categoría activo --}}
                    @if (! $categoria_id)
                        <td class="px-4 py-3 text-cerberus-light text-sm">
                            {{ $equipo->categoria->nombre }}
                        </td>
                    @endif

                    {{-- Estado: siempre visible --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                                     ring-1 ring-inset bg-teal-400/10 text-teal-400 ring-teal-500/20">
                            {{ $equipo->estado->nombre }}
                        </span>
                    </td>

                    {{-- Ubicación: siempre visible --}}
                    <td class="px-4 py-3 text-cerberus-light text-sm">
                        {{ $equipo->ubicacion?->nombre ?? '—' }}
                    </td>

                    {{-- Condición (activo/baja): siempre visible --}}
                    <td class="px-4 py-3">
                        @if ($equipo->activo)
                            <span class="inline-flex items-center rounded-md bg-green-400/10
                                         px-2 py-0.5 text-xs font-medium text-green-400
                                         ring-1 ring-inset ring-green-500/20">Activo</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-red-400/10
                                         px-2 py-0.5 text-xs font-medium text-red-400
                                         ring-1 ring-inset ring-red-400/20">Baja</span>
                        @endif
                    </td>

                    {{-- Columnas EAV dinámicas (solo cuando hay categoría seleccionada) --}}
                    @foreach ($this->atributosVisibles as $attr)
                        @php
                            $val = $equipo->atributosActuales
                                ->first(fn($v) => $v->atributo_id === $attr->id)
                                ?->valor;
                        @endphp
                        <td x-show="columnas['attr_{{ $attr->id }}'] ?? true"
                            class="px-4 py-3 text-cerberus-light text-sm">
                            @if ($attr->tipo === 'group')
                                @php $count = $equipo->grupoInstancias->where('atributo_id', $attr->id)->count(); @endphp
                                @if ($count > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full
                                                 bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400
                                                 border border-indigo-200 dark:border-indigo-500/30">
                                        <span class="material-icons text-xs">layers</span>
                                        {{ $count }}
                                    </span>
                                @else
                                    —
                                @endif
                            @elseif ($attr->tipo === 'boolean')
                                {{ $val !== null ? ($val ? 'Sí' : 'No') : '—' }}
                            @elseif ($attr->tipo === 'date' && $val)
                                {{ \Carbon\Carbon::parse($val)->format('d/m/Y') }}
                            @else
                                {{ $val ?? '—' }}
                            @endif
                        </td>
                    @endforeach

                    {{-- Acciones: siempre visible --}}
                    <td class="px-4 py-3 text-center">
                        <x-table.table-actions
                            :model="$equipo"
                            :editUrl="route('admin.equipos.edit', $equipo)"
                            viewEvent="openEquipoView"
                            deleteEvent="openEquipoDelete"
                            deleteLabel="Dar de baja"
                            :policy="$equipo">
                            <x-slot name="acciones">
                                <li>
                                    <a href="{{ route('admin.equipos.show', $equipo) }}"
                                       wire:navigate @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-cerberus-light hover:bg-cerberus-steel/20
                                              hover:text-purple-400 transition-colors duration-100">
                                        <span class="material-icons text-base text-purple-500">history</span>
                                        Historial completo
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.equipos.etiqueta', $equipo) }}"
                                       target="_blank" @click="close()"
                                       class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-cerberus-light hover:bg-cerberus-steel/20
                                              hover:text-amber-400 transition-colors duration-100">
                                        <span class="material-icons text-base text-amber-500">qr_code_2</span>
                                        Imprimir etiqueta
                                    </a>
                                </li>
                            </x-slot>
                        </x-table.table-actions>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="20" class="px-4 py-12 text-center text-cerberus-light">
                        <span class="material-icons text-4xl block mb-2 text-cerberus-steel">devices_other</span>
                        No se encontraron equipos con los filtros aplicados.
                    </td>
                </tr>
            @endforelse

        </x-table.crud-table>

    </div>{{-- /wire:key table-section --}}

</div>

@script
<script>
    window.equiposColumnas = function (categoriaId, atributos) {
        return {
            columnas: {},

            get columnLabels() {
                const labels = {};
                atributos.forEach(a => { labels['attr_' + a.id] = a.nombre; });
                return labels;
            },

            init() {
                const defaults = {};
                atributos.forEach(a => { defaults['attr_' + a.id] = true; });

                if (categoriaId) {
                    const saved = localStorage.getItem('cerberus_equipos_columnas_' + categoriaId);
                    if (saved) {
                        try { this.columnas = { ...defaults, ...JSON.parse(saved) }; }
                        catch (e) { this.columnas = defaults; }
                    } else {
                        this.columnas = defaults;
                    }
                } else {
                    this.columnas = defaults;
                }
            },

            save() {
                if (categoriaId) {
                    localStorage.setItem(
                        'cerberus_equipos_columnas_' + categoriaId,
                        JSON.stringify(this.columnas)
                    );
                }
            },
        };
    };
</script>
@endscript
