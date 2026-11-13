<?php

namespace App\Console\Commands;

use App\Models\ComponenteAlmacen;
use App\Models\Empresa;
use App\Models\FamiliaComponente;
use App\Models\MantenimientoComponente;
use App\Models\MovimientoComponente;
use App\Models\PiezaExtraida;
use App\Models\PiezaExtraidaMovimiento;
use App\Models\User;
use App\Services\ExtraccionPiezaService;
use App\Services\PiezaManualService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Carga en el almacén de una empresa algunas piezas de ejemplo, para ver
 * cómo se organiza por familias: RAM y Discos (con ficha), pantallas y
 * cargadores (por unidad, con serial) y bisagras, tornillos y pasta térmica
 * (a granel).
 *
 * - Todo entra como NUEVO y por las mismas vías que "Registrar entrada".
 * - Los seriales empiezan con EJEMPLO- y todo lleva la observación
 *   «Dato de ejemplo», para reconocerlo.
 * - RAM y Discos solo se cargan si su familia ya está vinculada a alguna
 *   característica de equipo (si no, sus piezas no se podrían instalar).
 * - Las familias que falten (Pantallas, Cargadores, Bisagras…) se crean.
 *
 * --quitar borra lo de ejemplo que nadie haya usado: si una pieza de ejemplo
 * ya se instaló en un caso, esa variante se deja (es historia de un equipo).
 *
 * Sin --ejecutar solo SIMULA (hace todo en una transacción y la revierte).
 */
class EjemplosAlmacen extends Command
{
    protected $signature = 'cerberus:almacen-ejemplos
                            {--empresa= : ID de la empresa donde se cargan los ejemplos}
                            {--usuario= : ID del Administrador que queda como quien los registró}
                            {--quitar : Borra los ejemplos que nadie haya usado (en vez de cargarlos)}
                            {--ejecutar : Aplica los cambios. Sin esta opción solo simula}';

    protected $description = 'Carga (o quita con --quitar) piezas de ejemplo en el almacén de una empresa. Simula por defecto.';

    const MARCA = 'Dato de ejemplo';
    const PREFIJO = 'EJEMPLO-';
    const FAMILIA_DE_EJEMPLO = 'Creada con los datos de ejemplo del almacén.';

    /** Piezas con ficha: familia → [valores por nombre de campo, cantidad, código]. */
    const CON_FICHA = [
        'RAM' => [
            [['Capacidad (GB)' => '8', 'Tipo' => 'DDR4', 'Formato' => 'SO-DIMM (laptop)'], 3, 'RAM8'],
            [['Capacidad (GB)' => '4', 'Tipo' => 'DDR3L', 'Formato' => 'SO-DIMM (laptop)'], 2, 'RAM4'],
            [['Capacidad (GB)' => '16', 'Tipo' => 'DDR4', 'Formato' => 'DIMM (escritorio)'], 2, 'RAM16'],
        ],
        'Discos' => [
            [['Capacidad (GB)' => '256', 'Tipo' => 'SSD 2.5" SATA'], 3, 'SSD256'],
            [['Capacidad (GB)' => '512', 'Tipo' => 'M.2 NVMe'], 2, 'NVME512'],
            [['Capacidad (GB)' => '1000', 'Tipo' => 'HDD 3.5"'], 1, 'HDD1000'],
        ],
    ];

    /** Piezas sin ficha: familia → [control, [nombre de la variante, cantidad, código]]. */
    const SIN_FICHA = [
        'Pantallas'     => ['unidad', [['Pantalla 14" HD 30 pines', 2, 'PAN14'], ['Pantalla 15.6" FHD 30 pines', 1, 'PAN156']]],
        'Cargadores'    => ['unidad', [['Cargador HP 65W punta azul', 3, 'CARHP']]],
        'Bisagras'      => ['granel', [['Bisagras HP 240 G7 (par)', 6, null]]],
        'Tornillos'     => ['granel', [['Tornillos M2 x 3 mm', 100, null]]],
        'Pasta térmica' => ['granel', [['Pasta térmica (jeringa 4 g)', 5, null]]],
    ];

