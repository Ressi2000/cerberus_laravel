<?php

namespace App\Livewire\Asistente;

use App\Asistente\Asistente;
use App\Asistente\AsistenteException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Ventana flotante de Cerberus Bot. La conversación vive en la sesión del
 * servidor (no en el navegador), así que no se puede manipular desde fuera y
 * se mantiene al cambiar de página.
 */
class ChatAsistente extends Component
{
    const SESION = 'cerberus_bot';

    /** Nombres amables de las consultas, para mostrar qué revisó el bot. */
    const ETIQUETAS = [
        'buscar_equipos'      => 'Buscó equipos',
        'ver_equipo'          => 'Ficha del equipo',
        'historial_equipo'    => 'Historial del equipo',
        'equipos_de_persona'  => 'Equipos de la persona',
        'buscar_piezas'       => 'Buscó piezas',
        'recorrido_pieza'     => 'Recorrido de la pieza',
        'stock_almacen'       => 'Stock del almacén',
        'casos_mantenimiento' => 'Casos de mantenimiento',
        'renovacion_equipos'  => 'Renovación',
        'resumen_inventario'  => 'Resumen del inventario',
    ];

    public bool $abierto = false;

    public string $pregunta = '';

    #[Locked]
    public bool $pensando = false;

    public function mount(): void
    {
        abort_unless(Asistente::disponiblePara(Auth::user()), 403);
    }

    public function enviar(): void
    {
        $pregunta = trim($this->pregunta);
        if ($pregunta === '' || $this->pensando) {
            return;
        }
        if (mb_strlen($pregunta) > 1000) {
            $this->addError('pregunta', 'La pregunta es muy larga (máximo 1000 caracteres).');
            return;
        }

        $this->resetErrorBag();
        $this->agregarMensaje(['rol' => 'usuario', 'texto' => $pregunta]);
        session()->put(self::SESION . '.pendiente', $pregunta);
        $this->pregunta = '';
        $this->pensando = true;

        // Se responde en una segunda petición para que la pregunta aparezca ya.
        $this->js('$wire.responder()');
    }

    public function responder(): void
    {
        $pregunta = session()->pull(self::SESION . '.pendiente');
        $this->pensando = false;

        if (! $pregunta) {
            return;
        }

        try {
            $respuesta = app(Asistente::class)->preguntar(Auth::user(), $pregunta, session(self::SESION . '.historial', []));

            session()->put(self::SESION . '.historial', $respuesta->historial);
            $this->agregarMensaje([
                'rol'       => 'bot',
                'texto'     => $respuesta->texto,
                'consultas' => collect($respuesta->herramientas)->pluck('nombre')->unique()
                    ->map(fn ($n) => self::ETIQUETAS[$n] ?? $n)->values()->all(),
            ]);
        } catch (AsistenteException $e) {
            $this->agregarMensaje(['rol' => 'error', 'texto' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Cerberus Bot falló', ['error' => $e->getMessage()]);
            $this->agregarMensaje(['rol' => 'error', 'texto' => 'Ocurrió un error inesperado. Intenta de nuevo.']);
        }

        $this->dispatch('cerberus-bot-respondio');
    }

    public function nuevaConversacion(): void
    {
        session()->forget(self::SESION);
        $this->pensando = false;
        $this->pregunta = '';
    }

    public function mensajes(): array
    {
        return session(self::SESION . '.mensajes', []);
    }

    private function agregarMensaje(array $mensaje): void
    {
        $mensajes = $this->mensajes();
        $mensajes[] = $mensaje + ['hora' => now()->format('H:i')];
        session()->put(self::SESION . '.mensajes', array_slice($mensajes, -60));
    }

    public function render()
    {
        return view('livewire.asistente.chat-asistente', [
            'mensajes' => $this->mensajes(),
            'nombre'   => config('asistente.nombre'),
            'avatar'   => asset('images/asistente/cerberus-bot.jpg'),
            'animado'  => file_exists(public_path('images/asistente/cerberus-bot.gif')) ? asset('images/asistente/cerberus-bot.gif') : null,
        ]);
    }
}
