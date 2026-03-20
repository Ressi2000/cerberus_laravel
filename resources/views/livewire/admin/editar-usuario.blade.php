<div class="space-y-6 max-w-5xl mx-auto">

    {{-- HEADER --}}
    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-icons text-[#1E40AF] dark:text-cerberus-accent text-2xl">manage_accounts</span>
                <div>
                    <h2 class="text-xl font-bold text-[#1E293B] dark:text-white">Editar usuario</h2>
                    <p class="text-sm text-gray-500 dark:text-cerberus-light">
                        {{ $usuario->username }} · {{ $usuario->empresaNomina->nombre ?? '—' }}
                    </p>
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

        {{-- ── FOTO ─────────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Foto de perfil</h3>
                <div class="flex flex-col items-center gap-4">

                    <img id="previewFoto"
                        src="{{ $this->fotoPreviewUrl }}"
                        class="w-28 h-28 rounded-full object-cover border-2 border-[#1E40AF] dark:border-cerberus-primary">

                    {{-- Input visible → cropper JS --}}
                    <label class="cursor-pointer flex items-center gap-2 px-4 py-2 rounded-lg text-sm
                                  bg-gray-100 dark:bg-cerberus-dark border border-gray-300 dark:border-cerberus-steel
                                  text-gray-700 dark:text-cerberus-light hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        <span class="material-icons text-base">upload</span>
                        Cambiar foto
                        <input type="file" id="fotoInput" class="hidden" accept="image/*">
                    </label>

                    {{-- Input oculto → Livewire --}}
                    <input type="file" id="fotoInputLivewire" wire:model="foto" class="hidden" accept="image/*">

                    @if ($fotoActual || $foto)
                        <button wire:click="eliminarFoto"
                            class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1 transition">
                            <span class="material-icons text-sm">delete</span>
                            Eliminar foto
                        </button>
                    @endif

                    <div wire:loading wire:target="foto" class="text-xs text-gray-400 flex items-center gap-1">
                        <span class="material-icons text-sm animate-spin">refresh</span> Subiendo...
                    </div>
                    @error('foto')
                        <p class="text-red-500 text-xs text-center">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 dark:text-cerberus-steel text-center">JPG, PNG · máx. 5MB</p>
                </div>
            </div>
        </div>

        {{-- ── FORMULARIO ───────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Datos personales --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Datos personales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                    <x-form.input label="Nombre completo"    wire:model="name"     required :error="$errors->first('name')" />
                    <x-form.input label="Usuario"            wire:model="username" required :error="$errors->first('username')" />
                    <x-form.input label="Cédula"             wire:model="cedula"   required :error="$errors->first('cedula')" />
                    <x-form.input label="Ficha"              wire:model="ficha"    required :error="$errors->first('ficha')" />
                    <x-form.input label="Teléfono"           wire:model="telefono"          :error="$errors->first('telefono')" />
                    <x-form.input label="Correo electrónico" wire:model="email"    type="email" :error="$errors->first('email')" />
                </div>
            </div>

            {{-- Datos laborales --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Datos laborales</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                    <x-form.select
                        label="Empresa (nómina)"
                        wire:model.live="empresa_id"
                        :options="$this->empresas"
                        required
                        :error="$errors->first('empresa_id')"
                    />
                    <x-form.select
                        label="Departamento"
                        wire:model.live="departamento_id"
                        :options="$this->departamentos"
                        :placeholder="$empresa_id ? 'Seleccione...' : 'Primero seleccione empresa'"
                        :disabled="!$empresa_id"
                        :error="$errors->first('departamento_id')"
                    />
                    <x-form.select
                        label="Cargo"
                        wire:model.live="cargo_id"
                        :options="$this->cargos"
                        :placeholder="$departamento_id ? 'Seleccione...' : 'Primero seleccione departamento'"
                        :disabled="!$departamento_id"
                        :error="$errors->first('cargo_id')"
                    />
                    <x-form.select
                        label="Ubicación"
                        wire:model.live="ubicacion_id"
                        :options="$this->ubicaciones"
                        required
                        :error="$errors->first('ubicacion_id')"
                    />
                    <x-form.select
                        label="Jefe directo"
                        wire:model="jefe_id"
                        :options="$this->jefes"
                        :error="$errors->first('jefe_id')"
                    />
                </div>
            </div>

            {{-- Acceso al sistema --}}
            <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-6 shadow-sm">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-cerberus-accent uppercase tracking-wide mb-4">Acceso al sistema</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">

                    @if (auth()->user()->hasRole('Administrador'))
                        <x-form.select
                            label="Rol"
                            wire:model.live="rol_id"
                            :options="$this->roles"
                            required
                            :error="$errors->first('rol_id')"
                        />
                        <x-form.select
                            label="Estado"
                            wire:model="estado"
                            :options="['Activo' => 'Activo', 'Inactivo' => 'Inactivo']"
                        />
                    @else
                        {{-- Analista: rol y estado de solo lectura --}}
                        <x-form.input label="Rol"    :value="$this->rolNombre" readonly />
                        <x-form.input label="Estado" :value="$estado"          readonly />
                    @endif

                    <x-form.input label="Nueva contraseña"    wire:model="password"              type="password" placeholder="Dejar vacío para no cambiar" :error="$errors->first('password')" />
                    <x-form.input label="Confirmar contraseña" wire:model="password_confirmation" type="password" />
                </div>

                {{-- Empresas asignadas — solo si rol = Analista --}}
                @if ($this->rolNombre === 'Analista')
                    <div class="mt-5 pt-4 border-t border-gray-200 dark:border-cerberus-steel">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-cerberus-light mb-3">
                            Empresas asignadas
                            <span class="text-xs text-gray-400 dark:text-cerberus-steel font-normal ml-1">(rotación entre empresas)</span>
                        </h4>
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
            <span wire:loading      wire:target="actualizar" class="material-icons text-base animate-spin">refresh</span>
            <span wire:loading.remove wire:target="actualizar">Guardar cambios</span>
            <span wire:loading      wire:target="actualizar">Guardando...</span>
        </button>
    </div>

    {{-- MODAL CROPPER --}}
    <x-modal.modal name="crop-photo" maxWidth="lg">
        <div class="bg-cerberus-dark p-6 space-y-4">
            <h3 class="text-lg font-semibold text-white">Ajustar foto de perfil</h3>
            <div class="w-full max-h-[400px] overflow-hidden bg-black rounded-lg">
                <img id="cropperImage" class="max-w-full block">
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" id="cancelCrop"
                    class="px-4 py-2 text-sm rounded-lg bg-cerberus-steel text-white hover:bg-cerberus-hover">
                    Cancelar
                </button>
                <button type="button" id="confirmCrop"
                    class="px-4 py-2 text-sm rounded-lg bg-cerberus-primary text-white hover:bg-cerberus-hover">
                    Usar imagen
                </button>
            </div>
        </div>
    </x-modal.modal>

</div>
