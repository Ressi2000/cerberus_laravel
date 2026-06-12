<div x-data="{ open: false }" @click.outside="open = false" class="relative" wire:poll.30s="refresh">

    {{-- Campana --}}
    <button @click="open = !open; if(open) $dispatch('bell-opened')"
            class="relative flex items-center justify-center w-9 h-9 rounded-xl bg-cerberus-mid
                   border border-cerberus-steel hover:border-cerberus-light/40 transition-colors">
        <span class="material-icons text-lg text-cerberus-accent">
            {{ $unreadCount > 0 ? 'notifications_active' : 'notifications_none' }}
        </span>
        @if($unreadCount > 0)
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-cerberus-mid animate-pulse"></span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-cerberus-dark border border-cerberus-steel rounded-xl shadow-cerberus z-50"
         style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-cerberus-steel">
            <div class="flex items-center gap-2">
                <span class="material-icons text-base text-cerberus-accent">notifications</span>
                <span class="text-sm font-semibold text-white">Notificaciones</span>
                @if($unreadCount > 0)
                    <span class="px-1.5 py-0.5 text-xs bg-red-500/20 text-red-400 rounded-full font-semibold">
                        {{ $unreadCount }}
                    </span>
                @endif
            </div>
            @if($unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-cerberus-accent hover:text-cerberus-light transition-colors">
                    Marcar leídas
                </button>
            @endif
        </div>

        {{-- Lista --}}
        <div class="max-h-80 overflow-y-auto">
            @forelse($recientes as $notif)
                @php
                    $data   = $notif->data;
                    $color  = $data['color'] ?? 'blue';
                    $icono  = $data['icono'] ?? 'notifications';
                    $titulo = $data['titulo'] ?? 'Notificación';
                    $msg    = $data['mensaje'] ?? '';
                    $url    = $data['url'] ?? null;
                    $leida  = $notif->read_at !== null;
                    $colorMap = [
                        'emerald' => 'text-emerald-400 bg-emerald-400/10',
                        'blue'    => 'text-blue-400 bg-blue-400/10',
                        'amber'   => 'text-amber-400 bg-amber-400/10',
                        'red'     => 'text-red-400 bg-red-400/10',
                        'orange'  => 'text-orange-400 bg-orange-400/10',
                    ];
                    $cls = $colorMap[$color] ?? 'text-cerberus-accent bg-cerberus-mid';
                @endphp
                <a href="{{ $url ?? '#' }}"
                   @if($url) wire:navigate @endif
                   wire:click="markRead('{{ $notif->id }}')"
                   @click="open = false"
                   class="flex items-start gap-3 px-4 py-3 hover:bg-cerberus-mid transition-colors border-b border-cerberus-steel/50 last:border-0
                          {{ $leida ? 'opacity-60' : '' }}">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center {{ $cls }}">
                        <span class="material-icons text-sm">{{ $icono }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-white truncate">{{ $titulo }}</p>
                        <p class="text-xs text-cerberus-accent mt-0.5 line-clamp-2">{{ $msg }}</p>
                        <p class="text-xs text-cerberus-steel mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                    </div>
                    @if(!$leida)
                        <div class="flex-shrink-0 w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5"></div>
                    @endif
                </a>
            @empty
                <div class="px-4 py-8 text-center">
                    <span class="material-icons text-3xl text-cerberus-steel">notifications_none</span>
                    <p class="text-xs text-cerberus-accent mt-2">Sin notificaciones</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="px-4 py-2.5 border-t border-cerberus-steel">
            <a href="{{ route('notificaciones.index') }}" wire:navigate @click="open = false"
               class="block text-center text-xs text-cerberus-accent hover:text-cerberus-light transition-colors">
                Ver todas las notificaciones →
            </a>
        </div>
    </div>
</div>
