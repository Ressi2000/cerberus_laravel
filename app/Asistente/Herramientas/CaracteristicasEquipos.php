<?php

namespace App\Asistente\Herramientas;

use App\Asistente\Caracteristicas;
use App\Asistente\Herramienta;
use App\Asistente\HerramientaException;
use App\Models\AtributoEquipo;
use App\Models\CategoriaEquipo;
use App\Models\Equipo;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\EquipoAtributoValor;
use App\Models\User;

class CaracteristicasEquipos extends Herramienta
{
    /** Si una característica tiene más valores distintos que esto (seriales, IP…), no se listan ejemplos. */
    const MAX_DISTINTOS = 25;

    public function nombre(): string
    {
        return 'caracteristicas_equipos';
    }

    public function descripcion(): string
    {
        return 'Qué características técnicas tiene cada categoría de equipo (ej. Laptop: Procesador, RAM con sus campos '
            . 'Capacidad (GB)/Tipo/Formato, Disco Duro, Sistema Operativo…) y, cuando son pocos, qué valores existen '
            . '(ej. Sistema Operativo: "Win 10 Pro", "Win 11 Pro"). Úsala ANTES de filtrar buscar_equipos por una '
            . 'característica si no conoces su nombre exacto o sus valores. Sin categoría lista solo los nombres.';
    }

    public function parametros(): array
    {
        return [
            'categoria' => ['type' => 'string', 'description' => 'Categoría (ej. "Laptop"). Con categoría incluye los valores existentes.'],
        ];
    }

    public function ejecutar(User $actor, array $args): array
    {
        $ocultas = Caracteristicas::ocultas();
        $categorias = CategoriaEquipo::query()
            ->when($args['categoria'] ?? null, fn ($q, $c) => $q->where('nombre', 'like', $this->like($c)))
            ->with(['atributos' => fn ($q) => $q->whereNotIn('id', $ocultas)->orderBy('orden')])
            ->orderBy('nombre')->get();

        if ($categorias->isEmpty()) {
            throw new HerramientaException("No existe la categoría «{$args['categoria']}».");
        }

        $conValores = isset($args['categoria']) && $categorias->count() <= 3;
        $visibles = Equipo::visiblePara($actor)->select('equipos.id');

        return [
            'categorias' => $categorias->map(fn (CategoriaEquipo $c) => [
                'categoria'       => $c->nombre,
                'caracteristicas' => $c->atributos->map(function (AtributoEquipo $a) use ($conValores, $visibles) {
                    if (! $a->esGrupo()) {
                        return array_filter([
                            'nombre'  => $a->nombre,
                            'valores' => $conValores ? $this->valoresSimples($a, $visibles) : null,
                        ]);
                    }

                    return array_filter([
                        'nombre' => $a->nombre,
                        'tipo'   => 'varias entradas (ej. un módulo de RAM por entrada)',
                        'campos' => collect($a->sub_campos ?? [])->map(fn ($campo) => array_filter([
                            'nombre'  => $campo['nombre'] ?? 'Campo',
                            'valores' => $conValores ? $this->valoresCampo($a, $campo['id'], $visibles) : null,
                        ]))->values()->all(),
                    ]);
                })->values()->all(),
            ])->values()->all(),
        ];
    }

    private function valoresSimples(AtributoEquipo $a, $visibles): ?array
    {
        $todos = EquipoAtributoValor::where('atributo_id', $a->id)->where('es_actual', true)
            ->whereIn('equipo_id', $visibles)->whereNotNull('valor')->where('valor', '!=', '')
            ->limit(3000)->pluck('valor')->map(fn ($v) => trim((string) $v));

        return $this->ejemplos($todos);
    }

    /**
     * Valores para mostrar como ejemplo: solo si son pocos y se repiten
     * (marca, sistema operativo…). Seriales, IP o hostnames —uno distinto por
     * equipo— no sirven de ejemplo y no se listan.
     */
    private function ejemplos($todos): ?array
    {
        $todos = collect($todos)->filter();
        $distintos = $todos->unique(fn ($v) => mb_strtolower($v));

        if ($distintos->isEmpty() || $distintos->count() > self::MAX_DISTINTOS
            || ($distintos->count() === $todos->count() && $todos->count() > 2)) {
            return null;
        }

        return $distintos->sort()->values()->all();
    }

    private function valoresCampo(AtributoEquipo $a, string $campoId, $visibles): ?array
    {
        $todos = EquipoAtributoGrupoInstancia::where('atributo_id', $a->id)->where('es_actual', true)
            ->whereIn('equipo_id', $visibles)->limit(3000)->pluck('valores')
            ->map(fn ($v) => trim((string) ($v[$campoId] ?? '')));

        return $this->ejemplos($todos);
    }
}
