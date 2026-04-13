{{--
    livewire/asignaciones/crear-asignacion.blade.php v2
    ─────────────────────────────────────────────────────────────────────────
    B1 — Filtro ampliado:
         · Buscador ahora cubre código, serial, hostname Y atributos EAV
         · Nuevo select "Ubicación" junto a los chips de categoría
    B2 — Toggle grilla / lista:
         · Botón par de íconos apps/view_list en la barra de filtros del paso 2
         · wire:click="toggleVistaEquipos" persiste en Livewire
         · Vista grilla = tarjetas (grid)
         · Vista lista  = filas compactas (tabla simplificada)
    El resto de la vista (paso 1, carrito, confirmación) es idéntico a v1.
    ─────────────────────────────────────────────────────────────────────────
--}}
<div class="space-y-5">

    {{-- ── Indicador de pasos ──────────────────────────────────────────────── --}}
    <div class="flex items-center gap-0">
        <div class="flex items-center gap-2">
            <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        {{ $paso === 1 ? 'bg-cerberus-primary text-white' : 'bg-emerald-600 text-white' }}">
                @if ($paso > 1)
                    <span class="material-icons text-sm">check</span>
                @else
                    1
                @endif
            </div>
            <span
                class="text-sm {{ $paso === 1 ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400 dark:text-cerberus-steel' }}">
                Receptor
            </span>
        </div>
        <div class="flex-1 h-px mx-3 {{ $paso > 1 ? 'bg-cerberus-primary' : 'bg-gray-200 dark:bg-cerberus-steel/40' }}">
        </div>
        <div class="flex items-center gap-2">
            <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        {{ $paso === 2 ? 'bg-cerberus-primary text-white' : 'bg-gray-200 dark:bg-cerberus-steel/40 text-gray-500 dark:text-cerberus-steel' }}">
                2
            </div>
            <span
                class="text-sm {{ $paso === 2 ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400 dark:text-cerberus-steel' }}">
                Equipos
            </span>
        </div>
    </div>

    @error('general')
        <div
            class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    {{-- ════════════════════════════ PASO 1 ════════════════════════════════ --}}
    @if ($paso === 1)
        <div
            class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                    rounded-xl p-6 space-y-5 animate-fade-in">

            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-lg">person_pin</span>
                ¿A quién se asignan los equipos?
            </h2>

            {{-- Toggle tipo receptor --}}
            <div class="flex gap-3">
                <button type="button" wire:click="$set('tipo_receptor', 'usuario')"
                    class="flex-1 flex items-center gap-3 px-4 py-3 rounded-lg border transition
                           {{ $tipo_receptor === 'usuario'
                               ? 'bg-cerberus-primary/10 dark:bg-cerberus-primary/20 border-cerberus-primary text-gray-900 dark:text-white'
                               : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light hover:border-cerberus-accent' }}">
                    <span
                        class="material-icons {{ $tipo_receptor === 'usuario' ? 'text-cerberus-primary' : 'text-gray-400 dark:text-cerberus-steel' }}">person</span>
                    <div class="text-left">
                        <p class="text-sm font-medium">Personal</p>
                        <p class="text-xs opacity-60">Asignación a un usuario</p>
                    </div>
                </button>
                <button type="button" wire:click="$set('tipo_receptor', 'area')"
                    class="flex-1 flex items-center gap-3 px-4 py-3 rounded-lg border transition
                           {{ $tipo_receptor === 'area'
                               ? 'bg-cerberus-primary/10 dark:bg-cerberus-primary/20 border-cerberus-primary text-gray-900 dark:text-white'
                               : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light hover:border-cerberus-accent' }}">
                    <span
                        class="material-icons {{ $tipo_receptor === 'area' ? 'text-cerberus-primary' : 'text-gray-400 dark:text-cerberus-steel' }}">corporate_fare</span>
                    <div class="text-left">
                        <p class="text-sm font-medium">Área común</p>
                        <p class="text-xs opacity-60">Departamento / sala compartida</p>
                    </div>
                </button>
            </div>

            {{-- Selector según tipo --}}
            @if ($tipo_receptor === 'usuario')
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-cerberus-accent mb-1">
                        Usuario receptor <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="usuario_id"
                        class="w-full bg-white dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel
                                   text-gray-900 dark:text-white rounded-lg px-3 py-2.5 text-sm
                                   focus:border-cerberus-primary focus:ring-1 focus:ring-cerberus-primary/30 transition">
                        <option value="">— Seleccionar usuario —</option>
                        @foreach ($this->usuarios as $u)
                            <option value="{{ $u->id }}">
                                {{ $u->name }}{{ $u->cargo ? ' · ' . $u->cargo->nombre : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('usuario_id')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @if (auth()->user()->hasRole('Administrador'))
                    <x-form.select label="Empresa de la asignación" wire:model.live="empresa_personal_id"
                        :options="$this->empresasArea" required
                        hint="Empresa bajo la cual quedará registrada esta asignación personal." :error="$errors->first('empresa_personal_id')" />
                @endif
            @else
                {{-- Área común --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-form.select label="Empresa del área *" :options="$this->empresasArea" wire:model.live="area_empresa_id"
                            placeholder="— Empresa —" />
                        @error('area_empresa_id')
                            <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-form.select label="Departamento *" :options="$this->departamentosArea->isEmpty() ? [] : $this->departamentosArea->toArray()" wire:model="area_departamento_id"
                            placeholder="{{ $area_empresa_id ? '— Departamento —' : '← Primero empresa' }}" />
                        @error('area_departamento_id')
                            <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-form.select label="Responsable *" :options="$this->responsablesArea->pluck('name', 'id')->toArray()" wire:model="area_responsable_id"
                            placeholder="— Responsable —" />
                        @error('area_responsable_id')
                            <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            {{-- Fecha y observaciones --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-form.input type="date" label="Fecha de asignación *" wire:model="fecha_asignacion" />
                    @error('fecha_asignacion')
                        <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <x-form.textarea label="Observaciones" wire:model="observaciones" placeholder="Motivo, notas adicionales..."
                rows="2" />
        </div>

        <div class="flex justify-end">
            <button type="button" wire:click="irPaso2" wire:loading.attr="disabled"
                class="flex items-center gap-2 px-6 py-2.5 rounded-lg bg-cerberus-primary hover:bg-cerberus-hover
                       text-white font-medium text-sm transition disabled:opacity-50">
                <span wire:loading.remove wire:target="irPaso2">Seleccionar equipos</span>
                <span wire:loading wire:target="irPaso2">Validando...</span>
                <span class="material-icons text-base" wire:loading.remove wire:target="irPaso2">arrow_forward</span>
            </button>
        </div>
    @endif

    {{-- ════════════════════════════ PASO 2 ════════════════════════════════ --}}
    @if ($paso === 2)
        <div class="flex gap-5 items-start animate-fade-in">

            {{-- ── Grilla/Lista izquierda ──────────────────────────────────── --}}
            <div class="flex-1 min-w-0 space-y-4">

                {{-- ── Panel de filtros ─────────────────────────────────────── --}}
                <div
                    class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                            rounded-xl p-4 space-y-3">

                    {{-- Fila 1: buscador + toggle vista (B1 + B2) --}}
                    <div class="flex gap-2 items-center">

                        {{-- Buscador ampliado (B1) --}}
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                <span
                                    class="material-icons text-base text-gray-400 dark:text-cerberus-steel">search</span>
                            </span>
                            <input type="text" wire:model.live.400ms="filtro_busqueda"
                                placeholder="Código, serial, hostname, marca, modelo..."
                                class="w-full bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/60
                                          text-gray-900 dark:text-white rounded-lg pl-10 pr-4 py-2.5 text-sm
                                          focus:outline-none focus:border-cerberus-primary focus:ring-1 focus:ring-cerberus-primary/30 transition" />
                        </div>

                        {{-- Toggle grilla / lista (B2) --}}
                        <div
                            class="flex rounded-lg border border-gray-200 dark:border-cerberus-steel/50 overflow-hidden flex-shrink-0">
                            <button type="button" wire:click="toggleVistaEquipos"
                                title="{{ $vistaEquipos === 'grilla' ? 'Cambiar a vista lista' : 'Cambiar a vista grilla' }}"
                                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium transition
                                           {{ $vistaEquipos === 'grilla'
                                               ? 'bg-cerberus-primary text-white'
                                               : 'bg-white dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-light hover:bg-gray-50' }}">
                                <span class="material-icons text-base">apps</span>
                                <span class="hidden sm:inline">Grilla</span>
                            </button>
                            <button type="button" wire:click="toggleVistaEquipos"
                                title="{{ $vistaEquipos === 'lista' ? 'Cambiar a vista grilla' : 'Cambiar a vista lista' }}"
                                class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium transition
                                           border-l border-gray-200 dark:border-cerberus-steel/50
                                           {{ $vistaEquipos === 'lista'
                                               ? 'bg-cerberus-primary text-white'
                                               : 'bg-white dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-light hover:bg-gray-50' }}">
                                <span class="material-icons text-base">view_list</span>
                                <span class="hidden sm:inline">Lista</span>
                            </button>
                        </div>

                    </div>

                    {{-- Fila 2: chips de categoría + select de ubicación (B1) --}}
                    <div class="flex flex-wrap items-center gap-2">

                        {{-- Chip "Todas" --}}
                        <button type="button" wire:click="setCategoriaFiltro('')"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition
                                       {{ $filtro_categoria === ''
                                           ? 'bg-cerberus-primary border-cerberus-primary text-white'
                                           : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel/60 text-gray-600 dark:text-cerberus-light hover:border-cerberus-primary' }}">
                            <span class="material-icons text-xs">apps</span> Todas
                        </button>

                        {{-- Chips por categoría --}}
                        @foreach ($this->categorias as $cat)
                            <button type="button" wire:click="setCategoriaFiltro('{{ $cat->id }}')"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition
                                           {{ $filtro_categoria === (string) $cat->id
                                               ? 'bg-cerberus-primary border-cerberus-primary text-white'
                                               : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel/60 text-gray-600 dark:text-cerberus-light hover:border-cerberus-primary' }}">
                                {{ $cat->nombre }}
                                <span
                                    class="px-1.5 py-0.5 rounded-full text-xs
                                             {{ $filtro_categoria === (string) $cat->id
                                                 ? 'bg-white/25 text-white'
                                                 : 'bg-gray-200 dark:bg-cerberus-steel/50 text-gray-600 dark:text-cerberus-light' }}">
                                    {{ $cat->disponibles_count }}
                                </span>
                            </button>
                        @endforeach

                        {{-- Separador --}}
                        <div class="h-5 w-px bg-gray-200 dark:bg-cerberus-steel/40 mx-1 hidden sm:block"></div>

                        {{-- Select de ubicación (B1 — nuevo) --}}
                        <div class="flex-shrink-0">
                            <select wire:model.live="filtro_ubicacion"
                                class="text-xs rounded-lg px-2.5 py-1.5
                                           bg-white dark:bg-cerberus-dark
                                           border border-gray-200 dark:border-cerberus-steel/50
                                           text-gray-700 dark:text-cerberus-light
                                           focus:border-cerberus-primary transition">
                                <option value="">Todas las ubicaciones</option>
                                @foreach ($this->ubicacionesOpciones as $id => $nombre)
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                {{-- ── Resultados: grilla o lista (B2) ─────────────────────── --}}
                <div wire:loading.class="opacity-50 pointer-events-none"
                    wire:target="setCategoriaFiltro,filtro_busqueda,filtro_ubicacion,toggleVistaEquipos">

                    @if ($this->equiposDisponibles->isEmpty())
                        <div
                            class="flex flex-col items-center justify-center py-16 bg-white dark:bg-cerberus-mid
                                    border border-gray-200 dark:border-cerberus-steel rounded-xl text-center">
                            <span
                                class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel/40 mb-3">inventory_2</span>
                            <p class="text-sm text-gray-500 dark:text-cerberus-accent">Sin equipos disponibles</p>
                            @if ($filtro_busqueda || $filtro_categoria || $filtro_ubicacion)
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-1">Prueba ajustando los
                                    filtros</p>
                            @endif
                        </div>
                    @elseif ($vistaEquipos === 'grilla')
                        {{-- ─── VISTA GRILLA (tarjetas) ────────────────────── --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach ($this->equiposDisponibles as $equipo)
                                @php
                                    $estaEnCarrito = collect($carrito)->contains('id', $equipo->id);
                                    $atvsVisible = $equipo->atributosActuales
                                        ->filter(fn($v) => $v->atributo?->visible_en_tabla)
                                        ->take(3);
                                @endphp
                                <div
                                    class="bg-white dark:bg-cerberus-mid
                                            border {{ $estaEnCarrito ? 'border-cerberus-primary/60 dark:border-cerberus-primary/50' : 'border-gray-200 dark:border-cerberus-steel' }}
                                            rounded-xl p-4 flex flex-col gap-3 transition">

                                    <div class="flex items-start gap-2">
                                        <span
                                            class="material-icons text-2xl text-cerberus-accent flex-shrink-0">devices</span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $equipo->codigo_interno }}
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-cerberus-steel truncate">
                                                {{ $equipo->categoria?->nombre ?? '—' }}
                                            </p>
                                            @if ($equipo->nombre_maquina)
                                                <p class="text-xs text-gray-500 dark:text-cerberus-accent truncate">
                                                    {{ $equipo->nombre_maquina }}
                                                </p>
                                            @endif
                                            @if ($equipo->serial)
                                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">S/N:
                                                    {{ $equipo->serial }}</p>
                                            @endif
                                            {{-- Atributos EAV visibles --}}
                                            @if ($atvsVisible->isNotEmpty())
                                                <p class="text-xs text-gray-400 dark:text-cerberus-steel/70 mt-1">
                                                    {{ $atvsVisible->map(fn($v) => $v->atributo->nombre . ': ' . $v->valor)->implode(' · ') }}
                                                </p>
                                            @endif
                                            @if ($equipo->ubicacion)
                                                <p
                                                    class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5 flex items-center gap-1">
                                                    <span class="material-icons text-xs">location_on</span>
                                                    {{ $equipo->ubicacion->nombre }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <button type="button" wire:click="agregarAlCarrito({{ $equipo->id }})"
                                        @disabled($estaEnCarrito)
                                        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-medium transition
                                                   {{ $estaEnCarrito
                                                       ? 'bg-cerberus-primary/10 dark:bg-cerberus-primary/20 text-cerberus-primary dark:text-cerberus-accent cursor-default'
                                                       : 'bg-cerberus-primary hover:bg-cerberus-hover text-white' }}">
                                        <span class="material-icons text-sm">
                                            {{ $estaEnCarrito ? 'check_circle' : 'add_shopping_cart' }}
                                        </span>
                                        {{ $estaEnCarrito ? 'En carrito' : 'Agregar' }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- ─── VISTA LISTA (filas compactas) ─────────────── --}}
                        <div
                            class="bg-white dark:bg-cerberus-mid
                                    border border-gray-200 dark:border-cerberus-steel
                                    rounded-xl overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-gray-100 dark:border-cerberus-steel/40
                                               bg-gray-50 dark:bg-cerberus-dark">
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider">
                                            Código</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider">
                                            Categoría</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider hidden md:table-cell">
                                            Hostname</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider hidden lg:table-cell">
                                            Serial</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider hidden lg:table-cell">
                                            Atributos</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider hidden md:table-cell">
                                            Ubicación</th>
                                        <th
                                            class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-cerberus-steel uppercase tracking-wider">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-cerberus-steel/20">
                                    @foreach ($this->equiposDisponibles as $equipo)
                                        @php
                                            $estaEnCarrito = collect($carrito)->contains('id', $equipo->id);
                                            $atvsVisible = $equipo->atributosActuales
                                                ->filter(fn($v) => $v->atributo?->visible_en_tabla)
                                                ->take(2);
                                        @endphp
                                        <tr
                                            class="hover:bg-gray-50 dark:hover:bg-cerberus-steel/10 transition
                                                   {{ $estaEnCarrito ? 'bg-cerberus-primary/5 dark:bg-cerberus-primary/10' : '' }}">
                                            <td class="px-4 py-2.5">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $equipo->codigo_interno }}</p>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-cerberus-accent">
                                                {{ $equipo->categoria?->nombre ?? '—' }}
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-xs text-gray-500 dark:text-cerberus-accent hidden md:table-cell">
                                                {{ $equipo->nombre_maquina ?? '—' }}
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-xs text-gray-500 dark:text-cerberus-accent hidden lg:table-cell">
                                                {{ $equipo->serial ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 hidden lg:table-cell">
                                                @if ($atvsVisible->isNotEmpty())
                                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel/70">
                                                        {{ $atvsVisible->map(fn($v) => $v->atributo->nombre . ': ' . $v->valor)->implode(' · ') }}
                                                    </p>
                                                @else
                                                    <span
                                                        class="text-xs text-gray-300 dark:text-cerberus-steel/40">—</span>
                                                @endif
                                            </td>
                                            <td
                                                class="px-4 py-2.5 text-xs text-gray-500 dark:text-cerberus-accent hidden md:table-cell">
                                                {{ $equipo->ubicacion?->nombre ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-center">
                                                <button type="button"
                                                    wire:click="agregarAlCarrito({{ $equipo->id }})"
                                                    @disabled($estaEnCarrito)
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition
                                                               {{ $estaEnCarrito
                                                                   ? 'bg-cerberus-primary/10 text-cerberus-primary dark:text-cerberus-accent cursor-default'
                                                                   : 'bg-cerberus-primary hover:bg-cerberus-hover text-white' }}">
                                                    <span class="material-icons text-sm">
                                                        {{ $estaEnCarrito ? 'check' : 'add' }}
                                                    </span>
                                                    {{ $estaEnCarrito ? 'Añadido' : 'Agregar' }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Paginación --}}
                    @if ($this->equiposDisponibles->hasPages())
                        <div class="mt-3 flex justify-center">
                            {{ $this->equiposDisponibles->links('vendor.livewire.cerberus-pagination') }}
                        </div>
                    @endif

                </div>
            </div>

            {{-- ── Carrito derecho ──────────────────────────────────────────── --}}
            <div class="w-80 flex-shrink-0 sticky top-20 space-y-3">

                {{-- Receptor --}}
                <div
                    class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4">
                    <p
                        class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-cerberus-steel mb-2">
                        Asignando a
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="material-icons text-base text-cerberus-accent">
                            {{ $tipo_receptor === 'usuario' ? 'person' : 'corporate_fare' }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $this->receptorNombre }}
                        </span>
                    </div>
                    <button type="button" wire:click="volverPaso1"
                        class="mt-1.5 text-xs text-cerberus-primary hover:text-cerberus-hover transition">
                        ← Cambiar receptor
                    </button>
                </div>

                {{-- Items del carrito --}}
                <div
                    class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl overflow-hidden">

                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-cerberus-steel/50">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                            <span class="material-icons text-base text-cerberus-accent">shopping_cart</span>
                            Carrito
                        </p>
                        @if (count($carrito) > 0)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-cerberus-primary text-white font-medium">
                                {{ count($carrito) }}
                            </span>
                        @endif
                    </div>

                    <div class="max-h-80 overflow-y-auto divide-y divide-gray-50 dark:divide-cerberus-steel/20">
                        @forelse ($carrito as $index => $item)
                            <div wire:key="carrito-{{ $item['uid'] }}" class="px-4 py-3 space-y-2">

                                <div class="flex items-center gap-2">
                                    <span class="material-icons text-base text-cerberus-accent flex-shrink-0">
                                        {{ $item['icono'] }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                                            {{ $item['codigo'] }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-cerberus-steel truncate">
                                            {{ $item['categoria'] }}
                                            @if ($item['serial'] !== '—')
                                                · {{ $item['serial'] }}
                                            @endif
                                        </p>
                                    </div>
                                    <button type="button" wire:click="quitarDelCarrito('{{ $item['uid'] }}')"
                                        class="flex-shrink-0 text-gray-300 dark:text-cerberus-steel/60 hover:text-red-500 transition">
                                        <span class="material-icons text-base">remove_circle_outline</span>
                                    </button>
                                </div>

                                {{-- Selector de padre (periférico) --}}
                                @if (count($this->itemsPrincipalesCarrito) > 1 ||
                                        (count($this->itemsPrincipalesCarrito) === 1 && $this->itemsPrincipalesCarrito[0]['uid'] !== $item['uid']))
                                    <div>
                                        <select wire:change="setPadre('{{ $item['uid'] }}', $event.target.value)"
                                            class="w-full text-xs rounded-lg px-2 py-1.5
                                                       bg-gray-50 dark:bg-cerberus-dark
                                                       border border-gray-200 dark:border-cerberus-steel/60
                                                       text-gray-700 dark:text-cerberus-light
                                                       focus:border-cerberus-primary transition">
                                            <option value="">Sin vincular (equipo principal)</option>
                                            @foreach ($this->itemsPrincipalesCarrito as $principal)
                                                @if ($principal['uid'] !== $item['uid'])
                                                    <option value="{{ $principal['uid'] }}"
                                                        {{ $item['padre_uid'] === $principal['uid'] ? 'selected' : '' }}>
                                                        {{ $principal['codigo'] }} ({{ $principal['categoria'] }})
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-8 px-4 text-center">
                                <span
                                    class="material-icons text-2xl text-gray-200 dark:text-cerberus-steel/30 mb-1">add_shopping_cart</span>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                    Selecciona equipos de la grilla
                                </p>
                            </div>
                        @endforelse
                    </div>

                    @error('carrito')
                        <div class="px-4 py-2 bg-red-50 dark:bg-red-900/20 border-t border-red-100 dark:border-red-700/30">
                            <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                <span class="material-icons text-xs">error_outline</span> {{ $message }}
                            </p>
                        </div>
                    @enderror

                    <div class="p-4 border-t border-gray-100 dark:border-cerberus-steel/50">
                        <button type="button" wire:click="confirmar" wire:loading.attr="disabled"
                            @disabled(empty($carrito))
                            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-medium
                                       bg-cerberus-primary hover:bg-cerberus-hover text-white transition
                                       disabled:opacity-40 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="confirmar">
                                <span class="material-icons text-base">assignment_turned_in</span>
                            </span>
                            <span wire:loading wire:target="confirmar">
                                <span class="material-icons text-base animate-spin">refresh</span>
                            </span>
                            <span wire:loading.remove wire:target="confirmar">
                                Confirmar @if (count($carrito) > 0)
                                    ({{ count($carrito) }})
                                @endif
                            </span>
                            <span wire:loading wire:target="confirmar">Guardando...</span>
                        </button>
                    </div>
                </div>

                {{-- Botón volver --}}
                <button type="button" wire:click="volverPaso1"
                    class="w-full py-2 text-xs text-gray-400 dark:text-cerberus-steel hover:text-gray-600 dark:hover:text-cerberus-light transition">
                    ← Volver al paso 1
                </button>

            </div>
        </div>
    @endif

</div>
