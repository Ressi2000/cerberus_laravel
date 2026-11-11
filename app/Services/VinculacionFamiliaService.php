<?php

namespace App\Services;

use App\Models\AtributoEquipo;
use App\Models\ComponenteAlmacen;
use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\FamiliaComponente;
use App\Models\PiezaExtraida;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * VinculacionFamiliaService
 * ─────────────────────────────────────────────────────────────────────────────
 * Hace que una característica de una categoría (ej. la "RAM (GB)" de Desktop)
 * pase a usar la ficha de una familia (ej. RAM), convirtiendo los valores que
 * ya están guardados.
 *
 * Los valores de un grupo se guardan con el ID de cada sub-campo
 * ({"a460…": "8", "4e7f…": "DDR4"}). Al usar la ficha de la familia, cada
 * valor tiene que pasar al ID del campo de la familia que le corresponde.
 * El "mapeo" dice, para cada campo de la familia, de dónde sale su valor:
 *
 *   [campo_familia_id => ['origen' => 'sub_campo', 'sub_campo_id' => '…']]  copiar de un sub-campo actual
 *   [campo_familia_id => ['origen' => 'fijo', 'valor' => 'SO-DIMM']]         el mismo valor para todos
 *   [campo_familia_id => ['origen' => 'vacio']]                              dejarlo vacío
 *
 * Se convierten, en una sola transacción: las instancias de los equipos
 * (vigentes y el historial), las piezas del almacén de esa característica,
 * y los nombres de los componentes del almacén que solo tienen esas piezas.
 */
class VinculacionFamiliaService
{
    const ORIGEN_SUB_CAMPO = 'sub_campo';
    const ORIGEN_FIJO      = 'fijo';
    const ORIGEN_VACIO     = 'vacio';

    /**
     * Mapeo sugerido: empareja por nombre (sin mayúsculas, tildes ni lo que
     * va entre paréntesis) cada campo de la familia con un sub-campo actual.
     * Los que no se emparejan quedan vacíos para que se elijan a mano.
     */
    public function mapeoSugerido(AtributoEquipo $atributo, FamiliaComponente $familia): array
    {
        $actuales = collect($this->subCamposPropios($atributo));

        return collect($familia->campos ?? [])
            ->mapWithKeys(function ($campo) use ($actuales) {
                $igual = $actuales->first(fn ($sc) => $this->normalizar($sc['nombre']) === $this->normalizar($campo['nombre']));

                return [$campo['id'] => $igual
                    ? ['origen' => self::ORIGEN_SUB_CAMPO, 'sub_campo_id' => $igual['id']]
                    : ['origen' => self::ORIGEN_VACIO]];
            })
            ->all();
    }

    /**
     * Vista previa de la conversión, sin guardar nada: cuántos registros se
     * tocan, ejemplos de antes → después y valores que no están en la lista
     * de opciones de la familia.
     */
    public function vistaPrevia(AtributoEquipo $atributo, FamiliaComponente $familia, array $mapeo): array
    {
        $this->validar($atributo, $familia, $mapeo);

        $instancias = EquipoAtributoGrupoInstancia::where('atributo_id', $atributo->id)->get();
        $piezas     = PiezaExtraida::where('atributo_id', $atributo->id)->get();

        $antesDespues = $instancias->where('es_actual', true)
            ->map(fn ($i) => [$this->describir($i->valores ?? [], $this->subCamposPropios($atributo)), $this->describir($this->convertir($i->valores ?? [], $mapeo), $familia->campos)])
            ->unique(fn ($par) => $par[0] . '|' . $par[1])
            ->values()
            ->take(10)
            ->all();

        return [
            'instancias_vigentes'  => $instancias->where('es_actual', true)->count(),
            'instancias_historial' => $instancias->where('es_actual', false)->count(),
            'piezas'               => $piezas->count(),
            'ejemplos'             => $antesDespues,
            'fuera_de_lista'       => $this->valoresFueraDeLista($instancias->pluck('valores')->concat($piezas->pluck('valor_extraido')), $familia, $mapeo),
        ];
    }

