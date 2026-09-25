<div
    x-data="cerberusCronogramaCalendario(@js($eventos))"
    x-init="init($el.querySelector('.cerberus-calendario'))"
    wire:ignore
>
    @once
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <style>
            .cerberus-calendario--compact .fc-toolbar-title { font-size: 1rem; }
            .cerberus-calendario--compact .fc { max-height: 420px; }
            .cerberus-calendario .fc-event { cursor: pointer; font-size: 0.75rem; }
        </style>
        <script>
            function cerberusCronogramaCalendario(eventosIniciales) {
                return {
                    calendar: null,
                    init(el) {
                        this.calendar = new FullCalendar.Calendar(el, {
                            initialView: 'dayGridMonth',
                            height: 'auto',
                            locale: 'es',
                            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listMonth' },
                            events: eventosIniciales,
                        });
                        this.calendar.render();

                        Livewire.on('planGuardado', () => this.refrescar());
                        Livewire.on('planEliminado', () => this.refrescar());
                    },
                    async refrescar() {
                        if (! this.calendar) return;
                        const eventos = await this.$wire.eventos();
                        this.calendar.removeAllEventSources();
                        this.calendar.addEventSource(eventos);
                    },
                };
            }
        </script>
    @endonce

    <div class="bg-white dark:bg-cerberus-mid border border-gray-200 dark:border-cerberus-steel rounded-xl p-4">
        <div class="cerberus-calendario {{ $modo === 'compact' ? 'cerberus-calendario--compact' : '' }}"></div>
    </div>
</div>
