<x-app-layout title="Perfil" header="Mi Perfil">

    {{-- BREADCRUMB --}}
    <x-ui.breadcrumb :items="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Gestión de Usuarios', 'url' => '#'],
        ['label' => 'Perfil', 'url' => route('profile.edit')],
    ]" />

    <div class="mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="space-y-8 lg:col-span-2">

            {{-- FOTO DE PERFIL --}}
            <x-ui.card title="Foto de perfil" description="Actualiza tu imagen de usuario">
                @if (session('photo-updated'))
                    <x-form.success />
                @endif
                @if ($errors->has('photo'))
                    <div class="mb-6 rounded-lg border border-red-500 bg-red-950/40 p-4 text-red-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-icons text-red-400">error</span>
                            <h3 class="font-semibold">Error al actualizar la foto</h3>
                        </div>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @foreach ($errors->get('photo') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data"
                    class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-6">
                        <img id="previewFoto" src="{{ $user->foto_url }}"
                            class="w-24 h-24 rounded-full object-cover border border-cerberus-steel">

                        <label class="cursor-pointer text-cerberus-light hover:text-cerberus-accent">
                            <span class="material-icons mr-1">upload</span>
                            Cambiar foto
                            <input type="file" name="foto" id="fotoInput" class="hidden" accept="image/*">
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button
                            class="px-6 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white font-semibold rounded-lg">
                            Guardar foto
                        </button>
                    </div>
                </form>
            </x-card>

            {{-- INFORMACIÓN DEL PERFIL --}}
            <x-ui.card title="Información del perfil" description="Actualiza tus datos personales y correo electrónico">
                @if (session('profile-updated'))
                    <x-form.success />
                @endif
                @if ($errors->has('profile'))
                    <div class="mb-6 rounded-lg border border-red-500 bg-red-950/40 p-4 text-red-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-icons text-red-400">error</span>
                            <h3 class="font-semibold">Error al actualizar el perfil</h3>
                        </div>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @foreach ($errors->get('profile') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <x-form.input name="name" label="Nombre" :value="old('name', $user->name)" required />

                    <x-form.input type="email" name="email" label="Correo electrónico" :value="old('email', $user->email)" required />

                    <div class="flex justify-end">
                        <button
                            class="px-6 py-2 bg-cerberus-primary hover:bg-cerberus-hover text-white font-semibold rounded-lg">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </x-card>

            {{-- INFORMACIÓN ADICIONAL --}}
            <x-ui.card title="Información adicional" description="Datos laborales y de sistema asociados a tu cuenta">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">

                    {{-- Usuario --}}
                    <div>
                        <p class="text-cerberus-light">Usuario</p>
                        <p class="text-white font-medium">
                            {{ $user->username ?? '—' }}
                        </p>
                    </div>

                    {{-- Email --}}
                    <div>
                        <p class="text-cerberus-light">Correo</p>
                        <p class="text-white font-medium">
                            {{ $user->email }}
                        </p>
                    </div>

                    {{-- Rol(es) --}}
                    <div>
                        <p class="text-cerberus-light">Rol</p>
                        <p class="text-white font-medium">
                            {{ $user->roles->pluck('name')->join(', ') }}
                        </p>
                    </div>

                    {{-- Estado --}}
                    <div>
                        <p class="text-cerberus-light">Estado</p>
                        <span
                            class="inline-block px-2 py-0.5 rounded text-xs
                {{ $user->estado === 'Activo' ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400' }}">
                            {{ $user->estado }}
                        </span>
                    </div>

                    {{-- Empresa nómina --}}
                    <div>
                        <p class="text-cerberus-light">Empresa nómina</p>
                        <p class="text-white font-medium">
                            {{ optional($user->empresaNomina)->nombre ?? '—' }}
                        </p>
                    </div>

                    {{-- Empresa activa --}}
                    @if ($user->empresaActiva)
                        <div>
                            <p class="text-cerberus-light">Empresa activa</p>
                            <p class="text-white font-medium">
                                {{ $user->empresaActiva->nombre }}
                            </p>
                        </div>
                    @endif

                    {{-- Empresas asignadas (solo Analista) --}}
                    @if ($user->hasRole('Analista'))
                        <div class="sm:col-span-2">
                            <p class="text-cerberus-light">Empresas asignadas</p>

                            @if ($user->empresasAsignadas->isEmpty())
                                <p class="text-white font-medium">—</p>
                            @else
                                <div class="flex flex-wrap gap-2 mt-1">
                                    @foreach ($user->empresasAsignadas as $empresa)
                                        <span
                                            class="px-2 py-0.5 rounded text-xs
                                bg-blue-600/20 text-blue-400">
                                            {{ $empresa->nombre }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Departamento --}}
                    <div>
                        <p class="text-cerberus-light">Departamento</p>
                        <p class="text-white font-medium">
                            {{ optional($user->departamento)->nombre ?? '—' }}
                        </p>
                    </div>

                    {{-- Cargo --}}
                    <div>
                        <p class="text-cerberus-light">Cargo</p>
                        <p class="text-white font-medium">
                            {{ optional($user->cargo)->nombre ?? '—' }}
                        </p>
                    </div>

                    {{-- Ubicación --}}
                    <div>
                        <p class="text-cerberus-light">Ubicación</p>
                        <p class="text-white font-medium">
                            {{ optional($user->ubicacion)->nombre ?? '—' }}
                        </p>
                    </div>

                    {{-- Jefe --}}
                    @if ($user->jefe)
                        <div>
                            <p class="text-cerberus-light">Jefe directo</p>
                            <p class="text-white font-medium">
                                {{ $user->jefe->name }}
                            </p>
                        </div>
                    @endif

                    {{-- Ficha --}}
                    <div>
                        <p class="text-cerberus-light">Ficha</p>
                        <p class="text-white font-medium">
                            {{ $user->ficha ?? '—' }}
                        </p>
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <p class="text-cerberus-light">Teléfono</p>
                        <p class="text-white font-medium">
                            {{ $user->telefono ?? '—' }}
                        </p>
                    </div>

                    {{-- Fecha creación --}}
                    <div>
                        <p class="text-cerberus-light">Cuenta creada</p>
                        <p class="text-white font-medium">
                            {{ $user->created_at?->format('d/m/Y') }}
                        </p>
                    </div>

                </div>
            </x-card>

            {{-- SEGURIDAD --}}
            <x-ui.card title="Seguridad" description="Actualiza tu contraseña de acceso">
                @if (session('password-updated'))
                    <x-form.success />
                @endif
                @if ($errors->has('password'))
                    <div class="mb-6 rounded-lg border border-red-500 bg-red-950/40 p-4 text-red-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="material-icons text-red-400">error</span>
                            <h3 class="font-semibold">Error al actualizar la contraseña</h3>
                        </div>
                        <ul class="list-disc list-inside text-sm space-y-1">
                            @foreach ($errors->get('password') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <x-form.input type="password" name="current_password" label="Contraseña actual" required />

                    <x-form.input type="password" name="password" label="Nueva contraseña" required />

                    <x-form.input type="password" name="password_confirmation" label="Confirmar contraseña" required />

                    <div class="flex justify-end">
                        <x-auth.primary-button>
                            Actualizar contraseña
                        </x-primary-button>
                    </div>
                </form>
            </x-card>
        </div>

        {{-- ULTIMAS ACCIONES --}}
        <aside class="lg:col-span-1">
            <x-ui.card title="Actividad reciente" description="Últimas acciones realizadas en el sistema">
                <ol class="relative border-s border-cerberus-steel ps-6 space-y-8">

                    @forelse($auditorias as $log)
                        <li class="relative">
                            {{-- Nodo --}}
                            <span
                                class="absolute -start-[14px] top-1 flex h-6 w-6 items-center justify-center rounded-full
                   bg-cerberus-primary/20 ring-4 ring-cerberus-dark">

                                {{-- Icono según acción --}}
                                @switch($log->accion)
                                    @case('create')
                                        <span class="material-icons text-sm text-green-400">add</span>
                                    @break

                                    @case('update')
                                        <span class="material-icons text-sm text-blue-400">edit</span>
                                    @break

                                    @case('delete')
                                        <span class="material-icons text-sm text-red-400">delete</span>
                                    @break

                                    @default
                                        <span class="material-icons text-sm text-cerberus-light">info</span>
                                @endswitch
                            </span>

                            {{-- Fecha --}}
                            <time
                                class="inline-block text-xs px-5 py-0.5 rounded
                   bg-cerberus-steel/40 text-cerberus-light">
                                {{ $log->created_at ? \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i') : '—' }}

                            </time>

                            {{-- Acción --}}
                            <h3 class="mt-2 text-sm font-semibold text-white">
                                {{ ucfirst($log->accion) }}
                                <span class="text-cerberus-light">
                                    {{ $log->tabla }}
                                </span>
                            </h3>

                            {{-- Detalle --}}
                            <p class="text-xs text-cerberus-light">
                                Registro #{{ $log->registro_id }}
                            </p>
                        </li>
                        @empty
                            <p class="text-sm text-cerberus-light text-center">
                                No hay actividad reciente.
                            </p>
                        @endforelse

                    </ol>
                    <div class="mt-6 text-center">
                        <a href="{{ route('profile.activity') }}" class="text-sm text-cerberus-accent hover:underline">
                            Ver historial completo
                        </a>
                    </div>
                </x-card>
            </aside>

            {{-- ZONA PELIGROSA --}}
            {{-- <x-ui.card title="Eliminar cuenta" description="Esta acción es permanente y no se puede deshacer"
            class="border-red-300">
            <form method="POST" action="{{ route('profile.destroy') }}"
                onsubmit="return confirm('¿Seguro que deseas eliminar tu cuenta? Esta acción no se puede deshacer.')"
                class="space-y-4">
                @csrf
                @method('DELETE')

                <p class="text-sm text-red-700">
                    Al eliminar tu cuenta, todos tus datos serán borrados permanentemente.
                </p>

                <div>
                    <x-form.input type="password" name="password_delete" label="Contraseña"
                        placeholder="Confirma tu contraseña" required /> --}}
            {{-- <x-auth.input-label for="password_delete" value="Contraseña" />
                    <x-auth.text-input id="password_delete" name="password" type="password" class="mt-1 block w-full"
                        placeholder="Confirma tu contraseña" />
                    <x-auth.input-error :messages="$errors->get('password')" class="mt-2" /> --}}
            {{-- </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition">
                        Eliminar cuenta
                    </button>
                </div>
            </form>
        </x-card> --}}

        </div>

        {{-- MODAL CROP FOTO --}}
        <x-modal.modal name="crop-photo" maxWidth="lg">
            <div class="bg-cerberus-dark p-6 space-y-4">

                <h3 class="text-lg font-semibold text-white">
                    Ajustar foto de perfil
                </h3>

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
        </x-modal>

        {{-- <script>
            let cropper = null;
            const fotoInput = document.getElementById('fotoInput');
            const modal = document.getElementById('cropperModal');
            const cropperImage = document.getElementById('cropperImage');
            const previewFoto = document.getElementById('previewFoto');

            const openModal = () => window.dispatchEvent(new CustomEvent('open-modal', {
                detail: 'crop-photo'
            }))
            const closeModal = () => window.dispatchEvent(new CustomEvent('close-modal', {
                detail: 'crop-photo'
            }))

            fotoInput?.addEventListener('change', e => {
                const file = e.target.files[0];
                if (!file || !file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = () => {
                    cropperImage.src = reader.result;
                    openModal();

                    cropper?.destroy();
                    requestAnimationFrame(() => {
                        cropper = new Cropper(cropperImage, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 1,
                            background: false,
                            responsive: true,
                        });
                    });
                };
                reader.readAsDataURL(file);
            });

            document.getElementById('cancelCrop')?.addEventListener('click', () => {
                cropper?.destroy();
                cropper = null;
                fotoInput.value = '';
                closeModal();
            });

            document.getElementById('confirmCrop')?.addEventListener('click', () => {
                if (!cropper) return;

                const canvas = cropper.getCroppedCanvas({
                    width: 256,
                    height: 256,
                    imageSmoothingQuality: 'high',
                });

                canvas.toBlob(blob => {
                    const file = new File([blob], 'avatar.jpg', {
                        type: 'image/jpeg'
                    });

                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fotoInput.files = dataTransfer.files;

                    previewFoto.src = URL.createObjectURL(blob);

                    cropper.destroy();
                    cropper = null;
                    closeModal();
                }, 'image/jpeg', 0.9);
            });
        </script> --}}

    </x-app-layout>
