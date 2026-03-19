@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-500 bg-red-950/40 p-4 text-red-200">
        <div class="flex items-center gap-2 mb-2">
            <span class="material-icons text-red-400">error</span>
            <h3 class="font-semibold text-sm">Hay errores en el formulario</h3>
        </div>

        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