    public function handle(): int
    {
        $usuario = User::find($this->option('usuario'));
        $empresa = Empresa::find($this->option('empresa'));

        if (! $usuario || ! $usuario->hasRole('Administrador')) {
            $this->error('Indica con --usuario=ID el Administrador que registra los ejemplos.');

            return self::FAILURE;
        }

        if (! $empresa) {
            $this->error('Indica con --empresa=ID la empresa donde van los ejemplos.');

            return self::FAILURE;
        }

        $ejecutar = (bool) $this->option('ejecutar');
        $conexion = DB::connection();

        $this->line('Base de datos: <info>' . $conexion->getDatabaseName() . '</info> en <info>' . ($conexion->getConfig('host') ?? '—') . '</info>.');
        $this->line("Empresa: <info>{$empresa->nombre}</info>.");
        $this->warn($ejecutar ? 'EJECUTANDO: los cambios son permanentes.' : 'SIMULACIÓN: no se va a guardar nada. Para aplicar, agrega --ejecutar.');
        $this->newLine();

        DB::beginTransaction();

        try {
            $ok = $this->option('quitar') ? $this->quitar($empresa) : $this->cargar($empresa, $usuario);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Se detuvo sin cambiar nada: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $ok || ! $ejecutar) {
            DB::rollBack();
            if ($ok) {
                $this->newLine();
                $this->info('Simulación terminada: no se guardó nada. Si todo se ve bien, repite con --ejecutar.');
            }

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        DB::commit();
        $this->newLine();
        $this->info('Listo.');

        return self::SUCCESS;
    }

    // ─── Cargar ────────────────────────────────────────────────────────────

    private function cargar(Empresa $empresa, User $usuario): bool
    {
        $ya = PiezaExtraida::where('empresa_id', $empresa->id)->where('identificador', 'like', self::PREFIJO . '%')->exists()
            || MovimientoComponente::whereHas('componente', fn ($q) => $q->where('empresa_id', $empresa->id))->where('observaciones', self::MARCA)->exists();

        if ($ya) {
            $this->error('Esta empresa ya tiene ejemplos cargados. Para volver a cargarlos, quítalos primero con --quitar.');

            return false;
        }

        $filas = [];

        foreach (self::CON_FICHA as $nombreFamilia => $variantes) {
            $familia  = FamiliaComponente::where('nombre', $nombreFamilia)->first();
            $atributo = $familia?->atributoRepresentativo();

            if (! $familia || ! $atributo) {
                $this->warn("   ! {$nombreFamilia}: se salta. " . ($familia
                    ? 'Vincula primero su familia a una característica de equipo (Configuración → Atributos → «Vincular a una familia»).'
                    : 'No existe la familia: corre el seeder FamiliasComponentesSeeder.'));
                continue;
            }

            foreach ($variantes as [$porNombre, $cantidad, $codigo]) {
                // Solo los campos que la familia tenga hoy (sus nombres pueden cambiar).
                $valores = collect($familia->campos)->mapWithKeys(fn ($c) => [$c['id'] => $porNombre[$c['nombre']] ?? null])->all();
                if (collect($valores)->filter()->isEmpty()) {
                    $this->warn("   ! {$nombreFamilia}: su ficha cambió y ya no tiene los campos del ejemplo; se salta.");
                    continue 2;
                }

                $componente = app(ExtraccionPiezaService::class)->varianteDeFicha($empresa->id, $atributo, $valores, $usuario);
                app(PiezaManualService::class)->registrar(
                    $componente, $cantidad, PiezaExtraida::CONDICION_NUEVO, $usuario,
                    observaciones: self::MARCA,
                    seriales: $this->seriales($codigo, $cantidad),
                    atributo: $atributo,
                    valorFicha: $valores,
                );
                $filas[] = [$nombreFamilia, $componente->nombre, $cantidad, 'Por unidad, con serial'];
            }
        }

        foreach (self::SIN_FICHA as $nombreFamilia => [$control, $variantes]) {
            $familia = FamiliaComponente::firstOrCreate(
                ['nombre' => $nombreFamilia],
                ['control' => $control, 'campos' => [], 'activo' => true, 'creado_por' => $usuario->id, 'descripcion' => self::FAMILIA_DE_EJEMPLO],
            );
            if ($familia->wasRecentlyCreated) {
                $this->line("   + Familia nueva: {$nombreFamilia} (" . ($control === 'granel' ? 'a granel' : 'por unidad') . ').');
            }

            foreach ($variantes as [$nombre, $cantidad, $codigo]) {
                $componente = ComponenteAlmacen::firstOrCreate(
                    ['empresa_id' => $empresa->id, 'nombre' => $nombre],
                    ['familia_id' => $familia->id, 'unidad' => 'unidad', 'stock_actual' => 0, 'activo' => true, 'creado_por' => $usuario->id],
                );

                if ($familia->esAGranel()) {
                    $componente->registrarEntrada($cantidad, $usuario, 'Entrada de stock', self::MARCA);
                } else {
                    app(PiezaManualService::class)->registrar(
                        $componente, $cantidad, PiezaExtraida::CONDICION_NUEVO, $usuario,
                        observaciones: self::MARCA,
                        seriales: $this->seriales($codigo, $cantidad),
                    );
                }
                $filas[] = [$nombreFamilia, $nombre, $cantidad, $familia->esAGranel() ? 'A granel' : 'Por unidad, con serial'];
            }
        }

        $this->table(['Familia', 'Variante', 'Cantidad', 'Control'], $filas);

        return true;
    }

    /** EJEMPLO-RAM8-001, EJEMPLO-RAM8-002… */
    private function seriales(?string $codigo, int $cantidad): array
    {
        return $codigo
            ? array_map(fn ($i) => self::PREFIJO . $codigo . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), range(1, $cantidad))
            : [];
    }

