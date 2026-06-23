<div class="space-y-5">

    {{-- ── Indicador de pasos ──────────────────────────────────────────────── --}}
    <div class="flex items-center gap-0">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        {{ $paso === 1 ? 'bg-cerberus-primary text-white' : 'bg-emerald-600 text-white' }}">
                @if ($paso > 1)
                    <span class="material-icons text-sm">check</span>
                @else
                    1
                @endif
            </div>
            <span class="text-sm {{ $paso === 1 ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400 dark:text-cerberus-steel' }}">
                Datos del traslado
            </span>
        </div>
        <div class="flex-1 h-px mx-3 {{ $paso > 1 ? 'bg-cerberus-primary' : 'bg-gray-200 dark:bg-cerberus-steel/40' }}"></div>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        {{ $paso === 2 ? 'bg-cerberus-primary text-white' : 'bg-gray-200 dark:bg-cerberus-steel/40 text-gray-500 dark:text-cerberus-steel' }}">
                2
            </div>
            <span class="text-sm {{ $paso === 2 ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-400 dark:text-cerberus-steel' }}">
                Seleccionar equipos
            </span>
        </div>
    </div>

    @error('general')
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg
                    bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50
                    text-red-700 dark:text-red-300 text-sm">
            <span class="material-icons text-base flex-shrink-0">error_outline</span>
            {{ $message }}
        </div>
    @enderror

    {{-- ════════════════════════════ PASO 1 ════════════════════════════════ --}}
    @if ($paso === 1)
        <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                    rounded-xl p-6 space-y-5">

            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="material-icons text-cerberus-accent text-lg">local_shipping</span>
                Datos del traslado
            </h2>

            {{-- Empresa (solo Admin) --}}
            @role('Administrador')
                <x-form.select
                    label="Empresa"
                    wire:model.live="empresa_id"
                    :options="$this->empresasOpciones"
                    placeholder="— Selecciona empresa —"
                    :error="$errors->first('empresa_id')"
                    required
                />
            @endrole

            {{-- Ubicaciones origen / destino --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form.select
                    label="Ubicación de origen"
                    wire:model.live="ubicacion_origen_id"
                    :options="$this->ubicacionesOrigenOpciones"
                    placeholder="— Selecciona origen —"
                    :error="$errors->first('ubicacion_origen_id')"
                    hint="Solo las ubicaciones que puedes gestionar."
                    required
                />
                <x-form.select
                    label="Ubicación de destino"
                    wire:model.live="ubicacion_destino_id"
                    :options="$this->ubicacionesDestinoOpciones"
                    placeholder="— Selecciona destino —"
                    :error="$errors->first('ubicacion_destino_id')"
                    hint="Puede ser cualquier sede del sistema."
                    required
                />
            </div>

            {{-- Motivo --}}
            <x-form.textarea
                label="Motivo del traslado"
                wire:model.live="motivo"
                rows="3"
                placeholder="Describe el motivo del traslado de equipos..."
                :error="$errors->first('motivo')"
                required
            />

            {{-- Quién recibe / quién autoriza --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-form.select
                    label="Recibe"
                    wire:model.live="recibe_id"
                    :options="$this->usuariosOpciones"
                    placeholder="— Selecciona quien recibe —"
                    :error="$errors->first('recibe_id')"
                    required
                />
                <x-form.select
                    label="Autoriza"
                    wire:model.live="autoriza_id"
                    :options="$this->usuariosOpciones"
                    placeholder="— Selecciona quien autoriza —"
                    :error="$errors->first('autoriza_id')"
                    required
                />
            </div>

            {{-- Fecha y observaciones --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-form.input
                        label="Fecha del traslado"
                        type="date"
                        wire:model.live="fecha_traslado"
                        required
                    />
                    @error('fecha_traslado') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-form.input
                        label="Observaciones (opcional)"
                        wire:model.live="observaciones"
                        placeholder="Información adicional..."
                    />
                </div>
            </div>
        </div>

        {{-- Botón siguiente --}}
        <div class="flex justify-end">
            <button type="button" wire:click="irPaso2"
                class="flex items-center gap-2 px-6 py-2.5 rounded-lg
                       bg-cerberus-primary hover:bg-cerberus-primary/90
                       text-white text-sm font-medium transition-colors">
                Siguiente
                <span class="material-icons text-base">arrow_forward</span>
            </button>
        </div>
    @endif

    {{-- ════════════════════════════ PASO 2 ════════════════════════════════ --}}
    @if ($paso === 2)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- ── Panel izquierdo: equipos disponibles ─────────────────── --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Resumen del traslado --}}
                <div class="bg-blue-50 dark:bg-cerberus-primary/10 border border-blue-200 dark:border-cerberus-primary/30
                            rounded-xl p-4">
                    <p class="text-xs font-semibold text-cerberus-primary dark:text-cerberus-accent uppercase tracking-wider mb-2">
                        Traslado configurado
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-cerberus-steel">Origen:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">
                                {{ $this->ubicacionesOrigen->firstWhere('id', $ubicacion_origen_id)?->nombre ?? '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-cerberus-steel">Destino:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">
                                {{ $this->ubicacionesDestino->firstWhere('id', $ubicacion_destino_id)?->nombre ?? '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-cerberus-steel">Recibe:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">
                                {{ $this->usuarios->firstWhere('id', $recibe_id)?->name ?? '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-cerberus-steel">Autoriza:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">
                                {{ $this->usuarios->firstWhere('id', $autoriza_id)?->name ?? '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Filtros de categoría --}}
                @if ($this->categorias->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            wire:click="setCategoriaFiltro('')"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                                   {{ ! $filtro_categoria ? 'bg-cerberus-primary text-white' : 'bg-gray-100 dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-light hover:bg-gray-200 dark:hover:bg-cerberus-steel/30' }}">
                            Todos
                        </button>
                        @foreach ($this->categorias as $cat)
                            <button type="button"
                                wire:click="setCategoriaFiltro('{{ $cat->id }}')"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition
                                       {{ $filtro_categoria == $cat->id ? 'bg-cerberus-primary text-white' : 'bg-gray-100 dark:bg-cerberus-dark text-gray-600 dark:text-cerberus-light hover:bg-gray-200 dark:hover:bg-cerberus-steel/30' }}">
                                {{ $cat->nombre }}
                                <span class="opacity-70">({{ $cat->disponibles_count }})</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Fila 0: escaneo de código de barras / QR (lector HID o cámara) --}}
                <div class="relative"
                    x-data="{
                        camaraAbierta: false,
                        usandoFallback: false,
                        errorCamara: '',
                        stream: null,
                        detectorInterval: null,
                        html5Qr: null,
                        ultimoValor: null,
                        ultimoTiempo: 0,
                        toastVisible: false,
                        toastOk: true,
                        toastMensaje: '',
                        toastTimeout: null,

                        playBeep(ok) {
                            try {
                                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.frequency.value = ok ? 880 : 220;
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                                osc.start();
                                osc.stop(ctx.currentTime + (ok ? 0.12 : 0.25));
                            } catch (e) {}
                        },

                        cargarScript(src) {
                            return new Promise((resolve, reject) => {
                                const s = document.createElement('script');
                                s.src = src;
                                s.onload = resolve;
                                s.onerror = reject;
                                document.head.appendChild(s);
                            });
                        },

                        async abrirCamara() {
                            this.errorCamara = '';
                            this.ultimoValor = null;
                            this.ultimoTiempo = 0;
                            this.camaraAbierta = true;
                            this.usandoFallback = !('BarcodeDetector' in window);
                            await this.$nextTick();
                            this.usandoFallback ? await this.iniciarFallback() : await this.iniciarNativo();
                        },

                        async iniciarNativo() {
                            try {
                                this.stream = await navigator.mediaDevices.getUserMedia({
                                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                                });
                                if (! this.camaraAbierta) { // se cerró mientras esperábamos el permiso
                                    this.stream.getTracks().forEach(t => t.stop());
                                    return;
                                }
                                this.$refs.video.srcObject = this.stream;
                                await this.$refs.video.play();
                                const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128'] });

                                // Bucle auto-programado (no setInterval de paso fijo): pide el siguiente
                                // frame apenas termina de analizar el actual, así detecta tan rápido como
                                // el dispositivo pueda en vez de esperar un intervalo fijo de por medio.
                                const detectar = async () => {
                                    if (! this.camaraAbierta) return;
                                    try {
                                        const codigos = await detector.detect(this.$refs.video);
                                        if (codigos.length) this.onDetectado(codigos[0].rawValue);
                                    } catch (e) {}
                                    this.detectorInterval = requestAnimationFrame(detectar);
                                };
                                detectar();
                            } catch (e) {
                                this.errorCamara = 'No se pudo acceder a la cámara. Revisa los permisos del navegador.';
                            }
                        },

                        async iniciarFallback() {
                            try {
                                if (! window.Html5Qrcode) {
                                    await this.cargarScript('https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js');
                                }
                                if (! this.camaraAbierta) return; // se cerró mientras cargaba la librería
                                this.html5Qr = new Html5Qrcode('camara-fallback');
                                await this.html5Qr.start(
                                    { facingMode: 'environment' },
                                    { fps: 20, qrbox: 240 },
                                    (decodedText) => this.onDetectado(decodedText),
                                    () => {}
                                );
                            } catch (e) {
                                this.errorCamara = 'No se pudo acceder a la cámara. Revisa los permisos del navegador.';
                            }
                        },

                        // La cámara se queda abierta entre escaneos para permitir escanear varios
                        // equipos seguidos. El modal tiene wire:ignore, asi que el morphing de
                        // Livewire nunca toca el video y el stream no se cuelga.
                        onDetectado(valor) {
                            const ahora = Date.now();
                            if (valor === this.ultimoValor && (ahora - this.ultimoTiempo) < 1500) return;
                            this.ultimoValor = valor;
                            this.ultimoTiempo = ahora;
                            this.$wire.procesarEscaneoCamara(valor);
                        },

                        mostrarToast(ok, mensaje) {
                            this.toastOk = ok;
                            this.toastMensaje = mensaje;
                            this.toastVisible = true;
                            clearTimeout(this.toastTimeout);
                            this.toastTimeout = setTimeout(() => { this.toastVisible = false; }, 1800);
                        },

                        cerrarCamara() {
                            this.camaraAbierta = false;
                            this.errorCamara = '';
                            this.toastVisible = false;
                            clearTimeout(this.toastTimeout);
                            if (this.detectorInterval) { cancelAnimationFrame(this.detectorInterval); this.detectorInterval = null; }
                            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
                            if (this.html5Qr) { this.html5Qr.stop().catch(() => {}).then(() => this.html5Qr?.clear()); this.html5Qr = null; }
                        },
                    }"
                    x-init="
                        $wire.on('equipo-escaneado', (e) => {
                            playBeep(e.ok);
                            mostrarToast(e.ok, e.mensaje);
                            if (! camaraAbierta) $refs.inputEscaneo.focus();
                        });
                        $refs.inputEscaneo.focus();
                    ">

                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                <span class="material-icons text-base text-cerberus-primary">qr_code_scanner</span>
                            </span>
                            <input type="text" x-ref="inputEscaneo" wire:model="escaneo"
                                wire:keydown.enter.prevent="procesarEscaneo" autocomplete="off"
                                placeholder="Escanear código de barras o QR del equipo..."
                                class="w-full bg-cerberus-primary/5 dark:bg-cerberus-primary/10 border border-cerberus-primary/30
                                          text-gray-900 dark:text-white rounded-lg pl-10 pr-4 py-2.5 text-sm
                                          focus:outline-none focus:border-cerberus-primary focus:ring-1 focus:ring-cerberus-primary/30 transition" />
                        </div>

                        <button type="button" x-on:click="abrirCamara()" title="Escanear con la cámara"
                            class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg
                                   border border-cerberus-primary/30 bg-cerberus-primary/5 dark:bg-cerberus-primary/10
                                   text-cerberus-primary hover:bg-cerberus-primary/15 transition">
                            <span class="material-icons text-lg">photo_camera</span>
                        </button>
                    </div>

                    {{-- Modal de escaneo por cámara, teletransportado al <body>. La animación
                         "animate-fade-in" del contenedor del paso 2 deja un transform aplicado
                         (animation-fill-mode: forwards), y eso convierte a ese contenedor en el
                         "containing block" de cualquier descendiente position:fixed — por eso el
                         modal aparecía centrado dentro de TODO el alto de la grilla de equipos en
                         vez del viewport visible, obligando a hacer scroll para verlo. Al moverlo
                         fuera de ese árbol con x-teleport, queda anclado al viewport real.
                         wire:ignore evita que el morphing de Livewire toque este subárbol entre
                         escaneos, así el <video> y su stream se mantienen vivos. --}}
                    <template x-teleport="body">
                        <div wire:ignore x-show="camaraAbierta" x-cloak x-on:keydown.escape.window="cerrarCamara()"
                            x-on:click.self="cerrarCamara()"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 overflow-y-auto">
                            <div x-on:click.stop
                                class="relative bg-white dark:bg-cerberus-mid rounded-xl shadow-xl w-full max-w-md
                                       max-h-[90vh] overflow-y-auto my-auto">

                                {{-- Botón de cierre flotante: siempre visible aunque el header quede fuera de vista --}}
                                <button type="button" x-on:click="cerrarCamara()" title="Cerrar"
                                    class="absolute top-2 right-2 z-10 flex items-center justify-center w-9 h-9 rounded-full
                                           bg-black/60 text-white hover:bg-black/80 transition">
                                    <span class="material-icons text-lg">close</span>
                                </button>

                                <div
                                    class="flex items-center gap-1.5 px-4 py-3 border-b border-gray-100 dark:border-cerberus-steel/40">
                                    <span class="material-icons text-base text-cerberus-accent">photo_camera</span>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        Escanear con la cámara
                                    </p>
                                </div>

                                <div class="relative bg-black h-72">
                                    <video x-ref="video" x-show="!usandoFallback" muted playsinline
                                        class="w-full h-full object-cover"></video>
                                    <div id="camara-fallback" x-show="usandoFallback" class="w-full h-full"></div>
                                    <div class="absolute inset-0 m-8 border-2 border-white/70 rounded-lg pointer-events-none"></div>

                                    {{-- Toast de confirmación tras cada escaneo, sin cerrar la cámara --}}
                                    <div x-show="toastVisible" x-cloak x-transition.opacity
                                        class="absolute bottom-3 left-3 right-3 rounded-lg px-3 py-2 text-xs font-semibold text-center text-white"
                                        :class="toastOk ? 'bg-green-600/90' : 'bg-red-600/90'"
                                        x-text="toastMensaje"></div>
                                </div>

                                <div class="px-4 py-3 space-y-2">
                                    <p class="text-xs text-gray-400 dark:text-cerberus-steel text-center">
                                        Apunta el código de barras o QR del equipo hacia el centro. Puedes escanear
                                        varios equipos seguidos sin cerrar la cámara.
                                    </p>
                                    <p x-show="errorCamara" x-text="errorCamara" class="text-xs text-red-500 text-center"></p>
                                    <button type="button" x-on:click="cerrarCamara()"
                                        class="w-full py-2 rounded-lg text-xs font-medium bg-cerberus-primary
                                               text-white hover:bg-cerberus-primary/90 transition">
                                        Listo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Buscador --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-cerberus-steel">
                        <span class="material-icons text-base">search</span>
                    </span>
                    <input type="text"
                        wire:model.live.debounce.300ms="filtro_busqueda"
                        placeholder="Buscar por código, marca, modelo, atributos..."
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-lg
                               bg-white dark:bg-cerberus-dark
                               border border-gray-200 dark:border-cerberus-steel
                               text-gray-900 dark:text-white
                               placeholder-gray-400 dark:placeholder-cerberus-steel
                               focus:outline-none focus:ring-2 focus:ring-cerberus-primary/50">
                </div>

                {{-- Grilla de equipos --}}
                <div wire:loading.class="opacity-50" wire:target="filtro_busqueda,filtro_categoria,setCategoriaFiltro">

                    @if ($this->equiposDisponibles->isEmpty())
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <span class="material-icons text-4xl text-gray-300 dark:text-cerberus-steel/50 mb-3">
                                devices_off
                            </span>
                            <p class="text-sm text-gray-500 dark:text-cerberus-steel">
                                @if ($ubicacion_origen_id)
                                    No hay equipos disponibles en esta ubicación.
                                @else
                                    Selecciona una ubicación de origen en el paso anterior.
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($this->equiposDisponibles as $equipo)
                                @php
                                    $marca  = $equipo->atributosActuales->firstWhere('atributo.slug', 'marca')?->valor ?? null;
                                    $modelo = $equipo->atributosActuales->firstWhere('atributo.slug', 'modelo')?->valor ?? null;
                                @endphp
                                <div class="group bg-white dark:bg-cerberus-dark border border-gray-200 dark:border-cerberus-steel/50
                                            rounded-xl p-4 hover:border-cerberus-primary/50 hover:shadow-sm transition-all">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-lg bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                                                        flex items-center justify-center flex-shrink-0">
                                                <span class="material-icons text-cerberus-primary dark:text-cerberus-accent text-lg">
                                                    devices
                                                </span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ $equipo->codigo_interno }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-cerberus-steel">
                                                    {{ $equipo->categoria?->nombre ?? '—' }}
                                                    @if ($marca || $modelo)
                                                        · {{ implode(' ', array_filter([$marca, $modelo])) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <button type="button"
                                            wire:click="agregarAlCarrito({{ $equipo->id }})"
                                            class="flex-shrink-0 w-8 h-8 rounded-lg
                                                   bg-cerberus-primary/10 hover:bg-cerberus-primary
                                                   text-cerberus-primary hover:text-white
                                                   flex items-center justify-center transition-colors">
                                            <span class="material-icons text-base">add</span>
                                        </button>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span class="text-xs px-2 py-0.5 rounded-full
                                                     {{ $equipo->estado?->nombre === 'Disponible' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' }}">
                                            {{ $equipo->estado?->nombre ?? '—' }}
                                        </span>
                                        <span class="text-xs px-2 py-0.5 rounded-full
                                                     bg-slate-100 dark:bg-slate-700/30 text-slate-600 dark:text-slate-400">
                                            {{ $equipo->ubicacion?->nombre ?? '—' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $this->equiposDisponibles->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Panel derecho: carrito ───────────────────────────────── --}}
            <div class="space-y-4">
                <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
                            rounded-xl overflow-hidden sticky top-5">

                    <div class="px-4 py-3 bg-gray-50 dark:bg-cerberus-dark border-b border-gray-200 dark:border-cerberus-steel flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="material-icons text-cerberus-accent text-base">inventory</span>
                            Equipos a trasladar
                        </h3>
                        @if (! empty($carrito))
                            <span class="px-2 py-0.5 rounded-full bg-cerberus-primary text-white text-xs font-bold">
                                {{ count($carrito) }}
                            </span>
                        @endif
                    </div>

                    @error('carrito')
                        <div class="px-4 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-700/40">
                            <p class="text-red-600 dark:text-red-400 text-xs">{{ $message }}</p>
                        </div>
                    @enderror

                    <div class="divide-y divide-gray-100 dark:divide-cerberus-steel/30 max-h-96 overflow-y-auto">
                        @forelse ($carrito as $item)
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="w-8 h-8 rounded-lg bg-cerberus-primary/10 dark:bg-cerberus-primary/20
                                            flex items-center justify-center flex-shrink-0">
                                    <span class="material-icons text-cerberus-primary dark:text-cerberus-accent text-sm">
                                        {{ $item['icono'] }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $item['codigo'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-cerberus-steel truncate">
                                        {{ $item['categoria'] }}
                                        @if (! empty($item['ubicacion']) && $item['ubicacion'] !== '—')
                                            · {{ $item['ubicacion'] }}
                                        @endif
                                    </p>
                                </div>
                                <button type="button"
                                    wire:click="quitarDelCarrito({{ $item['id'] }})"
                                    class="flex-shrink-0 w-7 h-7 rounded-lg
                                           text-gray-400 dark:text-cerberus-steel
                                           hover:bg-red-50 dark:hover:bg-red-900/20
                                           hover:text-red-500 dark:hover:text-red-400
                                           flex items-center justify-center transition-colors">
                                    <span class="material-icons text-base">close</span>
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-center">
                                <span class="material-icons text-3xl text-gray-300 dark:text-cerberus-steel/50 block mb-2">
                                    inbox
                                </span>
                                <p class="text-xs text-gray-400 dark:text-cerberus-steel">
                                    Aún no has seleccionado equipos
                                </p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Acciones --}}
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-cerberus-steel space-y-2">
                        <button type="button"
                            wire:click="confirmar"
                            wire:loading.attr="disabled"
                            @disabled(empty($carrito))
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg
                                   bg-cerberus-primary hover:bg-cerberus-primary/90 disabled:opacity-40 disabled:cursor-not-allowed
                                   text-white text-sm font-medium transition-colors">
                            <span wire:loading wire:target="confirmar" class="material-icons text-base animate-spin">sync</span>
                            <span wire:loading.remove wire:target="confirmar" class="material-icons text-base">check_circle</span>
                            Confirmar traslado
                        </button>
                        <button type="button"
                            wire:click="volverPaso1"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg
                                   border border-gray-200 dark:border-cerberus-steel
                                   text-gray-600 dark:text-cerberus-light text-sm
                                   hover:bg-gray-50 dark:hover:bg-cerberus-dark transition-colors">
                            <span class="material-icons text-base">arrow_back</span>
                            Volver
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
