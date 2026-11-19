<?php

namespace App\Asistente;

/** Lo que devuelve la IA a una pregunta. */
class Respuesta
{
    public function __construct(
        public readonly string $texto,
        /** @var array<int, array{nombre: string, args: array, ok: bool}> */
        public readonly array $herramientas,
        public readonly bool $sinRespuesta,
        /** Conversación completa en el formato del proveedor, para la próxima pregunta. */
        public readonly array $historial,
        public readonly ?string $modelo = null,
    ) {
    }
}
