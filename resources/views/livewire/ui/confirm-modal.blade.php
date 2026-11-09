<div>
    @if ($open)
        @php
            $tema = match ($variant) {
                'warning' => [
                    'icon'   => 'warning',
                    'iconBg' => 'text-yellow-400',
                    'btn'    => 'bg-yellow-600 hover:bg-yellow-700',
                ],
                'primary' => [
                    'icon'   => 'help_outline',
                    'iconBg' => 'text-[#1E40AF] dark:text-cerberus-accent',
                    'btn'    => 'bg-[#1E40AF] hover:bg-[#1E3A8A]',
                ],
                default => [
                    'icon'   => 'delete',
                    'iconBg' => 'text-red-500',
                    'btn'    => 'bg-red-600 hover:bg-red-700',
                ],
            };
        @endphp

        <div class="fixed inset-0 z-[9999] flex items-center justify-center">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="cerrar"></div>

            <div class="relative z-[9999] w-full max-w-md mx-4 p-6 bg-white dark:bg-cerberus-mid
                        border border-gray-200 dark:border-cerberus-steel rounded-xl shadow-xl">

                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <span class="material-icons {{ $tema['iconBg'] }}">{{ $tema['icon'] }}</span>
                    {{ $titulo }}
                </h2>

                <p class="text-sm text-gray-600 dark:text-cerberus-light">
                    {{ $mensaje }}
                </p>

                <div class="flex justify-end gap-3 mt-5">
                    <button wire:click="cerrar"
                        class="px-4 py-2 text-sm rounded-lg bg-gray-100 dark:bg-cerberus-steel/30
                               text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-cerberus-steel/50 transition">
                        {{ $cancelLabel }}
                    </button>

                    <button wire:click="confirmar" wire:loading.attr="disabled" wire:target="confirmar"
                        class="px-4 py-2 text-sm rounded-lg font-medium text-white transition
                               flex items-center gap-2 disabled:opacity-60 {{ $tema['btn'] }}">
                        <span wire:loading.remove wire:target="confirmar">{{ $confirmLabel }}</span>
                        <span wire:loading wire:target="confirmar" class="material-icons text-sm animate-spin">refresh</span>
                        <span wire:loading wire:target="confirmar">Procesando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