    /** Aplica la conversión y deja la característica usando la ficha de la familia. */
    public function vincular(AtributoEquipo $atributo, FamiliaComponente $familia, array $mapeo): array
    {
        $this->validar($atributo, $familia, $mapeo);

        return DB::transaction(function () use ($atributo, $familia, $mapeo) {
            $instancias = EquipoAtributoGrupoInstancia::where('atributo_id', $atributo->id)->get();
            foreach ($instancias as $instancia) {
                $instancia->update(['valores' => $this->convertir($instancia->valores ?? [], $mapeo)]);
            }

            $piezas = PiezaExtraida::where('atributo_id', $atributo->id)->get();
            foreach ($piezas as $pieza) {
                $pieza->update(['valor_extraido' => $this->convertir($pieza->valor_extraido ?? [], $mapeo)]);
            }

            // La columna sub_campos se conserva tal cual (respaldo); desde ahora
            // los campos salen de la familia. Una característica de familia
            // siempre es un grupo reutilizable.
            $atributo->forceFill([
                'familia_id'   => $familia->id,
                'tipo'         => AtributoEquipo::TIPO_GROUP,
                'reutilizable' => true,
            ])->save();

            $renombrados = $this->renombrarComponentes($atributo->fresh('familia'), $piezas);

            return [
                'instancias'  => $instancias->count(),
                'piezas'      => $piezas->count(),
                'componentes' => $renombrados,
            ];
        });
    }

    /**
     * Vincula una característica que todavía no tiene datos: no hay nada que
     * convertir, solo pasa a usar la ficha de la familia.
     */
    public function vincularSinDatos(AtributoEquipo $atributo, FamiliaComponente $familia): void
    {
        if ($atributo->exists && $atributo->tieneDatos()) {
            throw new \InvalidArgumentException("«{$atributo->nombre}» ya tiene datos guardados: hay que vincularla con la conversión de valores.");
        }

        $atributo->forceFill([
            'familia_id'   => $familia->id,
            'tipo'         => AtributoEquipo::TIPO_GROUP,
            'reutilizable' => true,
        ])->save();
    }

    /**
     * Deja de usar la familia. Los valores ya están guardados con los IDs de
     * los campos de la familia, así que se copian esos campos a la
     * característica: los datos siguen siendo válidos sin convertir nada.
     */
    public function desvincular(AtributoEquipo $atributo): void
    {
        $familia = $atributo->familia;

        if (! $familia) {
            return;
        }

        $atributo->forceFill([
            'familia_id' => null,
            'sub_campos' => collect($familia->camposComoSubCampos())
                ->map(fn ($c) => collect($c)->except('debe_coincidir')->all())
                ->all(),
        ])->save();
    }

    // ── Internos ─────────────────────────────────────────────────────────────

    /** Sub-campos guardados en la propia característica (ignorando la familia). */
    public function subCamposPropios(AtributoEquipo $atributo): array
    {
        $crudo = $atributo->getAttributes()['sub_campos'] ?? null;

        return is_array($crudo) ? $crudo : (json_decode((string) $crudo, true) ?: []);
    }

    /** Convierte un valor de grupo {sub_campo_id => v} a {campo_familia_id => v}. */
    public function convertir(array $valores, array $mapeo): array
    {
        $nuevo = [];

        foreach ($mapeo as $campoId => $regla) {
            $nuevo[$campoId] = match ($regla['origen'] ?? self::ORIGEN_VACIO) {
                self::ORIGEN_SUB_CAMPO => $valores[$regla['sub_campo_id']] ?? null,
                self::ORIGEN_FIJO      => $regla['valor'] ?? null,
                default                => null,
            };
        }

        return $nuevo;
    }

    private function validar(AtributoEquipo $atributo, FamiliaComponente $familia, array $mapeo): void
    {
        if (! $atributo->esGrupo()) {
            throw new \InvalidArgumentException("«{$atributo->nombre}» no es un grupo de campos: solo un grupo puede usar la ficha de una familia.");
        }

        if ($atributo->usaFamilia()) {
            throw new \InvalidArgumentException("«{$atributo->nombre}» ya usa la familia «{$atributo->familia?->nombre}».");
        }

        $otra = AtributoEquipo::where('categoria_id', $atributo->categoria_id)
            ->where('familia_id', $familia->id)
            ->whereKeyNot($atributo->id)
            ->first();
        if ($otra) {
            throw new \InvalidArgumentException("La categoría ya tiene «{$otra->nombre}» usando la familia «{$familia->nombre}».");
        }

        $camposFamilia = collect($familia->campos ?? [])->pluck('id');
        $subCampos     = collect($this->subCamposPropios($atributo))->pluck('id');

        foreach ($mapeo as $campoId => $regla) {
            if (! $camposFamilia->contains($campoId)) {
                throw new \InvalidArgumentException('El mapeo menciona un campo que no es de la familia.');
            }

            if (($regla['origen'] ?? null) === self::ORIGEN_SUB_CAMPO && ! $subCampos->contains($regla['sub_campo_id'] ?? null)) {
                throw new \InvalidArgumentException('El mapeo menciona un sub-campo que no es de la característica.');
            }
        }

        if ($camposFamilia->diff(array_keys($mapeo))->isNotEmpty()) {
            throw new \InvalidArgumentException('Indica de dónde sale el valor de cada campo de la familia.');
        }

        // Ningún dato actual se puede perder sin querer: cada sub-campo con
        // valores tiene que ir a algún campo de la familia.
        $usados    = collect($mapeo)->where('origen', self::ORIGEN_SUB_CAMPO)->pluck('sub_campo_id');
        $conDatos  = $this->subCamposConDatos($atributo);
        $sinDestino = collect($this->subCamposPropios($atributo))
            ->filter(fn ($sc) => $conDatos->contains($sc['id']) && ! $usados->contains($sc['id']));

        if ($sinDestino->isNotEmpty()) {
            throw new \InvalidArgumentException('Estos sub-campos tienen datos y no van a ningún campo de la familia: '
                . $sinDestino->pluck('nombre')->implode(', ') . '.');
        }
    }

