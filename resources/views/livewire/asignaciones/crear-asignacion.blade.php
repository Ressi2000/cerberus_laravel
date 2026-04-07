{{-- livewire/asignaciones/crear-asignacion.blade.php — versión final --}}
<div class="space-y-5">

    {{-- ── Indicador de pasos ──────────────────────────────────────────────── --}}
    <div class="flex items-center gap-0">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        {{ $paso === 1 ? 'bg-cerberus-primary text-white' : 'bg-emerald-600 text-white' }}">
                @if ($paso > 1)<span class="material-icons text-sm">check</span>@else 1 @endif
            </div>
            <span class="text-sm {{ $paso === 1 ? 'text-white font-medium' : 'text-gray-400 dark:text-cerberus-steel' }}">
                Receptor
            </span>
        </div>
        <div class="flex-1 h-px mx-3 {{ $paso > 1 ? 'bg-cerberus-primary' : 'bg-gray-200 dark:bg-cerberus-steel/40' }}"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        {{ $paso === 2 ? 'bg-cerberus-primary text-white' : 'bg-gray-200 dark:bg-cerberus-steel/40 text-gray-500 dark:text-cerberus-steel' }}">
                2
            </div>
            <span class="text-sm {{ $paso === 2 ? 'text-white font-medium' : 'text-gray-400 dark:text-cerberus-steel' }}">
                Equipos
            </span>
        </div>
    </div>

    @error('general')
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    {{-- ════════════════════════════ PASO 1 ════════════════════════════════ --}}
    @if ($paso === 1)
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                    rounded-xl p-6 space-y-5 animate-fade-in">

            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-lg">person_pin</span>
                ¿A quién se asignan los equipos?
            </h2>

            {{-- Toggle tipo --}}
            <div class="flex gap-3">
                <button type="button" wire:click="$set('tipo_receptor', 'usuario')"
                    class="flex-1 flex items-center gap-3 px-4 py-3 rounded-lg border transition
                           {{ $tipo_receptor === 'usuario'
                               ? 'bg-cerberus-primary/10 dark:bg-cerberus-primary/20 border-cerberus-primary text-gray-900 dark:text-white'
                               : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light hover:border-cerberus-accent' }}">
                    <span class="material-icons {{ $tipo_receptor === 'usuario' ? 'text-cerberus-primary' : 'text-gray-400 dark:text-cerberus-steel' }}">person</span>
                    <div class="text-left">
                        <p class="text-sm font-medium">Usuario personal</p>
                        <p class="text-xs opacity-60">Asignación a un empleado</p>
                    </div>
                </button>
                <button type="button" wire:click="$set('tipo_receptor', 'area')"
                    class="flex-1 flex items-center gap-3 px-4 py-3 rounded-lg border transition
                           {{ $tipo_receptor === 'area'
                               ? 'bg-cerberus-primary/10 dark:bg-cerberus-primary/20 border-cerberus-primary text-gray-900 dark:text-white'
                               : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel text-gray-600 dark:text-cerberus-light hover:border-cerberus-accent' }}">
                    <span class="material-icons {{ $tipo_receptor === 'area' ? 'text-cerberus-primary' : 'text-gray-400 dark:text-cerberus-steel' }}">corporate_fare</span>
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

            @else
                {{-- Área común --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-form.select label="Empresa del área *" :options="$this->empresasArea"
                            wire:model.live="area_empresa_id" placeholder="— Empresa —" />
                        @error('area_empresa_id')
                            <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <x-form.select label="Departamento *"
                            :options="$this->departamentosArea->isEmpty() ? [] : $this->departamentosArea->toArray()"
                            wire:model="area_departamento_id"
                            placeholder="{{ $area_empresa_id ? '— Departamento —' : '— Selecciona empresa primero —' }}" />
                        @error('area_departamento_id')
                            <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-cerberus-accent mb-1">
                            Responsable <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="area_responsable_id"
                                class="w-full bg-white dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel
                                       text-gray-900 dark:text-white rounded-lg px-3 py-2.5 text-sm
                                       focus:border-cerberus-primary focus:ring-1 focus:ring-cerberus-primary/30 transition">
                            <option value="">— Responsable —</option>
                            @foreach ($this->responsablesArea as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                        @error('area_responsable_id')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-form.input type="date" label="Fecha de asignación *" wire:model="fecha_asignacion" />
                    @error('fecha_asignacion')
                        <p class="text-xs text-red-500 -mt-3 mb-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <x-form.textarea label="Observaciones" wire:model="observaciones"
                placeholder="Motivo, notas adicionales..." rows="2" />
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

            {{-- ── Grilla izquierda ─────────────────────────────────────────── --}}
            <div class="flex-1 min-w-0 space-y-4">

                {{-- Filtros --}}
                <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                            rounded-xl p-4 space-y-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel">search</span>
                        </span>
                        <input type="text" wire:model.live.400ms="filtro_busqueda"
                               placeholder="Código, serial, hostname..."
                               class="w-full bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/60
                                      text-gray-900 dark:text-white rounded-lg pl-10 pr-4 py-2.5 text-sm
                                      focus:outline-none focus:border-cerberus-primary focus:ring-1 focus:ring-cerberus-primary/30 transition" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="setCategoriaFiltro('')"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition
                                       {{ $filtro_categoria === ''
                                           ? 'bg-cerberus-primary border-cerberus-primary text-white'
                                           : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel/60 text-gray-600 dark:text-cerberus-light hover:border-cerberus-primary' }}">
                            <span class="material-icons text-xs">apps</span> Todas
                        </button>
                        @foreach ($this->categorias as $cat)
                            <button type="button" wire:click="setCategoriaFiltro('{{ $cat->id }}')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition
                                           {{ $filtro_categoria === (string)$cat->id
                                               ? 'bg-cerberus-primary border-cerberus-primary text-white'
                                               : 'bg-gray-50 dark:bg-cerberus-dark border-gray-200 dark:border-cerberus-steel/60 text-gray-600 dark:text-cerberus-light hover:border-cerberus-primary' }}">
                                {{ $cat->nombre }}
                                <span class="px-1.5 py-0.5 rounded-full text-xs
                                             {{ $filtro_categoria === (string)$cat->id ? 'bg-white/25 text-white' : 'bg-gray-200 dark:bg-cerberus-steel/50 text-gray-600 dark:text-cerberus-light' }}">
                                    {{ $cat->disponibles_count }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Grilla de tarjetas --}}
                <div wire:loading.class="opacity-50 pointer-events-none" wire:target="setCategoriaFiltro,filtro_busqueda">
                    @if ($this->equiposDisponibles->isEmpty())
                        <div class="flex flex-col items-center justify-center py-16 bg-white dark:bg-cerberus-mid
                                    border border-gray-200 dark:border-cerberus-steel rounded-xl text-center">
                            <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel/40 mb-3">inventory_2</span>
                            <p class="text-sm text-gray-500 dark:text-cerberus-accent">Sin equipos disponibles</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @foreach ($this->equiposDisponibles as $equipo)
                                @php
                                    $estaEnCarrito = collect($carrito)->contains('id', $equipo->id);
                                    $marcaValor  = $equipo->atributosActuales->first(fn($v) => strtolower($v->atributo?->slug ?? '') === 'marca')?->valor;
                                    $modeloValor = $equipo->atributosActuales->first(fn($v) => strtolower($v->atributo?->slug ?? '') === 'modelo')?->valor;
                                @endphp
                                <div wire:key="card-{{ $equipo->id }}"
                                     class="group flex flex-col bg-white dark:bg-cerberus-mid border rounded-xl p-4 transition-all duration-150
                                            {{ $estaEnCarrito
                                                ? 'border-emerald-400 dark:border-emerald-500/50 ring-1 ring-emerald-400/30'
                                                : 'border-gray-200 dark:border-cerberus-steel/60 hover:border-cerberus-primary hover:shadow-md' }}">

                                    <div class="flex items-start justify-between gap-2 mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                                        {{ $estaEnCarrito ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-cerberus-primary/10 dark:bg-cerberus-primary/15' }}">
                                                <span class="material-icons text-lg {{ $estaEnCarrito ? 'text-emerald-600 dark:text-emerald-400' : 'text-cerberus-primary dark:text-cerberus-accent' }}">
                                                    {{ $this->iconoCategoria($equipo->categoria?->nombre ?? '') }}
                                                </span>
                                            </div>
                                            <span class="text-xs text-gray-500 dark:text-cerberus-accent truncate">
                                                {{ $equipo->categoria?->nombre ?? '—' }}
                                            </span>
                                        </div>
                                        @if ($estaEnCarrito)
                                            <span class="flex-shrink-0 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-xs
                                                         bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400
                                                         border border-emerald-200 dark:border-emerald-700/40">
                                                <span class="material-icons text-xs">check</span> Agregado
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex-1 mb-3">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $equipo->codigo_interno }}</p>
                                        @if ($marcaValor || $modeloValor)
                                            <p class="text-xs text-gray-600 dark:text-cerberus-light mt-0.5 truncate">
                                                {{ implode(' · ', array_filter([$marcaValor, $modeloValor])) }}
                                            </p>
                                        @endif
                                        @if ($equipo->serial && $equipo->serial !== '—')
                                            <p class="text-xs text-gray-400 dark:text-cerberus-steel mt-0.5">S/N {{ $equipo->serial }}</p>
                                        @endif
                                    </div>

                                    @if (!$estaEnCarrito)
                                        <button type="button" wire:click="agregarAlCarrito({{ $equipo->id }})"
                                                class="w-full flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium transition
                                                       bg-cerberus-primary/10 dark:bg-cerberus-primary/15 text-cerberus-primary dark:text-cerberus-accent
                                                       border border-cerberus-primary/20 dark:border-cerberus-primary/30
                                                       hover:bg-cerberus-primary hover:text-white dark:hover:bg-cerberus-primary dark:hover:text-white">
                                            <span class="material-icons text-sm">add_circle_outline</span>
                                            Agregar
                                        </button>
                                    @else
                                        <button type="button"
                                                wire:click="quitarDelCarrito({{ collect($carrito)->search(fn($i) => $i['id'] === $equipo->id) }})"
                                                class="w-full flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium transition
                                                       bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400
                                                       border border-red-200 dark:border-red-700/40 hover:bg-red-100">
                                            <span class="material-icons text-sm">remove_circle_outline</span>
                                            Quitar
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($this->equiposDisponibles->hasPages())
                            <div class="mt-4 flex justify-center">
                                {{ $this->equiposDisponibles->links('vendor.livewire.cerberus-pagination') }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- ── Carrito derecho ──────────────────────────────────────────── --}}
            <div class="w-80 flex-shrink-0 sticky top-20 space-y-3">

                {{-- Receptor --}}
                <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-cerberus-steel mb-2">
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
                <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl overflow-hidden">

                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-cerberus-steel/50">
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
                                        </p>
                                    </div>
                                    <button type="button" wire:click="quitarDelCarrito({{ $index }})"
                                            class="flex-shrink-0 text-gray-300 dark:text-cerberus-steel/60 hover:text-red-500 transition">
                                        <span class="material-icons text-base">close</span>
                                    </button>
                                </div>

                                {{-- Selector de vinculación --}}
                                @if ($this->itemsPrincipalesCarrito)
                                    <div class="ml-6">
                                        <label class="text-xs text-gray-400 dark:text-cerberus-steel">Vincular a:</label>
                                        <select
                                            wire:change="vincularPadre({{ $index }}, $event.target.value)"
                                            class="w-full mt-0.5 bg-gray-50 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/60
                                                   text-gray-700 dark:text-cerberus-light rounded-lg px-2 py-1 text-xs
                                                   focus:outline-none focus:border-cerberus-primary transition">
                                            <option value="">Sin vincular (principal)</option>
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
                                <span class="material-icons text-2xl text-gray-200 dark:text-cerberus-steel/30 mb-1">add_shopping_cart</span>
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
                                Confirmar @if(count($carrito) > 0)({{ count($carrito) }})@endif
                            </span>
                            <span wire:loading wire:target="confirmar">Guardando...</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif

</div>