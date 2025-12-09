<x-app-layout title="Dashboard" header="Dashboard">
    <x-slot name="title">
        Dashboard Principal
    </x-slot>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-cerberus-mid rounded-lg p-6 shadow-cerberus border border-cerberus-steel">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-500/20 mr-4">
                    <span class="material-icons text-cerberus-light">people</span>
                </div>
                <div>
                    <p class="text-cerberus-accent text-sm">Total Usuarios</p>
                    <h3 class="text-2xl font-bold text-white">1,234</h3>
                </div>
            </div>
        </div>

        <div class="bg-cerberus-mid rounded-lg p-6 shadow-cerberus border border-cerberus-steel">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-500/20 mr-4">
                    <span class="material-icons text-cerberus-light">inventory_2</span>
                </div>
                <div>
                    <p class="text-cerberus-accent text-sm">Productos</p>
                    <h3 class="text-2xl font-bold text-white">567</h3>
                </div>
            </div>
        </div>

        <div class="bg-cerberus-mid rounded-lg p-6 shadow-cerberus border border-cerberus-steel">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-500/20 mr-4">
                    <span class="material-icons text-cerberus-light">receipt</span>
                </div>
                <div>
                    <p class="text-cerberus-accent text-sm">Ventas Hoy</p>
                    <h3 class="text-2xl font-bold text-white">89</h3>
                </div>
            </div>
        </div>

        <div class="bg-cerberus-mid rounded-lg p-6 shadow-cerberus border border-cerberus-steel">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-500/20 mr-4">
                    <span class="material-icons text-cerberus-light">warning</span>
                </div>
                <div>
                    <p class="text-cerberus-accent text-sm">Alertas</p>
                    <h3 class="text-2xl font-bold text-white">3</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-cerberus-mid rounded-lg p-6 shadow-cerberus border border-cerberus-steel">
                <h2 class="text-xl font-bold text-white mb-4">Actividad Reciente</h2>
                <!-- Contenido de la tabla o gráfico -->
                <div class="text-cerberus-accent">
                    Tu contenido aquí...
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-cerberus-mid rounded-lg p-6 shadow-cerberus border border-cerberus-steel">
                <h2 class="text-xl font-bold text-white mb-4">Notificaciones</h2>
                <!-- Lista de notificaciones -->
                <div class="text-cerberus-accent">
                    Notificaciones recientes...
                </div>
            </div>
        </div>
    </div>
</x-app-layout>