    /** IDs de los sub-campos que tienen algún valor guardado (equipos o piezas). */
    private function subCamposConDatos(AtributoEquipo $atributo): Collection
    {
        return EquipoAtributoGrupoInstancia::where('atributo_id', $atributo->id)->pluck('valores')
            ->concat(PiezaExtraida::where('atributo_id', $atributo->id)->pluck('valor_extraido'))
            ->flatMap(fn ($v) => collect($v ?? [])->filter(fn ($x) => $x !== null && $x !== '')->keys())
            ->unique()
            ->values();
    }

    /** Valores que no están entre las opciones de un campo de lista de la familia. */
    private function valoresFueraDeLista(Collection $valores, FamiliaComponente $familia, array $mapeo): array
    {
        $listas = collect($familia->campos ?? [])->where('tipo', 'select')->keyBy('id');
        $fuera  = [];

        foreach ($valores as $valor) {
            foreach ($this->convertir($valor ?? [], $mapeo) as $campoId => $v) {
                if ($v === null || $v === '' || ! $listas->has($campoId)) {
                    continue;
                }

                if (! in_array($v, $listas[$campoId]['opciones'] ?? [], true)) {
                    $fuera[$listas[$campoId]['nombre']][] = $v;
                }
            }
        }

        return collect($fuera)->map(fn ($v) => array_values(array_unique($v)))->all();
    }

    private function describir(array $valores, array $campos): string
    {
        $nombres = collect($campos)->keyBy('id');

        return collect($valores)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $id) => ($nombres[$id]['nombre'] ?? '?') . ": {$v}")
            ->implode(' / ') ?: '(vacío)';
    }

    /**
     * Los componentes del almacén se llaman según la pieza ("RAM (GB)
     * (Cantidad: 4 / Tipo: DDR3)"). Tras convertir, se renombran con la
     * ficha de la familia — solo los que contienen únicamente piezas de
     * esta característica, y sin chocar con otro componente que ya exista.
     */
    private function renombrarComponentes(AtributoEquipo $atributo, Collection $piezas): int
    {
        $renombrados = 0;

        foreach ($piezas->pluck('componente_almacen_id')->filter()->unique() as $componenteId) {
            $componente = ComponenteAlmacen::withTrashed()->find($componenteId);
            if (! $componente) {
                continue;
            }

            $otrasPiezas = PiezaExtraida::where('componente_almacen_id', $componenteId)
                ->where(fn ($q) => $q->whereNull('atributo_id')->orWhere('atributo_id', '!=', $atributo->id))
                ->exists();
            if ($otrasPiezas) {
                continue;
            }

            $pieza = PiezaExtraida::where('componente_almacen_id', $componenteId)->first();
            $nuevo = $atributo->describirValor($pieza->valor_extraido ?? []);

            $choca = ComponenteAlmacen::withTrashed()
                ->where('empresa_id', $componente->empresa_id)
                ->where('nombre', $nuevo)
                ->whereKeyNot($componente->id)
                ->exists();

            if (! $choca && $componente->nombre !== $nuevo) {
                $componente->update(['nombre' => $nuevo]);
                $renombrados++;
            }
        }

        return $renombrados;
    }

    private function normalizar(string $texto): string
    {
        $texto = preg_replace('/\(.*?\)/', '', $texto);

        return trim(mb_strtolower(\Illuminate\Support\Str::ascii($texto)));
    }
}
