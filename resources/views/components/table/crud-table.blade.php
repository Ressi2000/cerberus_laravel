@props([
    'headers' => [],
    'paginated' => null,
    'export' => false,
    'exportRoute' => null,
    'filters' => [],
])

<div class="relative bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl">

    {{-- TOP BAR --}}
    <div class="p-4 flex items-center justify-between">

        <div class="text-sm text-cerberus-light">
            @if($paginated)
                {{ $paginated->total() }} registros encontrados
            @endif
        </div>

        {{-- Botón exportar --}}
        @if ($export && $exportRoute)
            @php
                $filtersClean = collect($filters)->filter()->toArray();
            @endphp
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open"
                    class="flex items-center gap-2 bg-cerberus-dark border border-cerberus-steel text-white px-4 py-2 rounded-lg hover:bg-cerberus-steel transition-colors">
                    <span class="material-icons text-base">file_download</span>
                    Exportar
                    <span class="material-icons text-sm">expand_more</span>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 z-10 w-44 bg-cerberus-mid rounded-md shadow-cerberus mt-2 border border-cerberus-steel"
                     style="display: none;">
                    <ul class="py-1 text-sm text-cerberus-light">
                        <li>
                            <a href="{{ route($exportRoute, array_merge(['format' => 'xlsx'], $filtersClean)) }}"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-steel/20 transition-colors">
                                <span class="material-icons text-sm text-green-400">table_chart</span>
                                Excel (.xlsx)
                            </a>
                        </li>
                        <li>
                            <a href="{{ route($exportRoute, array_merge(['format' => 'csv'], $filtersClean)) }}"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-steel/20 transition-colors">
                                <span class="material-icons text-sm text-blue-400">description</span>
                                CSV (.csv)
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- TABLE CONTAINER --}}
    <div class="overflow-x-auto relative">

        {{-- Spinner de carga Livewire --}}
        <div wire:loading.flex
            class="absolute inset-0 backdrop-blur-sm bg-black/40 flex items-center justify-center z-30 rounded-b-xl">
            <div class="flex flex-col items-center gap-3">
                <div class="h-10 w-10 border-4 border-cerberus-primary border-t-transparent rounded-full animate-spin"></div>
                <span class="text-white font-medium tracking-wide">Cargando...</span>
            </div>
        </div>

        <table class="w-full text-sm text-left">
            <thead class="bg-cerberus-steel/40 text-gray-200 uppercase text-xs">
                <tr>
                    @foreach ($headers as $h)
                        <th scope="col" class="px-6 py-3 font-semibold tracking-wide">
                            {{ $h }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-cerberus-steel/30">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if ($paginated && $paginated->hasPages())
        <div class="p-4 border-t border-cerberus-steel/30">
            {{ $paginated->links('vendor.livewire.cerberus-pagination') }}
        </div>
    @endif

</div>
