<?php

namespace App\Livewire\Configuracion\Familias;

use App\Models\EquipoAtributoGrupoInstancia;
use App\Models\FamiliaComponente;
use App\Models\PiezaExtraida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Crear o editar una familia de piezas y su ficha técnica.
 *
 * Cada campo conserva su id (UUID) aunque se renombre: es la clave con la
 * que se guardan los valores en los equipos y en las piezas. Por eso un
 * campo que ya tiene valores no se puede quitar desde aquí.
 */
class FamiliaModal extends Component
{
    public bool $open = false;

    #[Locked]
    public ?int $familiaId = null;

    public string $nombre      = '';
    public string $descripcion = '';
    public string $control     = FamiliaComponente::CONTROL_UNIDAD;

    /** @var array<int, array{id:string, nombre:string, tipo:string, opciones_raw:string, requerido:bool}> */
    public array $campos = [];

    #[On('openFamiliaCrear')]
    public function abrirCrear(): void
    {
        $this->autorizar();
        $this->reset(['familiaId', 'nombre', 'descripcion', 'campos']);
        $this->control = FamiliaComponente::CONTROL_UNIDAD;
        $this->agregarCampo();
        $this->resetValidation();
        $this->open = true;
    }

    #[On('openFamiliaEditar')]
    public function abrirEditar(int $id): void
    {
        $this->autorizar();

        $familia = FamiliaComponente::findOrFail($id);

        $this->familiaId   = $familia->id;
        $this->nombre      = $familia->nombre;
        $this->descripcion = $familia->descripcion ?? '';
        $this->control     = $familia->control;
        $this->campos      = collect($familia->campos ?? [])->map(fn ($c) => [
            'id'             => $c['id'],
            'nombre'         => $c['nombre'],
            'tipo'           => $c['tipo'] ?? 'string',
            'opciones_raw'   => implode("\n", $c['opciones'] ?? []),
            'requerido'      => (bool) ($c['requerido'] ?? false),
        ])->values()->all();

        $this->resetValidation();
        $this->open = true;
    }

    public function agregarCampo(): void
    {
        $this->campos[] = [
            'id'             => (string) Str::uuid(),
            'nombre'         => '',
            'tipo'           => 'string',
            'opciones_raw'   => '',
            'requerido'      => false,
        ];
    }

    public function quitarCampo(int $index): void
    {
        $campo = $this->campos[$index] ?? null;

        if (! $campo) {
            return;
        }

        if ($this->familiaId && $this->campoTieneValores($campo['id'])) {
            $this->addError("campos.{$index}.nombre", "«{$campo['nombre']}» ya tiene valores en equipos o piezas: no se puede quitar.");
            return;
        }

        unset($this->campos[$index]);
        $this->campos = array_values($this->campos);
    }

    public function mover(int $index, int $direccion): void
    {
        $destino = $index + $direccion;

        if (! isset($this->campos[$index], $this->campos[$destino])) {
            return;
        }

        [$this->campos[$index], $this->campos[$destino]] = [$this->campos[$destino], $this->campos[$index]];
    }

