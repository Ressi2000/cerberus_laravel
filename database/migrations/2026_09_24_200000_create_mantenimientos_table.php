<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();

            // Asignación activa del equipo al momento del hallazgo/reporte — de ahí sale
            // automáticamente quién lo tenía, sin que el Analista tenga que buscarla.
            $table->foreignId('asignacion_id')->nullable()->constrained('asignaciones')->nullOnDelete();

            // Estado anterior del equipo, para restaurarlo al cerrar el caso (si seguía
            // asignado, vuelve a "Asignado" en vez de forzarlo a "Disponible").
            $table->foreignId('estado_equipo_anterior_id')->nullable()->constrained('estados_equipos')->nullOnDelete();

            $table->enum('tipo', ['Preventivo', 'Correctivo']);

            // Unión de los dos flujos (Preventivo y Correctivo comparten esta sola columna):
            //   Preventivo: Programado -> En proceso -> Completado | Cancelado
            //   Correctivo: Reportado -> Diagnosticado -> En reparación -> Reparado -> Cerrado
            //               (o Reparado se reemplaza por Dado de baja, que ya es terminal)
            $table->enum('estado', [
                'Programado', 'En proceso', 'Completado', 'Cancelado',
                'Reportado', 'Diagnosticado', 'En reparación', 'Reparado', 'Cerrado', 'Dado de baja',
            ]);

            // Bandera informativa (no es parte del flujo oficial): hay al menos un
            // componente pendiente de stock en mantenimiento_componentes.
            $table->boolean('esperando_componente')->default(false);

            $table->foreignId('reportado_por_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('proveedor_externo')->nullable();

            $table->date('fecha_inicio');
            $table->date('fecha_fin_estimada')->nullable();
            $table->date('fecha_fin_real')->nullable();

            $table->decimal('costo', 10, 2)->nullable();
            $table->foreignId('ubicacion_taller_id')->nullable()->constrained('ubicaciones')->nullOnDelete();

            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();

            // ── Campos específicos de Mantenimiento (Preventivo) ─────────────────
            $table->unsignedSmallInteger('frecuencia_meses')->nullable();
            $table->date('proxima_fecha_programada')->nullable();
            $table->json('checklist')->nullable();

            // ── Campos específicos de Reparación (Correctivo) ────────────────────
            $table->text('falla_reportada')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('causa_raiz')->nullable();
            $table->boolean('en_garantia')->default(false);
            $table->foreignId('mantenimiento_origen_id')->nullable()->constrained('mantenimientos')->nullOnDelete();
            $table->text('motivo_baja')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->foreignId('aprobado_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index('equipo_id');
            $table->index(['empresa_id', 'estado']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
