@props([
    'headers' => [],
    'paginated' => null,
    'export' => false,
    'exportRoute' => null,
    'actions' => true,
    'filters' => [],
])

<div class="relative bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl">

    {{-- TOP BAR --}}
    <div class="p-4 flex items-center justify-between">

        {{-- Count --}}
        <div class="text-sm text-cerberus-light">
            {{ $paginated?->total() ?? '' }} registros encontrados
        </div>

        {{-- Export button --}}
        @if ($export)
            @php
                // solo filtra valores NO vacíos
                $filtersClean = collect($filters)->filter()->toArray();
            @endphp
            <div class="relative">
                <button id="exportDropdownButton" data-dropdown-toggle="exportDropdown"
                    class="flex items-center gap-2 bg-cerberus-dark border border-cerberus-steel text-white px-4 py-2 rounded-lg hover:bg-cerberus-steel">
                    <span class="material-icons text-base">file_download</span>
                    Exportar
                    <span class="material-icons text-sm">expand_more</span>
                </button>

                <div id="exportDropdown" class="hidden z-10 w-44 bg-cerberus-mid rounded-md shadow-cerberus mt-2">
                    <ul class="py-1 text-sm text-cerberus-light" aria-labelledby="exportDropdownButton">
                        <li>
                            <a href="{{ route($exportRoute, array_merge(['format' => 'csv'], $filtersClean)) }}"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-steel/20">
                                CSV
                            </a>
                        </li>
                        <li>
                            <a href="{{ route($exportRoute, array_merge(['format' => 'xlsx'], $filtersClean)) }}"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-steel/20">
                                XLSX
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif

    </div>

    {{-- TABLE CONTAINER --}}
    <div wire:loading.flex
        class="absolute inset-0 backdrop-blur-sm bg-black/40 flex items-center justify-center z-20 rounded-xl">

        <div class="flex flex-col items-center gap-3 animate-fade-in">
            <div class="h-10 w-10 border-4 border-cerberus-primary border-t-transparent rounded-full animate-spin">
            </div>
            <span class="text-white font-medium tracking-wide">Cargando...</span>
        </div>

    </div>

    {{-- <div class="overflow-x-auto"> --}}
    <div class="overflow-x-auto">
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
</div>
{{-- </div> --}}

{{-- PAGINATION --}}
@if ($paginated)
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-4 border-t border-cerberus-steel">

        {{-- PER PAGE --}}
        <div class="flex items-center gap-3 text-sm text-cerberus-light">
            <span>Mostrar</span>

            <select wire:model.live="perPage"
                class="bg-cerberus-dark border border-cerberus-steel rounded-lg px-3 py-1 text-white text-sm focus:ring-cerberus-primary focus:border-cerberus-primary">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>

            <span>por página</span>
        </div>

        {{ $paginated->links('vendor.livewire.cerberus-pagination') }}

    </div>
@endif

</div>
