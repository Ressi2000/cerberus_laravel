<?php

namespace App\Services;

class CodigoInternoService
{
    /**
     * Genera el código interno a partir del ID ya asignado por la BD.
     * Formato: EQ-00001
     * Global, único, correlativo, sin colisiones posibles.
     */
    public function generar(int $id): string
    {
        return 'EQ-' . str_pad($id, 5, '0', STR_PAD_LEFT);
    }
}