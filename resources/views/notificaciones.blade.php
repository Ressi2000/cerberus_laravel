<x-app-layout title="Notificaciones" header="Notificaciones">
    <x-slot name="title">Notificaciones</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="material-icons text-cerberus-accent">notifications</span>
                Notificaciones
            </h1>
            <p class="text-cerberus-accent text-xs mt-1">Historial de alertas y eventos del sistema</p>
        </div>
    </div>

    <livewire:notificaciones.notifications-index />
</x-app-layout>
