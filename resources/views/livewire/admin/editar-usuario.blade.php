<div class="space-y-6 max-w-5xl mx-auto">

    {{-- HEADER --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-icons text-[#1E40AF] dark:text-cerberus-accent text-2xl">manage_accounts</span>
                <div>
                    <h2 class="text-xl font-bold text-[#1E293B] dark:text-white">Editar usuario</h2>
                    <p class="text-sm text-gray-500 dark:text-cerberus-light">{{ $usuario->username }} · {{ $usuario->empresaNomina->nombre ?? '—' }}</p>
                </div>
            </div>
            <span class="px-3 py-1 text-xs rounded-full font-medium
                {{ $usuario->estado === 'Activo'
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                    : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' }}">
                {{ $usuario->estado }}
            </span>
        </div>
    </div>

    @error('general')
        <div class="rounded-lg border border-red-400 bg-red-50 dark:bg-red-950/40 p-4 text-red-700 dark:text-red-200 flex items-center gap-2">
            <span class="material-icons">error</span> {{ $message }}
        </div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- FOTO --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Foto de perfil</h3>
                <div class="flex flex-col items-center gap-4">
                    <img src="{{ $this->fotoPreviewUrl }}"
                        class="w-28 h-28 rounded-full object-cover border-2 border-[#1E40AF] dark:border-cerberus-primary">

                    <label class="cursor-pointer flex items-center gap-2 px-4 py-2 rounded-lg text-sm
                                  bg-gray-100 dark:bg-cerberus-dark border border-gray-300 dark:border-cerberus-steel
                                  text-gray-700 dark:text-cerberus-light hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        <span class="material-icons text-base">upload</span>
                        Cambiar foto
                        <input type="file" wire:model="foto" class="hidden" accept="image/*">
                    </label>

                    @if ($fotoActual || $foto)
                        <button wire:click="eliminarFoto"
                            class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1 transition">
                            <span class="material-icons text-sm">delete</span>
                            Eliminar foto
                        </button>
                    @endif

                    <div wire:loading wire:target="foto" class="text-xs text-gray-400 flex items-center gap-1">
                        <span class="material-icons text-sm animate-spin">refresh</span> Procesando...
                    </div>

                    @error('foto') <p class="text-xs text-red-500 text-center">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel text-center">JPG, PNG · máx. 5MB</p>
                </div>
            </div>
        </div>

        {{-- FORMULARIO --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Datos personales --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Datos personales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('livewire.admin.partials.usuario-field', ['field' => 'name',     'label' => 'Nombre completo',    'required' => true])
                    @include('livewire.admin.partials.usuario-field', ['field' => 'username', 'label' => 'Usuario',            'required' => true])
                    @include('livewire.admin.partials.usuario-field', ['field' => 'cedula',   'label' => 'Cédula',             'required' => true])
                    @include('livewire.admin.partials.usuario-field', ['field' => 'ficha',    'label' => 'Ficha',              'required' => true])
                    @include('livewire.admin.partials.usuario-field', ['field' => 'telefono', 'label' => 'Teléfono'])
                    @include('livewire.admin.partials.usuario-field', ['field' => 'email',    'label' => 'Correo electrónico', 'type' => 'email'])
                </div>
            </div>

            {{-- Datos laborales --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Datos laborales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @include('livewire.admin.partials.usuario-select', ['field' => 'empresa_id',     'label' => 'Empresa',       'options' => $this->empresas,       'required' => true])
                    @include('livewire.admin.partials.usuario-select', ['field' => 'departamento_id','label' => 'Departamento',  'options' => $this->departamentos,  'placeholder' => $empresa_id ? 'Seleccione...' : 'Primero seleccione empresa', 'disabled' => !$empresa_id])
                    @include('livewire.admin.partials.usuario-select', ['field' => 'cargo_id',       'label' => 'Cargo',         'options' => $this->cargos,         'placeholder' => $departamento_id ? 'Seleccione...' : 'Primero seleccione departamento', 'disabled' => !$departamento_id])
                    @include('livewire.admin.partials.usuario-select', ['field' => 'ubicacion_id',   'label' => 'Ubicación',     'options' => $this->ubicaciones,    'required' => true])
                    @include('livewire.admin.partials.usuario-select', ['field' => 'jefe_id',        'label' => 'Jefe directo',  'options' => $this->jefes])
                </div>
            </div>

            {{-- Acceso --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Acceso al sistema</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(auth()->user()->hasRole('Administrador'))
                        @include('livewire.admin.partials.usuario-select', ['field' => 'rol_id',  'label' => 'Rol',    'options' => $this->roles, 'required' => true])
                        @include('livewire.admin.partials.usuario-select', ['field' => 'estado',  'label' => 'Estado', 'options' => ['Activo' => 'Activo', 'Inactivo' => 'Inactivo']])
                    @else
                        {{-- Analista: solo lectura --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">Rol</label>
                            <div class="w-full rounded-lg px-4 py-2 text-sm bg-gray-100 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel text-gray-500 dark:text-cerberus-light">
                                {{ $this->rolNombre ?: '—' }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-cerberus-accent mb-1">Estado</label>
                            <div class="w-full rounded-lg px-4 py-2 text-sm bg-gray-100 dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel text-gray-500 dark:text-cerberus-light">
                                {{ $estado }}
                            </div>
                        </div>
                    @endif
                    @include('livewire.admin.partials.usuario-field', ['field' => 'password',              'label' => 'Nueva contraseña',    'type' => 'password', 'placeholder' => 'Dejar vacío para no cambiar'])
                    @include('livewire.admin.partials.usuario-field', ['field' => 'password_confirmation', 'label' => 'Confirmar contraseña','type' => 'password'])
                </div>

                {{-- Empresas asignadas — solo Analista --}}
                @if ($this->rolNombre === 'Analista')
                    <div class="mt-5 pt-4 border-t border-gray-200 dark:border-cerberus-steel">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-cerberus-light mb-3">Empresas asignadas (rotación)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($this->empresas as $id => $nombre)
                                <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition
                                              {{ in_array((string)$id, array_map('strval', $empresa_ids))
                                                  ? 'border-[#1E40AF] dark:border-cerberus-primary bg-[#1E40AF]/5 dark:bg-cerberus-primary/10'
                                                  : 'border-gray-200 dark:border-cerberus-steel bg-gray-50 dark:bg-cerberus-dark hover:border-gray-300 dark:hover:border-cerberus-accent' }}">
                                    <input type="checkbox" wire:model.live="empresa_ids" value="{{ $id }}" class="rounded text-[#1E40AF]">
                                    <span class="text-sm text-gray-700 dark:text-cerberus-light">{{ $nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- BOTONES --}}
    <div class="flex justify-end gap-3 pb-6">
        <a href="{{ route('admin.usuarios.index') }}"
            class="px-5 py-2 rounded-lg border border-gray-300 dark:border-cerberus-steel
                   text-gray-600 dark:text-cerberus-light hover:bg-gray-100 dark:hover:bg-cerberus-steel/20 transition text-sm">
            Cancelar
        </a>
        <button wire:click="actualizar" wire:loading.attr="disabled"
            class="px-6 py-2 bg-[#1E40AF] hover:bg-[#1E3A8A] text-white font-semibold rounded-lg
                   transition flex items-center gap-2 text-sm disabled:opacity-60">
            <span wire:loading.remove wire:target="actualizar" class="material-icons text-base">save</span>
            <span wire:loading wire:target="actualizar" class="material-icons text-base animate-spin">refresh</span>
            <span wire:loading.remove wire:target="actualizar">Guardar cambios</span>
            <span wire:loading wire:target="actualizar">Guardando...</span>
        </button>
    </div>

</div>
