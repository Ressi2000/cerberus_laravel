@props([
    'headers' => [],
    'paginated' => null,
    'export' => false,
    'actions' => true,
])

<div class="bg-cerberus-mid border border-cerberus-steel shadow-cerberus rounded-xl">

    {{-- TOP BAR --}}
    <div class="p-4 flex items-center justify-between">

        {{-- Count --}}
        <div class="text-sm text-cerberus-light">
            {{ $paginated?->total() ?? '' }} registros encontrados
        </div>

        {{-- Export button --}}
        @if ($export)
            <div class="relative">
                <button
                    id="exportDropdownButton"
                    data-dropdown-toggle="exportDropdown"
                    class="flex items-center gap-2 bg-cerberus-dark border border-cerberus-steel text-white px-4 py-2 rounded-lg hover:bg-cerberus-steel"
                    type="button"
                    aria-expanded="false"
                >
                    <span class="material-icons text-base">file_download</span>
                    Exportar
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="exportDropdown" class="hidden z-10 w-44 bg-cerberus-mid rounded-md shadow-cerberus mt-2">
                    <ul class="py-1 text-sm text-cerberus-light" aria-labelledby="exportDropdownButton">
                        <li>
                            <a
                                href="{{ route('export.usuarios', ['format' => 'csv']) }}"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-steel/20"
                            >
                                <span class="material-icons text-base">upload_file</span>
                                Exportar CSV
                            </a>
                        </li>
                        <li>
                            <a
                                href="{{ route('export.usuarios', ['format' => 'xlsx']) }}"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-cerberus-steel/20"
                            >
                                <span class="material-icons text-base">table_view</span>
                                Exportar XLSX
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif

    </div>

    {{-- TABLE CONTAINER --}}
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
    {{-- </div> --}}

    {{-- PAGINATION --}}
    @if ($paginated)
        <div class="p-4 border-t border-cerberus-steel">
            {{ $paginated->links() }}
        </div>
    @endif

</div>