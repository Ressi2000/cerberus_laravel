@if (session('success'))
    <div id="success-alert" class="mb-6 rounded-lg border border-green-500 bg-green-950/40 p-4 text-green-200 relative">
        <button onclick="document.getElementById('success-alert').style.display='none'" class="absolute top-2 right-2 text-green-400 hover:text-green-300">
            <span class="material-icons">close</span>
        </button>
        <div class="flex items-center gap-2 mb-2">
            <span class="material-icons text-green-400">check_circle</span>
            <h3 class="font-semibold">Operación exitosa</h3>
        </div>

        <p class="text-sm">{{ session('success') }}</p>
    </div>
@endif