<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AsignacionItem query()
 */
	class AsignacionItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $categoria_id
 * @property string $nombre
 * @property string $slug
 * @property string $tipo
 * @property bool $requerido
 * @property bool $filtrable
 * @property bool $visible_en_tabla
 * @property int $orden
 * @property array<array-key, mixed>|null $opciones Opciones disponibles para atributos tipo select. JSON array o key-value.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CategoriaEquipo $categoria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EquipoAtributoValor> $valores
 * @property-read int|null $valores_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EquipoAtributoValor> $valoresActuales
 * @property-read int|null $valores_actuales_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereFiltrable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereOpciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereRequerido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AtributoEquipo whereVisibleEnTabla($value)
 */
	class AtributoEquipo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $usuario_id
 * @property string $tabla
 * @property int|null $registro_id
 * @property string $accion
 * @property string|null $valores_previos
 * @property string|null $valores_nuevos
 * @property string $created_at
 * @property-read array $cambios
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria visiblePara(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereAccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereRegistroId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereTabla($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereUsuarioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereValoresNuevos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Auditoria whereValoresPrevios($value)
 */
	class Auditoria extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property int|null $empresa_id
 * @property int|null $departamento_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Departamento|null $departamento
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereDepartamentoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereUpdatedAt($value)
 */
	class Cargo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property int $asignable
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AtributoEquipo> $atributos
 * @property-read int|null $atributos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipo> $equipos
 * @property-read int|null $equipos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo whereAsignable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CategoriaEquipo whereUpdatedAt($value)
 */
	class CategoriaEquipo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property int|null $empresa_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Departamento whereUpdatedAt($value)
 */
	class Departamento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $rif
 * @property string|null $direccion
 * @property string $telefono
 * @property int $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereRif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereUpdatedAt($value)
 */
	class Empresa extends \Eloquent {}
}

namespace App\Models{
/**
 * Modelo Equipo
 *
 * Representa un activo tecnológico del inventario.
 * Usa arquitectura EAV para atributos técnicos variables por categoría.
 * Tiene SoftDeletes para eliminación administrativa (solo Administrador).
 * El campo 'activo' controla la baja lógica (Analista).
 *
 * La clave es que empresa_id en el equipo es la empresa propietaria (nómina del activo),
 * pero ubicacion_id es donde está físicamente, y es ese campo el que determina la
 * visibilidad del analista — exactamente igual al principio rector de Cerberus para usuarios.
 *
 * @property int $id
 * @property int $empresa_id
 * @property int $categoria_id
 * @property int $estado_id
 * @property int|null $ubicacion_id
 * @property string $codigo_interno
 * @property string|null $serial
 * @property string|null $nombre_maquina
 * @property string|null $fecha_adquisicion
 * @property string|null $fecha_garantia_fin
 * @property int $activo
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property int|null $creado_por
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EquipoAtributoValor> $atributosActuales
 * @property-read int|null $atributos_actuales_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EquipoAtributoValor> $atributosHistorico
 * @property-read int|null $atributos_historico_count
 * @property-read \App\Models\CategoriaEquipo $categoria
 * @property-read \App\Models\User|null $creadoPor
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\EstadoEquipo $estado
 * @property-read \App\Models\Ubicacion|null $ubicacion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo visiblePara(\App\Models\User $actor)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCategoriaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCodigoInterno($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCreadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereFechaAdquisicion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereFechaGarantiaFin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereNombreMaquina($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereSerial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereUbicacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Equipo withoutTrashed()
 */
	class Equipo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $equipo_id
 * @property int $atributo_id
 * @property string $valor
 * @property int $es_actual
 * @property int|null $creado_por
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AtributoEquipo $atributo
 * @property-read \App\Models\Equipo|null $equipo
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereAtributoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereCreadoPor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereEquipoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereEsActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EquipoAtributoValor whereValor($value)
 */
	class EquipoAtributoValor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Equipo> $equipos
 * @property-read int|null $equipos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoEquipo whereUpdatedAt($value)
 */
	class EstadoEquipo extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mantenimiento query()
 */
	class Mantenimiento extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestamo query()
 */
	class Prestamo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property int|null $empresa_id
 * @property bool $es_estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereEsEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ubicacion whereUpdatedAt($value)
 */
	class Ubicacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $empresa_id
 * @property int|null $empresa_activa_id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string|null $ficha
 * @property string $cedula
 * @property int|null $departamento_id
 * @property int|null $cargo_id
 * @property int|null $ubicacion_id
 * @property string|null $telefono
 * @property int|null $jefe_id
 * @property string|null $foto
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Cargo|null $cargo
 * @property-read \App\Models\Departamento|null $departamento
 * @property-read \App\Models\Empresa|null $empresaActiva
 * @property-read \App\Models\Empresa|null $empresaNomina
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Empresa> $empresasAsignadas
 * @property-read int|null $empresas_asignadas_count
 * @property-read mixed $foto_url
 * @property-read User|null $jefe
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $subordinados
 * @property-read int|null $subordinados_count
 * @property-read \App\Models\Ubicacion|null $ubicacion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User activos()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User seleccionables()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User visiblePara(\App\Models\User $actor)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCargoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCedula($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartamentoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmpresaActivaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmpresaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEstado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFicha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereJefeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUbicacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

