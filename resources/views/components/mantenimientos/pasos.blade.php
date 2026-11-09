@props(['mantenimiento', 'editable' => true])

@php
    $m = $mantenimiento;
    $esCorrectivo = $m->esCorrectivo();

    $pasos = $esCorrectivo
        ? ['Reportado', 'Diagnosticado', 'En reparación', 'Reparado', 'Cerrado']
        : ['Programado', 'En proceso', 'Completado'];

    // Cancelado / Dado de baja son salidas alternativas, no un paso más de
    // la línea normal — se siguen mostrando con su propio badge/aviso en el
    // header, pero no tiene sentido dibujar el stepper para ellas.
    $fueraDeRuta = in_array($m->estado, ['Cancelado', 'Dado de baja'], true);

    $indiceActual   = array_search($m->estado, $pasos, true);
    $retrocedibles  = $m->estadosRetrocedibles();
    $puedeRetroceder = in_array($m->estado, $retrocedibles, true);
@endphp

@unless ($fueraDeRuta || $indiceActual === false)
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-5 overflow-x-auto">
        <div class="flex items-center min-w-max">
            @foreach ($pasos as $i => $paso)
                @php
                    $esActual = $i === $indiceActual;
                    $esPasado = $i < $indiceActual;

                    // Desde "Cerrado" solo el paso "Reparado" es clicable, y
                    // abre el form de reabrir (con motivo) en vez de
                    // retroceder directo — reabrir() siempre aterriza en
                    // "En reparación" sin importar cuál paso pasado se toque.
                    $accionReabrir   = $editable && $esCorrectivo && $m->estado === 'Cerrado' && $paso === 'Reparado';
                    $accionRetroceder = $editable && $esPasado && $puedeRetroceder && in_array($paso, $retrocedibles, true);
                    $esClicable      = $accionReabrir || $accionRetroceder;
                @endphp

                <div class="flex items-center {{ $i < count($pasos) - 1 ? 'flex-1' : '' }}">
                    <button
                        type="button"
                        @if ($accionReabrir)
                            wire:click="abrirReabrir"
                        @elseif ($accionRetroceder)
                            wire:click="$dispatch('confirmar', {
                                titulo: 'Volver de paso',
                                mensaje: '¿Volver el caso a «{{ $paso }}»? Lo que ya quedó registrado no se borra.',
                                accionEvento: 'casoRetrocederConfirmado',
                                accionParams: ['{{ $paso }}'],
                                variant: 'warning',
                                confirmLabel: 'Volver',
                            })"
                        @endif
                        @unless ($esClicable) disabled @endunless
                        title="{{ $esClicable ? ($accionReabrir ? 'Reabrir caso' : "Volver a «{$paso}»") : $paso }}"
                        class="flex flex-col items-center gap-1.5 flex-shrink-0 group
                               {{ $esClicable ? 'cursor-pointer' : 'cursor-default' }}"
                    >
                        <span @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold border-2 transition',
                            'bg-green-600 border-green-600 text-white group-hover:bg-green-700' => $esPasado,
                            'bg-[#1E40AF] border-[#1E40AF] text-white' => $esActual,
                            'bg-gray-100 dark:bg-cerberus-dark border-gray-300 dark:border-cerberus-steel text-gray-400 dark:text-cerberus-steel' => ! $esPasado && ! $esActual,
                        ])>
                            @if ($esPasado)
                                <span class="material-icons text-sm">{{ $esClicable ? 'undo' : 'check' }}</span>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span @class([
                            'text-xs font-medium whitespace-nowrap',
                            'text-gray-900 dark:text-white' => $esActual,
                            'text-gray-500 dark:text-cerberus-light' => $esPasado,
                            'text-gray-400 dark:text-cerberus-steel' => ! $esPasado && ! $esActual,
                        ])>{{ $paso }}</span>
                    </button>

                    @if ($i < count($pasos) - 1)
                        <div class="h-0.5 flex-1 mx-1 {{ $i < $indiceActual ? 'bg-green-600' : 'bg-gray-200 dark:bg-cerberus-steel/40' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endunless
