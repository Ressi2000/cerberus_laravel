<div>
    @if ($show && $log)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60">
            <div class="bg-cerberus-mid rounded-xl shadow-cerberus max-w-2xl w-full p-6">

                <h3 class="text-lg font-semibold text-white mb-4">
                    Auditoría #{{ $log['id'] }}
                </h3>

                <p><strong>Usuario:</strong> {{ $log['usuario'] }}</p>
                <p><strong>Tabla:</strong> {{ $log['tabla'] }}</p>
                <p><strong>Acción:</strong> {{ $log['accion'] }}</p>
                <p><strong>Fecha:</strong> {{ $log['fecha'] }}</p>

                <div class="space-y-2 max-h-96 overflow-auto text-sm mt-3">
                    @foreach ($log['cambios'] as $campo => $values)
                        <div class="border border-cerberus-steel rounded-lg p-3">
                            <div class="text-cerberus-accent font-semibold mb-1">{{ $campo }}</div>

                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <div class="text-red-400 mb-1">Antes</div>
                                    <div class="bg-black/60 p-2 rounded font-mono break-all">
                                        {{ is_array($values['before']) ? json_encode($values['before'], JSON_UNESCAPED_UNICODE) : $values['before'] }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-green-400 mb-1">Después</div>
                                    <div class="bg-black/60 p-2 rounded font-mono break-all">
                                        {{ is_array($values['after']) ? json_encode($values['after'], JSON_UNESCAPED_UNICODE) : $values['after'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 text-right">
                    <button wire:click="close"
                        class="px-4 py-2 bg-cerberus-steel hover:bg-cerberus-dark text-white rounded-lg">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    @endif
</div>
