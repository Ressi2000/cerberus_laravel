@props([
    'headers'     => [],
    'paginated'   => null,
    'export'      => false,
    'exportRoute' => null,
    'filters'     => [],
])

<div class="relative bg-white dark:bg-cerberus-mid
            border border-gray-200 dark:border-cerberus-steel/60
            shadow-sm dark:shadow-none
            rounded-xl overflow-hidden">

    {{-- ── TOP BAR ─────────────────────────────────────────────────────────── --}}
    <div class="px-4 py-3 flex items-center justify-between
                border-b border-gray-100 dark:border-cerberus-steel/30
                bg-gray-50/50 dark:bg-cerberus-dark/20">

        {{-- Conteo de registros --}}
        <div class="flex items-center gap-2">
            <span class="material-icons text-base text-gray-400 dark:text-cerberus-steel">
                table_rows
            </span>
            <p class="text-sm text-gray-500 dark:text-cerberus-light">
                @if($paginated)
                    <span class="font-semibold text-[#1E293B] dark:text-white">
                        {{ $paginated->total() }}
                    </span>
                    registro(s)
                @else
                    &nbsp;
                @endif
            </p>
        </div>

        {{-- Botón exportar --}}
        @if ($export && $exportRoute)
            @php $filtersClean = collect($filters)->filter()->toArray(); @endphp
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                    class="inline-flex items-center gap-2 text-sm font-medium
                           px-3 py-1.5 rounded-lg
                           bg-white dark:bg-cerberus-dark
                           border border-gray-200 dark:border-cerberus-steel
                           text-gray-700 dark:text-cerberus-light
                           hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                           hover:border-gray-300 dark:hover:border-cerberus-accent/30
                           transition-all duration-150 shadow-sm">
                    <span class="material-icons text-base text-green-500 dark:text-green-400">
                        file_download
                    </span>
                    Exportar
                    <span class="material-icons text-sm text-gray-400 transition-transform duration-200"
                          :class="{ 'rotate-180': open }">
                        expand_more
                    </span>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                     class="absolute right-0 z-50 mt-2 w-44
                            bg-white dark:bg-cerberus-mid
                            border border-gray-100 dark:border-cerberus-steel/60
                            rounded-xl shadow-lg dark:shadow-black/20 overflow-hidden"
                     style="display: none;">
                    <ul class="py-1 text-sm">
                        <li>
                            <a href="{{ route($exportRoute, array_merge(['format' => 'xlsx'], $filtersClean)) }}"
                               @click="open = false"
                               class="flex items-center gap-3 px-4 py-2.5
                                      text-gray-600 dark:text-cerberus-light
                                      hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                      transition-colors duration-100">
                                <span class="material-icons text-base text-green-500">table_chart</span>
                                Excel (.xlsx)
                            </a>
                        </li>
                        <li>
                            <a href="{{ route($exportRoute, array_merge(['format' => 'csv'], $filtersClean)) }}"
                               @click="open = false"
                               class="flex items-center gap-3 px-4 py-2.5
                                      text-gray-600 dark:text-cerberus-light
                                      hover:bg-gray-50 dark:hover:bg-cerberus-steel/20
                                      transition-colors duration-100">
                                <span class="material-icons text-base text-blue-500">description</span>
                                CSV (.csv)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- ── TABLE WRAPPER
         IMPORTANTE: overflow-visible para que los dropdowns de acciones
         no queden recortados cuando hay pocos registros.
         El scroll horizontal se maneja con un div interno.
    ──────────────────────────────────────────────────────────────────────────── --}}
    <div class="relative">

        {{-- Spinner de carga Livewire --}}
        <div wire:loading.flex
            class="absolute inset-0 backdrop-blur-[2px] bg-white/60 dark:bg-black/40
                   items-center justify-center z-20 rounded-b-xl">
            <div class="flex flex-col items-center gap-3 py-8">
                <div class="h-9 w-9 border-[3px] border-[#1E40AF]/20 dark:border-cerberus-primary/20
                            border-t-[#1E40AF] dark:border-t-cerberus-primary
                            rounded-full animate-spin">
                </div>
                <span class="text-sm font-medium text-gray-500 dark:text-cerberus-light">
                    Cargando...
                </span>
            </div>
        </div>

        {{-- Scroll horizontal para la tabla (no clip vertical) --}}
        <div class="overflow-x-auto">
            {{--
                min-h: garantiza que incluso con 1-2 filas haya espacio
                para que el dropdown de acciones se despliegue sin quedar cortado.
                pb-16: padding bottom extra para los dropdowns inferiores.
            --}}
            <div style="min-height: 200px; padding-bottom: 3rem;">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-cerberus-dark/40
                                   border-b border-gray-200 dark:border-cerberus-steel/40">
                            @foreach ($headers as $h)
                                <th scope="col"
                                    class="px-4 py-3 text-xs font-semibold uppercase tracking-wider
                                           text-gray-500 dark:text-cerberus-accent
                                           whitespace-nowrap">
                                    {{ $h }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-cerberus-steel/20">
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── PAGINACIÓN ───────────────────────────────────────────────────────── --}}
    @if ($paginated && $paginated->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-cerberus-steel/30
                    bg-gray-50/50 dark:bg-cerberus-dark/20">
            {{ $paginated->links('vendor.livewire.cerberus-pagination') }}
        </div>
    @endif

</div>