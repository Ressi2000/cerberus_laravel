<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
        @foreach (['todas' => 'Todas', 'sin_respuesta' => 'Sin respuesta', 'errores' => 'Con error'] as $clave => $label)
            <button type="button" wire:click="$set('filtro', '{{ $clave }}')"
                @class([
                    'px-3 py-1.5 rounded-lg text-sm border transition',
                    'bg-cerberus-primary text-white border-cerberus-primary' => $filtro === $clave,
                    'bg-white dark:bg-cerberus-mid text-gray-700 dark:text-white border-gray-200 dark:border-cerberus-steel' => $filtro !== $clave,
                ])>
                {{ $label }} <span class="opacity-70">({{ $totales[$clave] }})</span>
            </button>
        @endforeach
        <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar en las preguntas…"
            class="ml-auto w-full sm:w-64 rounded-lg text-sm border-gray-300 dark:border-cerberus-steel bg-white dark:bg-cerberus-dark text-gray-900 dark:text-white">
    </div>

    <p class="text-sm text-gray-500 dark:text-cerberus-light">
        Las preguntas <strong>sin respuesta</strong> son las que el bot no pudo contestar con las consultas que tiene:
        indican qué conviene agregarle.
    </p>

    <div class="bg-white dark:bg-cerberus-mid rounded-xl border border-gray-200 dark:border-cerberus-steel divide-y divide-gray-100 dark:divide-cerberus-steel/50">
        @forelse ($consultas as $c)
            <div wire:key="c-{{ $c->id }}">
                <button type="button" wire:click="alternar({{ $c->id }})" class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-cerberus-dark/40">
                    <span @class([
                        'material-icons text-lg mt-0.5',
                        'text-red-500' => $c->error,
                        'text-amber-500' => ! $c->error && $c->sin_respuesta,
                        'text-green-500' => ! $c->error && ! $c->sin_respuesta,
                    ])>{{ $c->error ? 'error' : ($c->sin_respuesta ? 'help' : 'check_circle') }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $c->pregunta }}</p>
                        <p class="text-xs text-gray-500 dark:text-cerberus-light mt-0.5">
                            {{ $c->created_at->format('d/m/Y H:i') }} · {{ $c->usuario?->name }}
                            @if ($c->empresa) · {{ $c->empresa->nombre }} @endif
                            @if ($c->duracion_ms) · {{ number_format($c->duracion_ms / 1000, 1) }} s @endif
                            @if ($c->herramientas) · {{ count($c->herramientas) }} consulta(s) @endif
                        </p>
                    </div>
                    <span class="material-icons text-gray-400">{{ $abierta === $c->id ? 'expand_less' : 'expand_more' }}</span>
                </button>
                @if ($abierta === $c->id)
                    <div class="px-4 pb-4 pl-12 space-y-3 text-sm">
                        @if ($c->error)
                            <p class="text-red-600 dark:text-red-400">{{ $c->error }}</p>
                        @else
                            <div class="cerberus-bot-md rounded-lg bg-gray-50 dark:bg-cerberus-dark p-3 text-gray-800 dark:text-white">
                                {!! \Illuminate\Support\Str::markdown((string) $c->respuesta, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                            </div>
                        @endif
                        @if ($c->herramientas)
                            <div>
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-cerberus-light mb-1">Consultas que hizo</p>
                                <ul class="space-y-1">
                                    @foreach ($c->herramientas as $h)
                                        <li class="font-mono text-xs text-gray-600 dark:text-cerberus-light">
                                            <span @class(['text-green-600' => $h['ok'] ?? false, 'text-red-500' => ! ($h['ok'] ?? false)])>●</span>
                                            {{ $h['nombre'] }}({{ json_encode($h['args'] ?? [], JSON_UNESCAPED_UNICODE) }})
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($c->tiempos)
                            <div>
                                <p class="text-xs font-semibold uppercase text-gray-500 dark:text-cerberus-light mb-1">Dónde se fue el tiempo</p>
                                <ul class="space-y-0.5">
                                    @foreach ($c->tiempos as $t)
                                        <li class="font-mono text-xs text-gray-600 dark:text-cerberus-light">
                                            {{ str_pad(number_format(($t['ms'] ?? 0) / 1000, 1), 6, ' ', STR_PAD_LEFT) }} s · {{ $t['que'] ?? '' }}
                                            @if (isset($t['status']) && $t['status'] !== 200) <span class="text-red-500">({{ $t['status'] }})</span> @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <p class="text-xs text-gray-400">Modelo: {{ $c->modelo ?? '—' }}</p>
                    </div>
                @endif
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-gray-500 dark:text-cerberus-light">Todavía no hay preguntas registradas.</p>
        @endforelse
    </div>

    {{ $consultas->links() }}

    <style>
        .cerberus-bot-md > * + * { margin-top: .5rem; }
        .cerberus-bot-md ul { list-style: disc; padding-left: 1.1rem; }
        .cerberus-bot-md ol { list-style: decimal; padding-left: 1.1rem; }
        .cerberus-bot-md a { color: rgb(var(--cerberus-accent)); text-decoration: underline; }
        .cerberus-bot-md h3 { font-weight: 600; }
        .cerberus-bot-md table { font-size: .75rem; border-collapse: collapse; }
        .cerberus-bot-md th, .cerberus-bot-md td { border: 1px solid rgba(127,127,127,.3); padding: .2rem .4rem; }
    </style>
</div>
