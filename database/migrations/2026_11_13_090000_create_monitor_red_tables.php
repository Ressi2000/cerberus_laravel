<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monitor de red — hosts monitoreados por ping (con respaldo TCP), su
 * historial de caídas, las mediciones de latencia para las gráficas y los
 * tests de velocidad de internet.
 *
 * Las columnas de "último estado" de monitor_hosts (estado, latencia_ms,
 * fallos_consecutivos, ...) las escribe solo el MonitorRedService; el
 * formulario del módulo nunca las toca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_hosts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            // Vínculo opcional con el inventario: si el equipo se elimina, el host se conserva.
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();

            $table->string('nombre', 100);
            $table->string('host', 255);                       // IP v4/v6 o nombre DNS
            $table->string('tipo', 40)->default('Servicio');   // Router, Switch, Servidor...
            $table->string('proveedor', 60)->nullable();       // ISP / enlace
            $table->unsignedSmallInteger('puerto_tcp')->nullable(); // respaldo si el ping está bloqueado
            $table->boolean('pausado')->default(false);        // monitoreo suspendido (mantenimiento)
            $table->text('observaciones')->nullable();

            // ── Último estado conocido (lo escribe el servicio de monitoreo) ──
            $table->enum('estado', ['Pendiente', 'Activo', 'Caido'])->default('Pendiente');
            $table->timestamp('estado_desde')->nullable();
            $table->decimal('latencia_ms', 8, 1)->nullable();
            $table->string('metodo', 20)->nullable();          // ping | TCP:443 | ping/TCP
            $table->unsignedSmallInteger('fallos_consecutivos')->default(0);
            $table->timestamp('primer_fallo_en')->nullable();
            $table->timestamp('ultima_revision')->nullable();

            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'pausado']);
        });

        Schema::create('monitor_caidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_host_id')->constrained('monitor_hosts')->cascadeOnDelete();
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();              // null = caída en curso
            $table->unsignedInteger('duracion_segundos')->nullable();

            $table->index(['monitor_host_id', 'inicio']);
            $table->index(['monitor_host_id', 'fin']);
            $table->index('inicio');
        });

        Schema::create('monitor_mediciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_host_id')->constrained('monitor_hosts')->cascadeOnDelete();
            $table->timestamp('medido_en');
            $table->boolean('exito');
            $table->decimal('latencia_ms', 8, 1)->nullable();

            $table->index(['monitor_host_id', 'medido_en']);
            $table->index('medido_en');
        });

        Schema::create('monitor_pruebas_velocidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->decimal('bajada_mbps', 10, 2)->nullable();
            $table->decimal('subida_mbps', 10, 2)->nullable();
            $table->decimal('ping_ms', 10, 2)->nullable();
            $table->decimal('jitter_ms', 10, 2)->nullable();
            $table->string('isp', 120)->nullable();
            $table->string('asn', 20)->nullable();
            $table->string('ip_publica', 45)->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->string('pais', 80)->nullable();
            $table->timestamps();

            $table->index('empresa_id');
            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_pruebas_velocidad');
        Schema::dropIfExists('monitor_mediciones');
        Schema::dropIfExists('monitor_caidas');
        Schema::dropIfExists('monitor_hosts');
    }
};
