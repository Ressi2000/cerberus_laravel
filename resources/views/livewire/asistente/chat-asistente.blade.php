{{-- Cerberus Bot: botón flotante + ventana de chat (solo si está habilitado). --}}
<div x-data="{
        abierto: $wire.entangle('abierto'),
        bajar() { this.$nextTick(() => { const c = this.$refs.mensajes; if (c && !c.querySelector('[data-vacio]')) c.scrollTop = c.scrollHeight }) }
     }"
     x-init="$watch('abierto', v => { if (v) { bajar(); $nextTick(() => $refs.entrada?.focus()) } })"
     x-on:cerberus-bot-respondio.window="bajar()"
     class="fixed bottom-4 right-4 z-40 print:hidden">

    {{-- Botón flotante --}}
    <button type="button" x-show="!abierto" x-on:click="abierto = true"
        class="group relative flex items-center gap-2 rounded-full bg-[#0D1B2A] pl-1 pr-4 py-1 shadow-xl
               ring-2 ring-cerberus-accent/60 hover:ring-cerberus-accent transition"
        title="Pregúntale a {{ $nombre }}">
        <img src="{{ $animado ?? $avatar }}" alt="" class="h-11 w-11 rounded-full object-cover">
        <span class="text-sm font-semibold text-white hidden sm:inline">{{ $nombre }}</span>
        <span class="absolute -top-1 -left-1 rounded-full bg-amber-400 px-1.5 text-[10px] font-bold text-gray-900">BETA</span>
    </button>

    {{-- Ventana --}}
    <div x-show="abierto" x-cloak x-transition.origin.bottom.right
         class="fixed inset-0 sm:inset-auto sm:bottom-4 sm:right-4 sm:w-[400px] sm:h-[600px] sm:max-h-[calc(100vh-2rem)]
                flex flex-col bg-white dark:bg-cerberus-mid sm:rounded-2xl shadow-2xl
                border border-gray-200 dark:border-cerberus-steel overflow-hidden">

        {{-- Encabezado --}}
        <div class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-[#0D1B2A] to-[#1B3A5C] text-white flex-shrink-0">
            <img src="{{ $avatar }}" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-cerberus-accent/70">
            <div class="min-w-0 flex-1">
                <p class="font-semibold leading-tight">{{ $nombre }} <span class="ml-1 rounded bg-amber-400 px-1 text-[10px] font-bold text-gray-900 align-middle">BETA</span></p>
                <p class="text-xs text-slate-300">Consultas del inventario · solo lectura</p>
            </div>
            @if (auth()->user()->hasRole('Administrador'))
                <a href="{{ route('admin.asistente.consultas') }}" wire:navigate title="Bitácora: qué le han preguntado"
                    class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10">
                    <span class="material-icons text-xl">history</span>
                </a>
            @endif
            <button type="button" wire:click="nuevaConversacion" title="Nueva conversación"
                class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10">
                <span class="material-icons text-xl">add_comment</span>
            </button>
            <button type="button" x-on:click="abierto = false" title="Cerrar"
                class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10">
                <span class="material-icons text-xl">close</span>
            </button>
        </div>

        {{-- Mensajes --}}
        <div x-ref="mensajes" class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-gray-50 dark:bg-cerberus-dark">
            @if (empty($mensajes))
                <div data-vacio class="text-center pt-2">
                    <img src="{{ $animado ?? $avatar }}" alt="" class="mx-auto h-20 w-20 rounded-full object-cover shadow-lg ring-4 ring-cerberus-accent/30">
                    <p class="mt-3 font-semibold text-gray-900 dark:text-white">¡Hola, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}!</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-cerberus-light px-4">Pregúntame lo que necesites saber del inventario. Por ejemplo:</p>
                    <div class="mt-4 space-y-2 text-left">
                        @foreach ([
                            '¿Qué reparaciones están abiertas?',
                            'Hazme un informe del historial del equipo EQ-00057',
                            'Excel de las laptops con menos de 8 GB de RAM',
                            'Gráfico de los equipos por estado',
                            '¿Qué laptops habría que renovar?',
                        ] as $ejemplo)
                            <button type="button" wire:click="$set('pregunta', @js($ejemplo))" x-on:click="$nextTick(() => $refs.entrada.focus())"
                                class="w-full rounded-lg border border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid
                                       px-3 py-2 text-sm text-gray-700 dark:text-white hover:border-cerberus-accent transition">
                                {{ $ejemplo }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($mensajes as $i => $m)
                <div wire:key="msg-{{ $i }}" @class(['flex gap-2', 'justify-end' => $m['rol'] === 'usuario'])>
                    @if ($m['rol'] !== 'usuario')
                        <img src="{{ $avatar }}" alt="" class="h-7 w-7 rounded-full object-cover flex-shrink-0 mt-1">
                    @endif
                    <div @class([
                        'max-w-[85%] rounded-2xl px-3 py-2 text-sm',
                        'bg-cerberus-primary text-white rounded-br-sm' => $m['rol'] === 'usuario',
                        'bg-white dark:bg-cerberus-mid text-gray-800 dark:text-white border border-gray-200 dark:border-cerberus-steel rounded-bl-sm' => $m['rol'] === 'bot',
                        'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-500/30' => $m['rol'] === 'error',
                    ])>
                        @if ($m['rol'] === 'bot')
                            <div class="cerberus-bot-md">{!! \Illuminate\Support\Str::markdown($m['texto'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                            @foreach ($m['graficos'] ?? [] as $g => $grafico)
                                <div class="mt-2 rounded-lg border border-gray-200 dark:border-cerberus-steel bg-gray-50 dark:bg-cerberus-dark p-2"
                                     wire:key="graf-{{ $i }}-{{ $g }}" wire:ignore>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-white mb-1">{{ $grafico['titulo'] }}</p>
                                    <div x-data="graficoBot(@js($grafico))" style="height: {{ ($grafico['tipo'] === 'barras' && count($grafico['labels']) > 6) ? max(160, count($grafico['labels']) * 22) : 190 }}px">
                                        <canvas x-ref="lienzo"></canvas>
                                    </div>
                                </div>
                            @endforeach
                            @foreach ($m['adjuntos'] ?? [] as $adj)
                                <a href="{{ $adj['url'] }}" target="_blank"
                                    class="mt-2 flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition
                                           {{ $adj['tipo'] === 'Excel'
                                               ? 'border-green-300 dark:border-green-700/50 bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300 hover:bg-green-100'
                                               : 'border-red-300 dark:border-red-700/50 bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 hover:bg-red-100' }}">
                                    <span class="material-icons text-lg">{{ $adj['tipo'] === 'Excel' ? 'table_view' : 'picture_as_pdf' }}</span>
                                    <span class="min-w-0 flex-1 truncate">Descargar {{ $adj['tipo'] }}<br><span class="font-normal opacity-75">{{ $adj['archivo'] }}</span></span>
                                    <span class="material-icons text-base">download</span>
                                </a>
                            @endforeach
                            @if (! empty($m['consultas']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($m['consultas'] as $c)
                                        <span class="inline-flex items-center gap-0.5 rounded-full bg-gray-100 dark:bg-cerberus-dark px-2 py-0.5 text-[10px] text-gray-500 dark:text-cerberus-light">
                                            <span class="material-icons" style="font-size: 11px">search</span>{{ $c }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="whitespace-pre-line">{{ $m['texto'] }}</p>
                        @endif
                        <p @class(['mt-1 text-[10px]', 'text-blue-100 text-right' => $m['rol'] === 'usuario', 'text-gray-400' => $m['rol'] !== 'usuario'])>{{ $m['hora'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach

            @if ($pensando)
                <div class="flex gap-2">
                    <img src="{{ $avatar }}" alt="" class="h-7 w-7 rounded-full object-cover flex-shrink-0">
                    <div class="rounded-2xl rounded-bl-sm bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel px-3 py-2 text-sm text-gray-500 dark:text-cerberus-light flex items-center gap-2">
                        <span class="flex gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-cerberus-accent animate-bounce"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-cerberus-accent animate-bounce" style="animation-delay: .15s"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-cerberus-accent animate-bounce" style="animation-delay: .3s"></span>
                        </span>
                        Revisando el inventario…
                    </div>
                </div>
            @endif
        </div>

        {{-- Entrada --}}
        <form wire:submit="enviar" class="flex-shrink-0 border-t border-gray-200 dark:border-cerberus-steel p-3 bg-white dark:bg-cerberus-mid">
            @error('pregunta') <p class="text-xs text-red-500 mb-1">{{ $message }}</p> @enderror
            <div class="flex items-end gap-2">
                <textarea x-ref="entrada" wire:model="pregunta" rows="1" maxlength="1000"
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                    @disabled($pensando)
                    placeholder="Escribe tu pregunta…"
                    class="flex-1 resize-none rounded-xl border-gray-300 dark:border-cerberus-steel bg-white dark:bg-cerberus-dark
                           text-sm text-gray-900 dark:text-white focus:border-cerberus-accent focus:ring-cerberus-accent"></textarea>
                <button type="submit" @disabled($pensando) title="Enviar"
                    class="h-10 w-10 flex-shrink-0 rounded-xl bg-cerberus-primary text-white flex items-center justify-center
                           hover:opacity-90 disabled:opacity-50 transition">
                    <span class="material-icons text-xl">send</span>
                </button>
            </div>
            <p class="mt-1.5 text-[10px] text-gray-400 dark:text-cerberus-steel text-center">
                Asistente en prueba. Puede equivocarse: verifica en las pantallas antes de decidir.
            </p>
        </form>
    </div>

    <style>
        .cerberus-bot-md > * + * { margin-top: .5rem; }
        .cerberus-bot-md h1, .cerberus-bot-md h2, .cerberus-bot-md h3 { font-weight: 600; font-size: .9rem; }
        .cerberus-bot-md ul { list-style: disc; padding-left: 1.1rem; }
        .cerberus-bot-md ol { list-style: decimal; padding-left: 1.1rem; }
        .cerberus-bot-md li + li { margin-top: .15rem; }
        .cerberus-bot-md a { color: rgb(var(--cerberus-accent)); text-decoration: underline; }
        .cerberus-bot-md strong { font-weight: 600; }
        .cerberus-bot-md code { font-size: .8em; padding: 0 .25rem; border-radius: .25rem; background: rgba(127,127,127,.15); }
        .cerberus-bot-md table { width: 100%; font-size: .75rem; border-collapse: collapse; display: block; overflow-x: auto; }
        .cerberus-bot-md th, .cerberus-bot-md td { border: 1px solid rgba(127,127,127,.3); padding: .2rem .4rem; text-align: left; }
        .cerberus-bot-md th { background: rgba(127,127,127,.1); }
    </style>
</div>
