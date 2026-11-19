<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Herramienta;
use App\Models\Equipo;
use App\Models\PiezaExtraida;
use App\Models\PrestamoItem;
use App\Models\User;
use App\Services\RenovacionService;

class VerEquipo extends Herramienta
{
    public function nombre(): string
    {
        return 'ver_equipo';
    }

    public function descripcion(): string
    {
        return 'Ficha completa de UN equipo por su código interno: categoría, estado, ubicación, características '
            . '(procesador, RAM, discos, serial…), a quién está asignado o prestado, si tiene un caso de '
            . 'mantenimiento abierto, piezas del almacén instaladas y su clasificación de renovación.';
    }

    public function parametros(): array
    {
        return [
            'codigo' => ['type' => 'string', 'description' => 'Código interno del equipo, ej. "EQ-00057".'],
        ];
    }

    public function requeridos(): array
    {
        return ['codigo'];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $equipo = $this->encontrarEquipo($actor, $args['codigo']);
        $equipo->load([
            'categoria', 'estado', 'ubicacion', 'empresa', 'deposito',
            'atributosActuales.atributo', 'grupoInstancias.atributo',
            'asignacionItemActivo.asignacion.usuario', 'asignacionItemActivo.asignacion.areaDepartamento', 'asignacionItemActivo.asignacion.areaEmpresa',
            'mantenimientoAbierto.responsable',
        ]);

        $asignacion = $equipo->asignacionItemActivo?->asignacion;
        $prestamo = PrestamoItem::with('prestamo.usuario', 'prestamo.areaDepartamento', 'prestamo.areaEmpresa')
            ->where('equipo_id', $equipo->id)->where('devuelto', false)->latest('id')->first()?->prestamo;
        $caso = $equipo->mantenimientoAbierto;
        $evaluacion = app(RenovacionService::class)->evaluarEquipos(collect([$equipo]))->first();
        $renovacion = $evaluacion && $evaluacion['criterio']->evaluar ? $evaluacion['resultado'] : null;

        $piezas = PiezaExtraida::visiblePara($actor)->instaladas()->where('equipo_destino_id', $equipo->id)
            ->with('componenteAlmacen')->get()
            ->map(fn ($p) => trim(($p->componenteAlmacen?->nombre ?? $p->descripcion()) . ' · ' . $p->codigo()))->all();

        return array_filter([
            'codigo'            => $equipo->codigo_interno,
            'categoria'         => $equipo->categoria?->nombre,
            'estado'            => $equipo->estado?->nombre,
            'empresa'           => $equipo->empresa?->nombre,
            'ubicacion'         => $equipo->ubicacion?->nombre,
            'en_deposito'       => $equipo->deposito?->nombre,
            'fecha_adquisicion' => $this->fecha($equipo->fecha_adquisicion),
            'garantia_hasta'    => $this->fecha($equipo->fecha_garantia_fin),
            'observaciones'     => $equipo->observaciones,
            'caracteristicas'   => $this->caracteristicas($equipo),
            'asignado_a'        => $asignacion ? [
                'receptor' => $asignacion->receptorNombre(),
                'tipo'     => $asignacion->tipoReceptor() === 'personal' ? 'Personal' : 'Área común',
                'desde'    => $this->fecha($asignacion->fecha_asignacion),
            ] : null,
            'prestado_a'        => $prestamo ? [
                'receptor'           => $prestamo->receptorNombre(),
                'desde'              => $this->fecha($prestamo->fecha_prestamo),
                'devolucion_esperada' => $this->fecha($prestamo->fecha_devolucion_esperada),
                'estado'             => $prestamo->estado,
            ] : null,
            'caso_abierto'      => $caso ? [
                'numero'      => $caso->id,
                'tipo'        => $caso->esCorrectivo() ? 'Reparación' : 'Preventivo',
                'estado'      => $caso->estado,
                'responsable' => $caso->responsable?->name,
                'desde'       => $this->fecha($caso->fecha_inicio),
                'url'         => route('admin.mantenimientos.show', $caso),
            ] : null,
            'piezas_instaladas_del_almacen' => $piezas ?: null,
            'renovacion'        => $renovacion && $renovacion['clasificacion'] !== 'sin_datos' ? [
                'clasificacion' => RenovacionService::CLASIFICACIONES[$renovacion['clasificacion']],
                'motivos'       => array_values(array_merge($renovacion['obsoleto'] ?? [], $renovacion['mejoras'] ?? [], $renovacion['revisar'] ?? [])),
            ] : null,
            'url'               => route('admin.equipos.show', $equipo),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Características actuales como texto: simples "Nombre → valor"; grupos, una línea por entrada. */
    private function caracteristicas(Equipo $equipo): array
    {
        $salida = [];
        $ocultas = \App\Asistente\Caracteristicas::ocultas();   // PUK, claves… nunca salen

        foreach ($equipo->atributosActuales as $v) {
            if ($v->atributo && trim((string) $v->valor) !== '' && ! in_array($v->atributo_id, $ocultas)) {
                $salida[$v->atributo->nombre] = (string) $v->valor;
            }
        }

        foreach ($equipo->grupoInstancias->groupBy('atributo_id') as $instancias) {
            $atributo = $instancias->first()->atributo;
            if (! $atributo || in_array($atributo->id, $ocultas)) {
                continue;
            }
            $campos = collect($atributo->sub_campos ?? [])->keyBy('id');
            $salida[$atributo->nombre] = $instancias->map(function ($i) use ($campos) {
                return collect($i->valores ?? [])
                    ->reject(fn ($v) => $v === null || $v === '')
                    ->map(fn ($v, $id) => ($campos[$id]['nombre'] ?? 'Campo') . ': ' . $v)
                    ->implode(', ');
            })->filter()->values()->all();
        }

        return $salida;
    }
}