    // ─── Quitar ────────────────────────────────────────────────────────────

    private function quitar(Empresa $empresa): bool
    {
        $componentes = ComponenteAlmacen::where('empresa_id', $empresa->id)
            ->where(fn ($q) => $q
                ->whereHas('movimientos', fn ($m) => $m->where('observaciones', self::MARCA))
                ->orWhereHas('piezasExtraidas', fn ($p) => $p->where('identificador', 'like', self::PREFIJO . '%')))
            ->get();

        if ($componentes->isEmpty()) {
            $this->info('Esta empresa no tiene ejemplos cargados.');

            return true;
        }

        $borrados = [];
        $dejados  = [];

        foreach ($componentes as $componente) {
            // Se borra solo si TODO lo de la variante es de ejemplo y sigue en el almacén.
            $otrosMovimientos = $componente->movimientos()->where(fn ($q) => $q->whereNull('observaciones')->orWhere('observaciones', '!=', self::MARCA))->exists();
            $piezasAjenas     = $componente->piezasExtraidas()->where(fn ($q) => $q
                ->where('identificador', 'not like', self::PREFIJO . '%')
                ->orWhere('estado', '!=', PiezaExtraida::ESTADO_EN_ALMACEN))->exists();
            $usadaEnCasos     = MantenimientoComponente::where('componente_id', $componente->id)->exists();

            if ($otrosMovimientos || $piezasAjenas || $usadaEnCasos) {
                $dejados[] = [$componente->nombre, 'Ya tiene movimientos reales o se usó en un caso'];
                continue;
            }

            $piezas = $componente->piezasExtraidas()->pluck('id');
            PiezaExtraidaMovimiento::whereIn('pieza_id', $piezas)->delete();
            PiezaExtraida::whereIn('id', $piezas)->delete();
            $componente->movimientos()->delete();
            $componente->forceDelete();

            $borrados[] = [$componente->nombre, $piezas->count() ? "{$piezas->count()} pieza(s)" : "{$componente->stock_actual} a granel"];
        }

        if ($borrados) {
            $this->info('Se borran:');
            $this->table(['Variante', 'Qué tenía'], $borrados);
        }

        if ($dejados) {
            $this->warn('Se dejan (no se pueden borrar sin perder historia):');
            $this->table(['Variante', 'Por qué'], $dejados);
        }

        // Familias que creó este comando y quedaron vacías (las que ya existían se dejan).
        $vacias = FamiliaComponente::where('descripcion', self::FAMILIA_DE_EJEMPLO)
            ->whereDoesntHave('componentes', fn ($q) => $q->withTrashed())
            ->whereDoesntHave('atributos')
            ->get();
        foreach ($vacias as $familia) {
            $familia->forceDelete(); // si no, su nombre quedaría ocupado
            $this->line("   - Familia vacía borrada: {$familia->nombre}.");
        }

        return true;
    }
}
