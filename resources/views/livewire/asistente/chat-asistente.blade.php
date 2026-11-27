{{-- Cerberus Bot: ventana flotante o vista de pantalla completa (solo si está habilitado). --}}
@php $completo = $pantallaCompleta; @endphp
<div x-data="{
        abierto: {{ $completo ? 'true' : "\$wire.entangle('abierto')" }},
        bajar() { this.$nextTick(() => { const c = this.$refs.mensajes; if (c && !c.querySelector('[data-vacio]')) c.scrollTop = c.scrollHeight }) }
     }"
     x-init="if (abierto) { bajar(); $nextTick(() => $refs.entrada?.focus()) }; $watch('abierto', v => { if (v) { bajar(); $nextTick(() => $refs.entrada?.focus()) } })"
     x-on:cerberus-bot-respondio.window="bajar()"
     @class([
        'fixed bottom-4 right-4 z-40 print:hidden' => ! $completo,
        'w-full max-w-5xl mx-auto' => $completo,
     ])>

    {{-- Botón flotante (solo fuera de pantalla completa) --}}
    @unless ($completo)
        <button type="button" x-show="!abierto" x-on:click="abierto = true"
            class="group relative flex items-center gap-2 rounded-full bg-[#0D1B2A] pl-1 pr-4 py-1 shadow-xl
                   ring-2 ring-cerberus-accent/60 hover:ring-cerberus-accent transition"
            title="Pregúntale a {{ $nombre }}">
            <img src="{{ $animado ?? $avatar }}" alt="" class="h-11 w-11 rounded-full object-cover">
            <span class="text-sm font-semibold text-white hidden sm:inline">{{ $nombre }}</span>
            <span class="absolute -top-1 -left-1 rounded-full bg-amber-400 px-1.5 text-[10px] font-bold text-gray-900">BETA</span>
        </button>
    @endunless

    {{-- Contenedor del chat --}}
    <div @unless ($completo) x-show="abierto" x-cloak x-transition.origin.bottom.right @endunless
         @class([
            'flex flex-col bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel overflow-hidden',
            'fixed inset-0 sm:inset-auto sm:bottom-4 sm:right-4 sm:w-[420px] sm:h-[620px] sm:max-h-[calc(100vh-2rem)] sm:rounded-2xl shadow-2xl' => ! $completo,
            'h-[calc(100vh-11.5rem)] min-h-[540px] rounded-2xl shadow-lg' => $completo,
         ])>

        {{-- Encabezado --}}
        <div class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-[#0D1B2A] to-[#1B3A5C] text-white flex-shrink-0">
            <img src="{{ $avatar }}" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-cerberus-accent/70">
            <div class="min-w-0 flex-1">
                <p class="font-semibold leading-tight">{{ $nombre }} <span class="ml-1 rounded bg-amber-400 px-1 text-[10px] font-bold text-gray-900 align-middle">BETA</span></p>
                <p class="text-xs text-slate-300">Consultas del inventario · solo lectura</p>
            </div>
            @unless ($completo)
                <a href="{{ route('asistente.index') }}" title="Abrir en pantalla completa"
                    class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10">
                    <span class="material-icons text-xl">open_in_full</span>
                </a>
            @endunless
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
            @unless ($completo)
                <button type="button" x-on:click="abierto = false" title="Cerrar"
                    class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10">
                    <span class="material-icons text-xl">close</span>
                </button>
            @endunless
        </div>

        {{-- Mensajes --}}
        <div x-ref="mensajes" @class([
            'flex-1 overflow-y-auto space-y-4 bg-gray-50 dark:bg-cerberus-dark',
            'px-4 py-4' => ! $completo,
            'px-4 sm:px-6 py-6' => $completo,
        ])>
            @if (empty($mensajes))
                <div data-vacio @class(['text-center', 'pt-2' => ! $completo, 'pt-6 max-w-2xl mx-auto' => $completo])>
                    <img src="{{ $animado ?? $avatar }}" alt="" @class([
                        'mx-auto rounded-full object-cover shadow-lg ring-4 ring-cerberus-accent/30',
                        'h-20 w-20' => ! $completo,
                        'h-24 w-24' => $completo,
                    ])>
                    <p @class(['mt-3 font-semibold text-gray-900 dark:text-white', 'text-lg' => $completo])>
                        ¡Hola, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}!
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-cerberus-light px-4">Pregúntame lo que necesites saber del inventario. Por ejemplo:</p>
                    <div @class([
                        'mt-5 text-left',
                        'space-y-2' => ! $completo,
                        'grid grid-cols-1 sm:grid-cols-2 gap-2.5' => $completo,
                    ])>
                        @foreach ([
                            '¿Qué reparaciones están abiertas?',
                            'Hazme un informe del historial del equipo EQ-00057',
                            'Excel de las laptops con menos de 8 GB de RAM',
                            'Gráfico de los equipos por estado',
                            '¿Qué laptops habría que renovar?',
                        ] as $ejemplo)
                            <button type="button" wire:click="$set('pregunta', @js($ejemplo))" x-on:click="$nextTick(() => $refs.entrada.focus())"
                                class="w-full rounded-lg border border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid
                                       px-3 py-2.5 text-sm text-gray-700 dark:text-white hover:border-cerberus-accent transition text-left">
                                {{ $ejemplo }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($mensajes as $i => $m)
                @php $tieneGraficos = ! empty($m['graficos']); @endphp
                <div wire:key="msg-{{ $i }}" @class(['flex gap-2.5', 'justify-end' => $m['rol'] === 'usuario'])>
                    @if ($m['rol'] !== 'usuario')
                        <img src="{{ $avatar }}" alt="" class="h-7 w-7 rounded-full object-cover flex-shrink-0 mt-1">
                    @endif
                    <div @class([
                        'rounded-2xl px-3.5 py-2.5 text-sm min-w-0',
                        'max-w-[85%]' => ! $completo && ! $tieneGraficos,
                        'w-full max-w-[90%]' => ! $completo && $tieneGraficos,
                        'max-w-[85%] lg:max-w-[78%]' => $completo && ! $tieneGraficos,
                        'w-full max-w-3xl' => $completo && $tieneGraficos,
                        'bg-cerberus-primary text-white rounded-br-sm' => $m['rol'] === 'usuario',
                        'bg-white dark:bg-cerberus-mid text-gray-800 dark:text-white border border-gray-200 dark:border-cerberus-steel rounded-bl-sm' => $m['rol'] === 'bot',
                        'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-500/30' => $m['rol'] === 'error',
                    ])>
                        @if ($m['rol'] === 'bot')
                            <div class="cerberus-bot-md">{!! \Illuminate\Support\Str::markdown($m['texto'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                            @foreach ($m['graficos'] ?? [] as $g => $grafico)
                                @php
                                    $cantEtiquetas = count($grafico['labels'] ?? []);
                                    $altoGrafico = $completo
                                        ? (($grafico['tipo'] === 'barras' && $cantEtiquetas > 6) ? max(300, $cantEtiquetas * 32) : 340)
                                        : (($grafico['tipo'] === 'barras' && $cantEtiquetas > 6) ? max(200, $cantEtiquetas * 24) : 220);
                                @endphp
                                <div class="mt-3 rounded-xl border border-gray-200 dark:border-cerberus-steel bg-gray-50 dark:bg-cerberus-dark p-3"
                                     wire:key="graf-{{ $i }}-{{ $g }}" wire:ignore
                                     x-data="graficoBot(@js($grafico), @js($completo))"
                                     x-on:keydown.escape.window="if (ampliado) cerrarAmpliado()">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <p class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-white truncate" title="{{ $grafico['titulo'] }}">
                                            {{ $grafico['titulo'] }}
                                        </p>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <button type="button" x-on:click="abrirAmpliado()" title="Ver más grande"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-medium
                                                       text-gray-600 dark:text-cerberus-light hover:text-gray-900 dark:hover:text-white
                                                       bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel/70 transition">
                                                <span class="material-icons text-sm">zoom_in</span>
                                                <span class="hidden sm:inline">Ampliar</span>
                                            </button>
                                            <button type="button" x-on:click="descargar()" title="Descargar imagen PNG en alta resolución"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-medium
                                                       text-white bg-cerberus-primary hover:opacity-90 transition">
                                                <span class="material-icons text-sm">download</span>
                                                <span>PNG</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: {{ $altoGrafico }}px; position: relative;">
                                        <canvas x-ref="lienzo"></canvas>
                                    </div>

                                    {{-- Lightbox para ver el gráfico ampliado --}}
                                    <template x-teleport="body">
                                        <div x-show="ampliado" x-cloak
                                             class="fixed inset-0 z-[70] flex items-center justify-center p-4">
                                            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" x-on:click="cerrarAmpliado()"></div>
                                            <div class="relative z-10 w-full max-w-4xl bg-white dark:bg-cerberus-mid
                                                        border border-gray-200 dark:border-cerberus-steel rounded-2xl shadow-2xl
                                                        flex flex-col max-h-[90vh]">
                                                <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-gray-200 dark:border-cerberus-steel flex-shrink-0">
                                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                                        {{ $grafico['titulo'] }}
                                                    </h3>
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        <button type="button" x-on:click="descargar()"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                                                   bg-[#1E40AF] hover:bg-[#1E3A8A] text-white transition">
                                                            <span class="material-icons text-sm">download</span>
                                                            Descargar PNG (HD)
                                                        </button>
                                                        <button type="button" x-on:click="cerrarAmpliado()" title="Cerrar"
                                                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                                                            <span class="material-icons">close</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="p-5 flex-1 overflow-y-auto">
                                                    <div class="w-full h-[60vh] min-h-[340px] max-h-[560px] relative">
                                                        <canvas x-ref="lienzoAmpliado"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
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
                <div class="flex gap-2.5">
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
        <form wire:submit="enviar" @class([
            'flex-shrink-0 border-t border-gray-200 dark:border-cerberus-steel bg-white dark:bg-cerberus-mid',
            'p-3' => ! $completo,
            'px-4 sm:px-6 py-4' => $completo,
        ])>
            @error('pregunta') <p class="text-xs text-red-500 mb-1">{{ $message }}</p> @enderror
            <div class="flex items-end gap-2">
                <textarea x-ref="entrada" wire:model="pregunta" rows="1" maxlength="1000"
                    x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit() }"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 140) + 'px'"
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
