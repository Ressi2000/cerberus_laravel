@props([
    'titulo'    => 'Esta sección está en mejora',
    'mensaje'   => 'Estamos trabajando en mejoras para este módulo. Vuelve a intentarlo más adelante.',
    'icon'      => 'construction',
])

<div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel
            rounded-xl shadow-sm dark:shadow-cerberus p-12 flex flex-col items-center justify-center text-center">
    <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-500/15
                border border-amber-200 dark:border-amber-500/30
                flex items-center justify-center mb-4">
        <span class="material-icons text-3xl text-amber-500 dark:text-amber-400">{{ $icon }}</span>
    </div>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $titulo }}</h2>
    <p class="text-sm text-gray-500 dark:text-cerberus-light max-w-md">{{ $mensaje }}</p>
</div>
