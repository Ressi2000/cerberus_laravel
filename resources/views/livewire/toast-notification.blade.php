{{--
    Toast Notification — Componente global de notificaciones
    ─────────────────────────────────────────────────────────────────────────
    Posición: esquina inferior-derecha (fixed).
    Apila múltiples toasts en orden cronológico (el más nuevo al fondo).
    Cada toast se auto-cierra a los 5 s y tiene botón de cierre manual.
    La barra de progreso refleja el tiempo restante.

    Tipos soportados: success | error | warning | info
    ─────────────────────────────────────────────────────────────────────────
--}}
<div
    class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-3 w-80 pointer-events-none"
    aria-live="polite"
    aria-atomic="false"
>
    @foreach ($toasts as $toast)
        <div
            wire:key="toast-{{ $toast['id'] }}"
            x-data="{
                visible: false,
                progress: 100,
                timer: null,
                progressTimer: null,
                duration: 5000,

                init() {
                    this.$nextTick(() => { this.visible = true });

                    // Barra de progreso
                    const step = 50;
                    this.progressTimer = setInterval(() => {
                        this.progress -= (step / this.duration) * 100;
                        if (this.progress <= 0) this.close();
                    }, step);

                    // Auto-cierre
                    this.timer = setTimeout(() => this.close(), this.duration);
                },

                close() {
                    clearTimeout(this.timer);
                    clearInterval(this.progressTimer);
                    this.visible = false;
                    setTimeout(() => $wire.remove({{ $toast['id'] }}), 350);
                }
            }"
            x-show="visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="pointer-events-auto w-full rounded-xl overflow-hidden shadow-lg border
                   @if($toast['type'] === 'success') bg-white dark:bg-cerberus-mid border-green-400/50
                   @elseif($toast['type'] === 'error') bg-white dark:bg-cerberus-mid border-red-400/50
                   @elseif($toast['type'] === 'warning') bg-white dark:bg-cerberus-mid border-yellow-400/50
                   @else bg-white dark:bg-cerberus-mid border-blue-400/50
                   @endif"
            role="alert"
        >
            {{-- Cuerpo --}}
            <div class="flex items-start gap-3 px-4 pt-4 pb-3">

                {{-- Ícono --}}
                <span class="material-icons text-xl flex-shrink-0 mt-0.5
                    @if($toast['type'] === 'success') text-green-500
                    @elseif($toast['type'] === 'error') text-red-500
                    @elseif($toast['type'] === 'warning') text-yellow-500
                    @else text-blue-500
                    @endif">
                    @if($toast['type'] === 'success') check_circle
                    @elseif($toast['type'] === 'error') error
                    @elseif($toast['type'] === 'warning') warning
                    @else info
                    @endif
                </span>

                {{-- Mensaje --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white leading-snug">
                        @if($toast['type'] === 'success') Operación exitosa
                        @elseif($toast['type'] === 'error') Error
                        @elseif($toast['type'] === 'warning') Advertencia
                        @else Información
                        @endif
                    </p>
                    <p class="text-sm text-gray-600 dark:text-cerberus-light mt-0.5 break-words">
                        {{ $toast['message'] }}
                    </p>
                </div>

                {{-- Botón cerrar --}}
                <button
                    @click="close()"
                    class="flex-shrink-0 text-gray-400 dark:text-cerberus-steel
                           hover:text-gray-600 dark:hover:text-white
                           transition-colors duration-150 mt-0.5"
                    aria-label="Cerrar notificación"
                >
                    <span class="material-icons text-base">close</span>
                </button>
            </div>

            {{-- Barra de progreso --}}
            <div class="h-1 w-full bg-gray-100 dark:bg-cerberus-dark/50">
                <div
                    class="h-full transition-all ease-linear
                        @if($toast['type'] === 'success') bg-green-500
                        @elseif($toast['type'] === 'error') bg-red-500
                        @elseif($toast['type'] === 'warning') bg-yellow-500
                        @else bg-blue-500
                        @endif"
                    :style="'width: ' + progress + '%'"
                ></div>
            </div>
        </div>
    @endforeach
</div>