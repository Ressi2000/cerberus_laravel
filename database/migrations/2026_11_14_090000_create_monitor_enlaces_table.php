<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaces de internet de cada localidad (empresa): un registro por proveedor
 * o conexión, con su puerta de enlace, IP pública y plan contratado.
 *
 * Los hosts monitoreados y los tests de velocidad pueden apuntar a un enlace,
 * así se puede ver disponibilidad y velocidad POR PROVEEDOR de cada localidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_enlaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();

            $table->string('nombre', 100);                     // "Fibra principal", "Respaldo 4G"
            $table->string('proveedor', 80);                   // ISP
            $table->string('tipo_conexion', 30)->default('Fibra');
            $table->string('gateway', 45)->nullable();         // puerta de enlace en la LAN de la localidad
            $table->string('ip_publica', 45)->nullable();      // IP WAN (si es fija)
            $table->string('asn', 20)->nullable();             // sistema autónomo del ISP (ej. 8048)
            $table->decimal('bajada_contratada_mbps', 10, 2)->nullable();
            $table->decimal('subida_contratada_mbps', 10, 2)->nullable();
            $table->boolean('principal')->default(false);
            $table->boolean('con_cuota')->default(false);      // 4G/LTE con límite de datos: no saturar con tests
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();

            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('empresa_id');
            $table->index(['empresa_id', 'activo']);
        });

        Schema::table('monitor_hosts', function (Blueprint $table) {
            $table->foreignId('enlace_id')->nullable()->after('equipo_id')
                ->constrained('monitor_enlaces')->nullOnDelete();
        });

        Schema::table('monitor_pruebas_velocidad', function (Blueprint $table) {
            $table->foreignId('enlace_id')->nullable()->after('usuario_id')
                ->constrained('monitor_enlaces')->nullOnDelete();
            // Cómo se asignó el enlace: 'ip' / 'asn' (detectado) o 'manual' (elegido por quien midió)
            $table->string('enlace_asignado_por', 10)->nullable()->after('enlace_id');
        });
    }

    public function down(): void
    {
        Schema::table('monitor_pruebas_velocidad', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enlace_id');
            $table->dropColumn('enlace_asignado_por');
        });

        Schema::table('monitor_hosts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enlace_id');
        });

        Schema::dropIfExists('monitor_enlaces');
    }
};