    protected function rules(): array
    {
        $rules = [
            'nombre'         => ['required', 'string', 'max:100', Rule::unique('familias_componentes', 'nombre')->ignore($this->familiaId)],
            'descripcion'    => 'nullable|string|max:500',
            'control'        => 'required|in:' . implode(',', array_keys(FamiliaComponente::CONTROLES)),
            'campos'         => 'required|array|min:1',
            'campos.*.nombre'=> 'required|string|max:100|distinct',
            'campos.*.tipo'  => 'required|in:' . implode(',', array_keys(FamiliaComponente::TIPOS_CAMPO)),
        ];

        foreach ($this->campos as $i => $campo) {
            if (($campo['tipo'] ?? '') === 'select') {
                $rules["campos.{$i}.opciones_raw"] = 'required|string';
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'nombre.required'                 => 'Ponle un nombre a la familia (ej. RAM).',
            'nombre.unique'                   => 'Ya existe una familia con ese nombre.',
            'campos.required'                 => 'La ficha necesita al menos un campo.',
            'campos.min'                      => 'La ficha necesita al menos un campo.',
            'campos.*.nombre.required'        => 'Cada campo necesita un nombre.',
            'campos.*.nombre.distinct'        => 'Hay dos campos con el mismo nombre.',
            'campos.*.opciones_raw.required'  => 'Una lista necesita al menos una opción (una por línea).',
        ];
    }

    public function guardar(): void
    {
        $this->autorizar();
        $this->validate();

        $campos = collect($this->campos)->map(fn ($c) => [
            'id'             => $c['id'],
            'nombre'         => trim($c['nombre']),
            'tipo'           => $c['tipo'],
            'opciones'       => $c['tipo'] === 'select'
                ? array_values(array_filter(array_map('trim', explode("\n", $c['opciones_raw'] ?? ''))))
                : [],
            'requerido'      => (bool) $c['requerido'],
        ])->values()->all();

        try {
            if ($this->familiaId) {
                $familia = FamiliaComponente::findOrFail($this->familiaId);

                // Un campo con valores no puede desaparecer al guardar (por si
                // se quitó de otra forma que no sea el botón).
                $quitados = collect($familia->campos ?? [])->pluck('id')->diff(collect($campos)->pluck('id'));
                foreach ($quitados as $id) {
                    if ($this->campoTieneValores($id)) {
                        $this->addError('campos', 'Un campo que ya tiene valores no se puede quitar.');
                        return;
                    }
                }

                $familia->update([
                    'nombre'      => trim($this->nombre),
                    'descripcion' => trim($this->descripcion) ?: null,
                    'control'     => $this->control,
                    'campos'      => $campos,
                ]);
                $msg = "Familia «{$familia->nombre}» actualizada.";
            } else {
                FamiliaComponente::create([
                    'nombre'      => trim($this->nombre),
                    'descripcion' => trim($this->descripcion) ?: null,
                    'control'     => $this->control,
                    'campos'      => $campos,
                    'activo'      => true,
                    'creado_por'  => Auth::id(),
                ]);
                $msg = 'Familia «' . trim($this->nombre) . '» creada.';
            }

            $this->close();
            $this->dispatch('familiaGuardada');
            $this->dispatch('toast', type: 'success', message: $msg);
        } catch (\Throwable $e) {
            Log::error('FamiliaModal@guardar: ' . $e->getMessage());
            $this->addError('campos', 'Ocurrió un error al guardar la familia.');
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->reset(['familiaId', 'nombre', 'descripcion', 'campos']);
        $this->control = FamiliaComponente::CONTROL_UNIDAD;
        $this->resetValidation();
    }

    /** ¿Algún equipo o pieza de esta familia tiene un valor en ese campo? */
    private function campoTieneValores(string $campoId): bool
    {
        $familia = FamiliaComponente::find($this->familiaId);
        $atributos = $familia?->atributos()->pluck('id') ?? collect();

        if ($atributos->isEmpty()) {
            return false;
        }

        $conValor = fn ($v) => isset($v[$campoId]) && $v[$campoId] !== '' && $v[$campoId] !== null;

        return EquipoAtributoGrupoInstancia::whereIn('atributo_id', $atributos)->pluck('valores')->contains($conValor)
            || PiezaExtraida::whereIn('atributo_id', $atributos)->pluck('valor_extraido')->contains($conValor);
    }

    private function autorizar(): void
    {
        abort_unless(Auth::user()?->hasRole('Administrador'), 403);
    }

    public function render()
    {
        return view('livewire.configuracion.familias.familia-modal', [
            'tipos'     => FamiliaComponente::TIPOS_CAMPO,
            'controles' => FamiliaComponente::CONTROLES,
        ]);
    }
}
