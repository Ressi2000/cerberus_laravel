@if (session('success'))
    <div
        id="success-alert"
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mb-6 rounded-lg border border-green-500 bg-green-950/40 p-4 text-green-200 relative">

        <button @click="show = false"
            class="absolute top-3 right-3 text-green-400 hover:text-green-300 transition-colors">
            <span class="material-icons text-sm">close</span>
        </button>

        <div class="flex items-center gap-2 mb-1">
            <span class="material-icons text-green-400 text-base">check_circle</span>
            <h3 class="font-semibold text-sm">Operación exitosa</h3>
        </div>

        <p class="text-sm">{{ session('success') }}</p>
    </div>
@endif
