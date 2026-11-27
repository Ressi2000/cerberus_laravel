import './bootstrap'
// ❌ NO importar Alpine aquí — Livewire 3 lo incluye internamente.
// Solo se carga dinámicamente si la página NO tiene Livewire (ej. layouts/guest)
// una vez el DOM esté listo, evitando iniciar dos instancias de Alpine a la vez.
import 'flowbite'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import { cerberusDarkMode } from './dark-mode'
import { initAvatarCropper } from './modules/avatar-cropper'
import { monitorRedAlertas, pruebaVelocidad } from './modules/monitor-red'
import { graficoBot } from './modules/grafico-bot'

function registrarComponentesAlpine(alpine) {
    alpine.data('cerberusDarkMode', cerberusDarkMode)
    alpine.data('monitorRedAlertas', monitorRedAlertas)
    alpine.data('pruebaVelocidad', pruebaVelocidad)
    alpine.data('graficoBot', graficoBot)
}

// ─── Registrar componentes Alpine ANTES de que Alpine arranque ────────────────
// Livewire 3 dispara 'alpine:init' antes de iniciar Alpine
document.addEventListener('alpine:init', () => {
    registrarComponentesAlpine(window.Alpine)
})

// ─── Exponer Cropper globalmente ──────────────────────────────────────────────
window.Cropper = Cropper

function montarAvatarCropper() {
    initAvatarCropper({
        inputId:   'fotoInput',
        imageId:   'cropperImage',
        previewId: 'previewFoto',
        modalName: 'crop-photo',
    })
}

function inicializarPagina() {
    montarAvatarCropper()

    // Fallback de Alpine solo para páginas públicas sin Livewire (login, welcome, etc.)
    if (typeof window.Alpine === 'undefined' && typeof window.Livewire === 'undefined') {
        import('alpinejs').then((module) => {
            if (typeof window.Alpine !== 'undefined') return
            const alpine = module.default || module
            window.Alpine = alpine
            registrarComponentesAlpine(alpine)
            if (typeof alpine.start === 'function') {
                alpine.start()
            }
        }).catch(() => {
            // Falla silenciosa si Alpine no carga en páginas públicas
        })
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPagina)
} else {
    inicializarPagina()
}

// ─── Re-inicializar cropper tras navegación SPA de Livewire ──────────────────
document.addEventListener('livewire:navigated', montarAvatarCropper)
