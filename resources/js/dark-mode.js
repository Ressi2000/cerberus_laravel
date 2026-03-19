/**
 * Cerberus 2.0 — Dark Mode Manager
 *
 * Registra un componente Alpine global `cerberusDarkMode()`.
 * Persistencia en localStorage, fallback a preferencia del SO.
 *
 * Uso en layout:
 *   <html x-data="cerberusDarkMode()" :class="{ 'dark': isDark }">
 */
export function cerberusDarkMode() {
    return {
        isDark: true,

        init() {
            const saved = localStorage.getItem('cerberus-theme')

            if (saved !== null) {
                this.isDark = saved === 'dark'
            } else {
                // Fallback: preferencia del sistema operativo
                this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            }

            // Sincronizar la clase en <html> inmediatamente (evita flash)
            this.applyClass()

            // Escuchar cambios del SO si no hay preferencia guardada
            window.matchMedia('(prefers-color-scheme: dark)')
                .addEventListener('change', (e) => {
                    if (localStorage.getItem('cerberus-theme') === null) {
                        this.isDark = e.matches
                        this.applyClass()
                    }
                })
        },

        toggle() {
            this.isDark = !this.isDark
            localStorage.setItem('cerberus-theme', this.isDark ? 'dark' : 'light')
            this.applyClass()
        },

        applyClass() {
            document.documentElement.classList.toggle('dark', this.isDark)
        },
    }
}

/**
 * Acceso global para usar fuera de Alpine (ej: botón en navbar).
 * Llama desde cualquier script: window.cerberusToggleDark()
 */
window.cerberusToggleDark = function () {
    const isDark = document.documentElement.classList.toggle('dark')
    localStorage.setItem('cerberus-theme', isDark ? 'dark' : 'light')
}
