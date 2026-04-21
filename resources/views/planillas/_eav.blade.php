{{--
    Partial: planillas._eav
    Parámetros:
      $atributos    — Collection de EquipoAtributoValor (ya filtrados, sin marca/modelo)
      $esPeriferico — bool, cambia color del bloque
      $colspan      — int, cuántas columnas abarca la tabla principal
--}}
@php
    $eavItems = $atributos
        ->filter(fn($v) => $v->atributo !== null && $v->valor !== null && $v->valor !== '')
        ->values();

    // Agrupar en filas de 3
    $filas = $eavItems->chunk(3);
@endphp

@if ($eavItems->isNotEmpty())
    @if ($esPeriferico ?? false)
        <tr class="tr-eav-per periferico-group">
            <td colspan="{{ $colspan ?? 7 }}">
                <div class="eav-titulo-per">Características técnicas</div>
                <table class="eav-tabla">
                    @foreach ($filas as $fila)
                        <tr>
                            @foreach ($fila as $av)
                                <td>
                                    <div class="eav-attr-label">{{ $av->atributo->nombre }}</div>
                                    <div class="eav-attr-valor">{{ $av->valor }}</div>
                                </td>
                            @endforeach
                            @for ($i = $fila->count(); $i < 3; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    @else
        <tr class="tr-eav equipo-group">
            <td colspan="{{ $colspan ?? 7 }}">
                <div class="eav-titulo">Características técnicas</div>
                <table class="eav-tabla">
                    @foreach ($filas as $fila)
                        <tr>
                            @foreach ($fila as $av)
                                <td>
                                    <div class="eav-attr-label">{{ $av->atributo->nombre }}</div>
                                    <div class="eav-attr-valor">{{ $av->valor }}</div>
                                </td>
                            @endforeach
                            @for ($i = $fila->count(); $i < 3; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    @endif
@endif