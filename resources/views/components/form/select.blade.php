@props([
    'name'        => null,
    'label'       => null,
    'options'     => [],
    'selected'    => null,
    'required'    => false,
    'disabled'    => false,
    'hint'        => null,
    'placeholder' => 'Seleccione...',
    'error'       => null,
    'searchable'  => false,
])

@php
    $hasWireModel = $attributes->has('wire:model')
                 || $attributes->has('wire:model.live')
                 || $attributes->has('wire:model.blur')
                 || $attributes->has('wire:model.lazy');

    $fieldId = $name ?? 'field-' . uniqid();
    $hintId  = $fieldId . '-hint';
    $errorId = $fieldId . '-error';

    // Valor inicial para el dropdown searchable (garantizar que sea escalar)
    $rawInitial   = old($name, $selected) ?? '';
    $initialValue = is_array($rawInitial) ? '' : (string) $rawInitial;

    // Convertir opciones a array indexado para Alpine (solo valores escalares)
    $optionsForJs = collect($options)
        ->filter(fn($label) => is_scalar($label) || is_null($label))
        ->map(fn($label, $value) => [
            'value' => (string) $value,
            'label' => (string) ($label ?? ''),
        ])->values()->toArray();
@endphp

<div class="mb-4">

    {{-- ── LABEL ──────────────────────────────────────────────────────────── --}}
    @if ($label)
        <div class="flex items-center gap-1.5 mb-1">
            <label for="{{ $fieldId }}"
                   class="text-sm font-medium text-gray-700 dark:text-cerberus-accent">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500 ml-0.5">*</span>
                @endif
            </label>

            @if ($hint)
                <div class="relative flex items-center group" aria-describedby="{{ $hintId }}">
                    <span class="material-icons text-gray-400 dark:text-cerberus-steel
                                 hover:text-[#1E40AF] dark:hover:text-cerberus-accent
                                 text-[16px] cursor-help transition-colors duration-150
                                 select-none">
                        help_outline
                    </span>

                    <div id="{{ $hintId }}"
                         role="tooltip"
                         class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2
                                w-56 px-3 py-2 rounded-lg shadow-lg
                                bg-[#1E293B] dark:bg-cerberus-dark
                                text-white text-xs leading-relaxed
                                border border-gray-600 dark:border-cerberus-steel
                                opacity-0 invisible pointer-events-none
                                group-hover:opacity-100 group-hover:visible
                                transition-all duration-200 z-50
                                whitespace-normal">
                        {{ $hint }}
                        <span class="absolute top-full left-1/2 -translate-x-1/2
                                     border-4 border-transparent
                                     border-t-[#1E293B] dark:border-t-cerberus-dark">
                        </span>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         SEARCHABLE DROPDOWN (Alpine.js)
    ══════════════════════════════════════════════════════════════════════ --}}
    @if ($searchable)

        @php
            // Separar wire:model del resto de atributos para pasarlo al input oculto
            $wireModelAttr = $attributes->only(['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.lazy']);
            $extraAttrs    = $attributes->except(['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.lazy', 'class']);
        @endphp

        <div
            x-data="{
                open: false,
                search: '',
                value: '{{ $initialValue }}',
                placeholder: '{{ $placeholder }}',
                options: {{ Js::from($optionsForJs) }},

                get filtered() {
                    if (!this.search.trim()) return this.options;
                    const q = this.search.toLowerCase();
                    return this.options.filter(o => o.label.toLowerCase().includes(q));
                },
                get selectedLabel() {
                    const opt = this.options.find(o => o.value === String(this.value));
                    return opt ? opt.label : this.placeholder;
                },
                select(optValue) {
                    this.value = optValue;
                    this.open  = false;
                    this.search = '';
                    // Notifica a Livewire / formularios nativos
                    const inp = this.$refs.hiddenInput;
                    inp.value = optValue;
                    inp.dispatchEvent(new Event('input',  { bubbles: true }));
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                },
                clear() {
                    this.select('');
                }
            }"
            x-on:keydown.escape.window="open = false"
            class="relative"
        >
            {{-- Input oculto que recibe wire:model / name --}}
            <input
                type="hidden"
                id="{{ $fieldId }}"
                @if ($name && !$hasWireModel) name="{{ $name }}" @endif
                @if ($required) required @endif
                x-ref="hiddenInput"
                x-model="value"
                {{ $wireModelAttr }}
                {{ $extraAttrs }}
            >

            {{-- Trigger --}}
            <button
                type="button"
                @click="open = !open"
                @disabled($disabled)
                class="w-full rounded-lg px-4 py-2 pr-9 text-sm text-left transition
                       bg-white dark:bg-cerberus-dark
                       border {{ ($error || (!$hasWireModel && $errors->has($name ?? ''))) ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-cerberus-steel' }}
                       text-[#1E293B] dark:text-white
                       focus:outline-none focus:ring-2
                       focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                       dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                       disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-text="selectedLabel"
                      :class="value === '' ? 'text-gray-400 dark:text-gray-500' : ''">
                </span>
            </button>

            {{-- Chevron --}}
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="material-icons text-gray-400 dark:text-cerberus-steel text-base"
                      :class="{ 'rotate-180': open }"
                      style="transition: transform 0.2s">
                    expand_more
                </span>
            </div>

            {{-- Dropdown panel --}}
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                @click.outside="open = false"
                class="absolute z-50 mt-1 w-full rounded-lg shadow-lg
                       bg-white dark:bg-cerberus-dark
                       border border-gray-200 dark:border-cerberus-steel
                       overflow-hidden"
                style="display: none;"
            >
                {{-- Buscador --}}
                <div class="px-2 pt-2 pb-1 border-b border-gray-100 dark:border-cerberus-steel/40">
                    <div class="relative">
                        <span class="material-icons absolute left-2 top-1/2 -translate-y-1/2
                                     text-gray-400 dark:text-cerberus-steel text-base pointer-events-none">
                            search
                        </span>
                        <input
                            type="text"
                            x-model="search"
                            x-ref="searchInput"
                            x-on:keydown.enter.prevent="filtered.length === 1 && select(filtered[0].value)"
                            placeholder="Buscar..."
                            class="w-full pl-8 pr-3 py-1.5 text-sm rounded-md
                                   bg-gray-50 dark:bg-cerberus-mid
                                   border border-gray-200 dark:border-cerberus-steel/50
                                   text-[#1E293B] dark:text-white
                                   placeholder-gray-400 dark:placeholder-cerberus-steel
                                   focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30"
                            autocomplete="off"
                        >
                    </div>
                </div>

                {{-- Lista de opciones --}}
                <ul class="max-h-52 overflow-y-auto py-1" role="listbox">

                    {{-- Opción vacía --}}
                    <li
                        @click="select('')"
                        :class="value === '' ? 'bg-[#1E40AF]/10 dark:bg-cerberus-primary/20 text-[#1E40AF] dark:text-white' :
                                               'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-cerberus-steel/20'"
                        class="px-4 py-2 text-sm cursor-pointer transition-colors duration-100 italic"
                        role="option"
                    >
                        {{ $placeholder }}
                    </li>

                    <template x-for="opt in filtered" :key="opt.value">
                        <li
                            @click="select(opt.value)"
                            :class="String(value) === opt.value
                                ? 'bg-[#1E40AF]/10 dark:bg-cerberus-primary/20 text-[#1E40AF] dark:text-white font-medium'
                                : 'text-[#1E293B] dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-cerberus-steel/20'"
                            class="px-4 py-2 text-sm cursor-pointer transition-colors duration-100 flex items-center justify-between"
                            role="option"
                        >
                            <span x-text="opt.label"></span>
                            <span x-show="String(value) === opt.value"
                                  class="material-icons text-base text-[#1E40AF] dark:text-cerberus-accent">
                                check
                            </span>
                        </li>
                    </template>

                    {{-- Sin resultados --}}
                    <li x-show="filtered.length === 0"
                        class="px-4 py-3 text-sm text-gray-400 dark:text-cerberus-steel text-center italic">
                        Sin resultados
                    </li>
                </ul>
            </div>
        </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SELECT NATIVO (comportamiento original)
    ══════════════════════════════════════════════════════════════════════ --}}
    @else

        <div class="relative">
            <select
                id="{{ $fieldId }}"
                @if ($name && !$hasWireModel) name="{{ $name }}" @endif
                @if ($required) required  @endif
                @if ($disabled) disabled  @endif
                @if ($hint)     aria-describedby="{{ $hintId }}" @endif
                {{ $attributes->merge([
                    'class' =>
                        'w-full rounded-lg px-4 py-2 pr-9 text-sm transition appearance-none
                         bg-white dark:bg-cerberus-dark
                         border border-gray-300 dark:border-cerberus-steel
                         text-[#1E293B] dark:text-white
                         focus:outline-none focus:ring-2
                         focus:ring-[#1E40AF]/30 focus:border-[#1E40AF]
                         dark:focus:ring-cerberus-primary/30 dark:focus:border-cerberus-primary
                         disabled:opacity-50 disabled:cursor-not-allowed'
                         . ($error || (!$hasWireModel && $errors->has($name ?? ''))
                             ? ' border-red-400 dark:border-red-500'
                             : '')
                ]) }}
            >
                <option value="">{{ $placeholder }}</option>

                @foreach ($options as $optValue => $optLabel)
                    <option
                        value="{{ $optValue }}"
                        @if (!$hasWireModel && old($name, $selected) == $optValue) selected @endif
                    >
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>

            {{-- Chevron decorativo --}}
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="material-icons text-gray-400 dark:text-cerberus-steel text-base">
                    expand_more
                </span>
            </div>
        </div>

    @endif

    {{-- ── ERROR ──────────────────────────────────────────────────────────── --}}
    @if (!$hasWireModel && $name)
        @error($name)
            <p id="{{ $errorId }}" class="text-red-500 text-xs mt-1 flex items-center gap-1">
                <span class="material-icons text-xs">error_outline</span>
                {{ $message }}
            </p>
        @enderror
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="text-red-500 text-xs mt-1 flex items-center gap-1">
            <span class="material-icons text-xs">error_outline</span>
            {{ $error }}
        </p>
    @endif

</div>
