<div class="space-y-6" x-data="equiposColumnas()" x-init="init()">

    {{-- ── Modales ─────────────────────────────────────────────────────────── --}}
    @livewire('equipos.equipo-view-modal')
    @livewire('equipos.equipo-delete-modal')

    {{-- ── STATS CARDS ─────────────────────────────────────────────────────── --}}
    <x-ui.stats-cards :items="[
        ['title' => 'Total equipos', 'value' => $total, 'icon' => 'inventory_2'],
        ['title' => 'Activos', 'value' => $totalActivos, 'icon' => 'check_circle'],
        ['title' => 'Garantía vencida', 'value' => $garantiaVencida, 'icon' => 'warning'],
        ['title' => 'En mantenimiento', 'value' => $enMantenimiento, 'icon' => 'build'],
    ]" />

    {{-- ── HEADER + FILTROS ────────────────────────────────────────────────── --}}
    <x-table.crud-header title="Equipos" subtitle="Inventario tecnológico corporativo" buttonLabel="Registrar equipo"
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

                {{-- FILA 1: Búsqueda — ancho completo --}}
                <x-form.input label="Buscar" wire:model.live.500ms="search"
                    placeholder="Código interno, serial, hostname..."
                    hint="Busca por código interno, número de serie o nombre de máquina (hostname)." />

                {{-- FILA 2: Selects principales en grid de 3 columnas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4">
                    <x-form.select label="Categoría" :options="$this->categorias" wire:model.live="categoria_id"
                        hint="Filtra por tipo de equipo. Al seleccionar una categoría aparecerán sus atributos técnicos como filtros adicionales." />
                    <x-form.select label="Estado" :options="$this->estados" wire:model.live="estado_id"
                        hint="Estado operativo actual del equipo en el sistema." />
                    <x-form.select label="Ubicación" :options="$this->ubicaciones" wire:model.live="ubicacion_id"
                        hint="Ubicación física donde se encuentra el equipo." />
                </div>

                {{-- FILA 3: Fechas + Activo + Garantía --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4">

                    <x-form.input type="date" label="Adquisición desde" wire:model.live="fecha_desde"
                        hint="Filtra equipos adquiridos a partir de esta fecha." />

                    <x-form.input type="date" label="Adquisición hasta" wire:model.live="fecha_hasta" />

                    {{-- Activo/Baja --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Condición
                        </label>
                        <div class="flex items-center gap-3 h-[38px] text-sm text-cerberus-light">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="" wire:model.live="activo"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Todos
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="1" wire:model.live="activo"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Activos
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="0" wire:model.live="activo"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Baja
                            </label>
                        </div>
                    </div>

                    {{-- Garantía --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">
                            Garantía
                        </label>
                        <div class="flex items-center gap-3 h-[38px] text-sm text-cerberus-light">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="" wire:model.live="garantia"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Todas
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="vigente" wire:model.live="garantia"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Vigente
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" value="vencida" wire:model.live="garantia"
                                    class="text-cerberus-primary border-cerberus-steel bg-cerberus-dark">
                                Vencida
                            </label>
                        </div>
                    </div>

                </div>

                {{-- FILA 4: Filtros EAV dinámicos --}}
                @if ($this->atributosFiltrables->count())
                    <div>
                        <p class="text-xs text-cerberus-accent uppercase tracking-wide font-semibold mb-3">
                            Características técnicas — {{ $this->categorias[$categoria_id] ?? '' }}
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4">
                            @foreach ($this->atributosFiltrables as $atributo)
                                @if ($atributo->tipo === 'boolean')
                                    <x-form.select :label="$atributo->nombre" :options="[1 => 'Sí', 0 => 'No']"
                                        wire:model.live="filtros.{{ $atributo->id }}" />
                                @elseif ($atributo->tipo === 'select' && $atributo->opciones)
                                    <x-form.select :label="$atributo->nombre" :options="$atributo->opciones"
                                        wire:model.live="filtros.{{ $atributo->id }}" />
                                @else
                                    <x-form.input :label="$atributo->nombre" wire:model.live.500ms="filtros.{{ $atributo->id }}"
                                        :placeholder="'Filtrar por ' . strtolower($atributo->nombre) . '...'" />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </x-slot>

        </x-crud-header>

        {{-- ── SELECTOR DE COLUMNAS VISIBLES ──────────────────────────────────── --}}
        <div class="flex justify-end">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">

                <button @click="open = !open"
                    class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg
                           bg-cerberus-mid border border-cerberus-steel
                           text-cerberus-light hover:text-white transition">
                    <span class="material-icons text-base">view_column</span>
                    Columnas
                    <span class="material-icons text-sm">expand_more</span>
                </button>

                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 z-50 mt-1 w-52
                        bg-cerberus-mid border border-cerberus-steel
                        rounded-xl shadow-cerberus overflow-hidden"
                    style="display:none">
                    <div class="px-3 py-2 border-b border-cerberus-steel">
                        <p class="text-xs font-semibold text-cerberus-accent uppercase tracking-wide">
                            Columnas visibles
                        </p>
                    </div>
                    <ul class="py-2 text-sm text-cerberus-light max-h-72 overflow-y-auto">
                        <template x-for="(label, key) in columnLabels" :key="key">
                            <li>
                                <label
                                    class="flex items-center gap-3 px-4 py-2
                                          hover:bg-cerberus-dark cursor-pointer transition">
                                    <input type="checkbox" x-model="columnas[key]" @change="save()"
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

        {{-- ── TABLA ───────────────────────────────────────────────────────────── --}}
        <div class="relative bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl">

            {{-- Top bar: count + export --}}
            <div class="px-4 py-3 flex items-center justify-between border-b border-cerberus-steel/30">
                <p class="text-sm text-cerberus-light">
                    {{ $equipos->total() }} equipo(s) encontrado(s)
                </p>
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                        class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg
                               bg-cerberus-dark border border-cerberus-steel
                               text-white hover:bg-cerberus-steel transition">
                        <span class="material-icons text-base">file_download</span>
                        Exportar
                        <span class="material-icons text-sm">expand_more</span>
                    </button>
                    <div x-show="open"
                        class="absolute right-0 z-10 mt-1 w-44
                            bg-cerberus-mid border border-cerberus-steel
                            rounded-xl shadow-cerberus overflow-hidden"
                        style="display:none">
                        <ul class="py-1 text-sm text-cerberus-light">
                            <li>
                                <a href="{{ route('export.equipos', array_merge($this->filterParams, ['format' => 'xlsx'])) }}"
                                    class="flex items-center gap-2 px-4 py-2
                                      hover:bg-cerberus-dark transition">
                                    <span class="material-icons text-sm text-green-400">table_chart</span>
                                    Excel (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('export.equipos', array_merge($this->filterParams, ['format' => 'csv'])) }}"
                                    class="flex items-center gap-2 px-4 py-2
                                      hover:bg-cerberus-dark transition">
                                    <span class="material-icons text-sm text-blue-400">description</span>
                                    CSV (.csv)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="overflow-x-auto relative">

                {{-- Spinner Livewire --}}
                <div wire:loading.flex
                    class="absolute inset-0 backdrop-blur-sm bg-black/40 items-center
                        justify-center z-30 rounded-b-xl">
                    <div class="flex flex-col items-center gap-3">
                        <div
                            class="h-10 w-10 border-4 border-cerberus-primary
                                border-t-transparent rounded-full animate-spin">
                        </div>
                        <span class="text-white font-medium">Cargando...</span>
                    </div>
                </div>

                <table class="w-full text-sm text-left">
                    <thead class="bg-cerberus-steel/40 text-gray-200 uppercase text-xs">
                        <tr>
                            {{-- Código: siempre visible --}}
                            <th class="px-4 py-3 font-semibold tracking-wide">Código</th>

                            <th x-show="columnas.categoria" class="px-4 py-3 font-semibold tracking-wide">Categoría
                            </th>
                            <th x-show="columnas.marca_modelo" class="px-4 py-3 font-semibold tracking-wide">Marca /
                                Modelo</th>
                            <th x-show="columnas.estado" class="px-4 py-3 font-semibold tracking-wide">Estado</th>
                            <th x-show="columnas.serial" class="px-4 py-3 font-semibold tracking-wide">Serial</th>
                            <th x-show="columnas.nombre_maquina" class="px-4 py-3 font-semibold tracking-wide">
                                Hostname</th>
                            <th x-show="columnas.ubicacion" class="px-4 py-3 font-semibold tracking-wide">Ubicación
                            </th>
                            <th x-show="columnas.garantia" class="px-4 py-3 font-semibold tracking-wide">Garantía</th>
                            <th x-show="columnas.adquisicion" class="px-4 py-3 font-semibold tracking-wide">
                                Adquisición</th>
                            <th x-show="columnas.activo" class="px-4 py-3 font-semibold tracking-wide">Condición</th>
                            <th x-show="columnas.creado" class="px-4 py-3 font-semibold tracking-wide">Creado</th>

                            {{-- Acciones: siempre visible --}}
                            <th class="px-4 py-3 text-center font-semibold tracking-wide">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-cerberus-steel/30">
                        @forelse ($equipos as $equipo)
                            @php
                                /*
                                 * Marca y Modelo son atributos EAV con slug 'marca' y 'modelo'.
                                 * Los buscamos entre los atributosActuales del equipo.
                                 * Si la categoría no los tiene, queda null → mostramos '—'.
                                 */
                                $marcaValor = $equipo->atributosActuales->first(
                                    fn($v) => str($v->atributo?->slug ?? '')
                                        ->lower()
                                        ->is('marca'),
                                )?->valor;
                                $modeloValor = $equipo->atributosActuales->first(
                                    fn($v) => str($v->atributo?->slug ?? '')
                                        ->lower()
                                        ->is('modelo'),
                                )?->valor;
                            @endphp

                            <tr wire:key="equipo-{{ $equipo->id }}" class="hover:bg-cerberus-darkest">

                                {{-- Código --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-white text-sm font-semibold">
                                        {{ $equipo->codigo_interno }}
                                    </span>
                                </td>

                                <td x-show="columnas.categoria" class="px-4 py-3 text-cerberus-light text-sm">
                                    {{ $equipo->categoria->nombre }}
                                </td>

                                {{-- Marca / Modelo —— columna combinada --}}
                                <td x-show="columnas.marca_modelo" class="px-4 py-3 text-sm">
                                    @if ($marcaValor || $modeloValor)
                                        <span class="text-white font-medium">
                                            {{ $marcaValor ?? '' }}
                                        </span>
                                        @if ($modeloValor)
                                            <span class="text-cerberus-light text-xs block">
                                                {{ $modeloValor }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-cerberus-steel">—</span>
                                    @endif
                                </td>

                                <td x-show="columnas.estado" class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-teal-400/10 text-teal-400 ring-teal-500/20">
                                        {{ $equipo->estado->nombre }}
                                    </span>
                                </td>

                                <td x-show="columnas.serial" class="px-4 py-3 text-cerberus-light text-sm font-mono">
                                    {{ $equipo->serial ?? '—' }}
                                </td>

                                <td x-show="columnas.nombre_maquina" class="px-4 py-3 text-cerberus-light text-sm">
                                    {{ $equipo->nombre_maquina ?? '—' }}
                                </td>

                                <td x-show="columnas.ubicacion" class="px-4 py-3 text-cerberus-light text-sm">
                                    {{ $equipo->ubicacion?->nombre ?? '—' }}
                                </td>

                                <td x-show="columnas.garantia" class="px-4 py-3 text-sm">
                                    @if ($equipo->fecha_garantia_fin)
                                        @php
                                            $vencida = \Carbon\Carbon::parse($equipo->fecha_garantia_fin)->isPast();
                                        @endphp
                                        <span class="{{ $vencida ? 'text-red-400' : 'text-green-400' }}">
                                            {{ \Carbon\Carbon::parse($equipo->fecha_garantia_fin)->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-cerberus-steel">—</span>
                                    @endif
                                </td>

                                <td x-show="columnas.adquisicion" class="px-4 py-3 text-cerberus-light text-sm">
                                    {{ $equipo->fecha_adquisicion ? \Carbon\Carbon::parse($equipo->fecha_adquisicion)->format('d/m/Y') : '—' }}
                                </td>

                                <td x-show="columnas.activo" class="px-4 py-3">
                                    @if ($equipo->activo)
                                        <span
                                            class="inline-flex items-center rounded-md bg-green-400/10
                                                 px-2 py-0.5 text-xs font-medium text-green-400
                                                 ring-1 ring-inset ring-green-500/20">
                                            Activo
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-md bg-red-400/10
                                                 px-2 py-0.5 text-xs font-medium text-red-400
                                                 ring-1 ring-inset ring-red-400/20">
                                            Baja
                                        </span>
                                    @endif
                                </td>

                                <td x-show="columnas.creado" class="px-4 py-3 text-cerberus-light text-sm">
                                    {{ $equipo->created_at->format('d/m/Y') }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <x-table.table-actions :model="$equipo" :editUrl="route('admin.equipos.edit', $equipo)" :viewUrl="route('admin.equipos.show', $equipo)"
                                        viewEvent="openEquipoView" deleteEvent="openEquipoDelete"
                                        deleteLabel="Dar de baja" :policy="$equipo">
                                        <x-slot name="acciones">
                                            <li>
                                                <a href="{{ route('admin.equipos.show', $equipo) }}" wire:navigate
                                                    @click="close()"
                                                    class="flex items-center gap-3 px-4 py-2.5 w-full
                                              text-gray-600 dark:text-cerberus-light
                                              hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                              hover:text-purple-600 dark:hover:text-purple-400
                                              transition-colors duration-100">
                                                    <span
                                                        class="material-icons text-base text-purple-500">history</span>
                                                    Historial completo
                                                </a>
                                            </li>
                                        </x-slot>
                                    </x-table.table-actions>
                                </td>


                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-12 text-center text-cerberus-light">
                                    <span class="material-icons text-4xl block mb-2 text-cerberus-steel">
                                        devices_other
                                    </span>
                                    No se encontraron equipos con los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if ($equipos->hasPages())
                <div class="px-4 py-3 border-t border-cerberus-steel/30">
                    {{ $equipos->links('vendor.livewire.cerberus-pagination') }}
                </div>
            @endif

        </div>

</div>

{{-- ── Alpine: columnas visibles con localStorage ─────────────────────────── --}}
<script>
    function equiposColumnas() {
        return {
            columnas: {
                categoria: true,
                marca_modelo: true, // ← nuevo: Marca / Modelo (EAV)
                estado: true,
                serial: true,
                nombre_maquina: false,
                ubicacion: true,
                garantia: false,
                adquisicion: false,
                activo: true,
                creado: false,
            },
            columnLabels: {
                categoria: 'Categoría',
                marca_modelo: 'Marca / Modelo', // ← nuevo
                estado: 'Estado',
                serial: 'Serial',
                nombre_maquina: 'Hostname',
                ubicacion: 'Ubicación',
                garantia: 'Fecha garantía',
                adquisicion: 'Fecha adquisición',
                activo: 'Condición',
                creado: 'Fecha creación',
            },
            init() {
                const saved = localStorage.getItem('cerberus_equipos_columnas')
                if (saved) {
                    try {
                        // Merge: conserva nuevas claves con su default si no estaban guardadas
                        this.columnas = {
                            ...this.columnas,
                            ...JSON.parse(saved)
                        }
                    } catch (e) {
                        /* ignorar JSON inválido */
                    }
                }
            },
            save() {
                localStorage.setItem('cerberus_equipos_columnas', JSON.stringify(this.columnas))
            },
        }
    }
</script>
