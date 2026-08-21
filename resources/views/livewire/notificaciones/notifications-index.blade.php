<div class="space-y-4">

    {{-- Barra de filtros --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex items-center gap-2">
            @foreach(['todas' => 'Todas', 'no_leidas' => 'No leídas', 'leidas' => 'Leídas'] as $val => $label)
                <button wire:click="$set('filtro', '{{ $val }}')"
                        class="px-3 py-1.5 text-xs rounded-lg border transition-colors
                               {{ $filtro === $val
                                    ? 'bg-cerberus-light/20 text-cerberus-light border-cerberus-light/40'
                                    : 'text-cerberus-accent border-cerberus-steel hover:border-cerberus-light/40 bg-cerberus-mid' }}">
                    {{ $label }}
                    @if($val === 'no_leidas' && $totalNoLeidas > 0)
                        <span class="ml-1 px-1.5 py-0.5 bg-red-500/20 text-red-400 rounded-full text-xs">{{ $totalNoLeidas }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="flex-1 sm:max-w-xs">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-icons text-sm text-cerberus-steel">search</span>
                <input wire:model.live.debounce.300ms="busqueda"
                       placeholder="Buscar notificaciones…"
                       class="w-full pl-9 pr-4 py-1.5 text-xs bg-cerberus-mid border border-cerberus-steel rounded-lg
                              text-gray-900 dark:text-white placeholder-cerberus-steel focus:outline-none focus:border-cerberus-light/40">
            </div>
        </div>

        @if($totalNoLeidas > 0)
            <button wire:click="markAllRead"
                    class="ml-auto flex items-center gap-1.5 px-3 py-1.5 text-xs bg-cerberus-mid border border-cerberus-steel
                           rounded-lg text-cerberus-accent hover:text-cerberus-light hover:border-cerberus-light/40 transition-colors">
                <span class="material-icons text-sm">done_all</span>
                Marcar todas leídas
            </button>
        @endif
    </div>

    {{-- Lista --}}
    <div class="rounded-xl border border-cerberus-steel overflow-hidden">
        @forelse($notificaciones as $notif)
            @php
                $data   = $notif->data;
                $color  = $data['color'] ?? 'blue';
                $icono  = $data['icono'] ?? 'notifications';
                $titulo = $data['titulo'] ?? 'Notificación';
                $msg    = $data['mensaje'] ?? '';
                $url    = $data['url'] ?? null;
                $leida  = $notif->read_at !== null;
                $colorMap = [
                    'emerald' => 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20',
                    'blue'    => 'text-blue-400 bg-blue-400/10 border-blue-400/20',
                    'amber'   => 'text-amber-400 bg-amber-400/10 border-amber-400/20',
                    'red'     => 'text-red-400 bg-red-400/10 border-red-400/20',
                    'orange'  => 'text-orange-400 bg-orange-400/10 border-orange-400/20',
                ];
                $cls = $colorMap[$color] ?? 'text-cerberus-accent bg-cerberus-mid border-cerberus-steel';
            @endphp
            <div class="flex items-start gap-4 px-5 py-4 border-b border-cerberus-steel/50 last:border-0
                        bg-cerberus-dark hover:bg-cerberus-mid/50 transition-colors
                        {{ $leida ? 'opacity-70' : '' }}">

                {{-- Ícono --}}
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center border {{ $cls }}">
                    <span class="material-icons text-lg">{{ $icono }}</span>
                </div>

                {{-- Contenido --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $titulo }}</p>
                            <p class="text-xs text-cerberus-accent mt-0.5">{{ $msg }}</p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            @if(!$leida)
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-xs text-cerberus-steel">{{ $notif->created_at->format('d/m/Y H:i') }}</span>
                        <span class="text-cerberus-steel">·</span>
                        <span class="text-xs text-cerberus-steel">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    @if($url)
                        <a href="{{ $url }}" wire:navigate
                           wire:click="markRead('{{ $notif->id }}')"
                           class="p-1.5 rounded-lg text-cerberus-accent hover:text-cerberus-light hover:bg-cerberus-mid transition-colors"
                           title="Ir">
                            <span class="material-icons text-base">open_in_new</span>
                        </a>
                    @endif
                    @if(!$leida)
                        <button wire:click="markRead('{{ $notif->id }}')"
                                class="p-1.5 rounded-lg text-cerberus-accent hover:text-emerald-400 hover:bg-cerberus-mid transition-colors"
                                title="Marcar leída">
                            <span class="material-icons text-base">check</span>
                        </button>
                    @endif
                    <button wire:click="deleteNotification('{{ $notif->id }}')"
                            wire:confirm="¿Eliminar esta notificación?"
                            class="p-1.5 rounded-lg text-cerberus-accent hover:text-red-400 hover:bg-cerberus-mid transition-colors"
                            title="Eliminar">
                        <span class="material-icons text-base">delete_outline</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="py-16 text-center">
                <span class="material-icons text-4xl text-cerberus-steel">notifications_none</span>
                <p class="text-cerberus-accent text-sm mt-3">No hay notificaciones aquí.</p>
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if($notificaciones->hasPages())
        <div class="mt-4">
            {{ $notificaciones->links() }}
        </div>
    @endif
</div>
