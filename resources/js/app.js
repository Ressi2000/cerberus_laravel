import './bootstrap'
// ❌ NO importar Alpine aquí — Livewire 3 lo incluye internamente
// import Alpine from 'alpinejs'  ← COMENTADO / ELIMINADO
// Si Alpine no está presente (p. ej. en páginas sin Livewire), lo cargamos dinámicamente.
if (typeof window.Alpine === 'undefined') {
    import('alpinejs').then((module) => {
        window.Alpine = module.default || module
        // start se llamará automáticamente cuando el script se ejecute,
        // pero por seguridad llamamos a start() si existe.
        if (window.Alpine && typeof window.Alpine.start === 'function') {
            window.Alpine.start()
        }
    }).catch(() => {
        // Falla silenciosa: si Alpine no carga, los componentes no-searchable seguirán funcionando
    })
}

import 'flowbite'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import { cerberusDarkMode } from './dark-mode'
import { initAvatarCropper } from './modules/avatar-cropper'

// ─── Registrar componentes Alpine ANTES de que Alpine arranque ────────────────
// Livewire 3 dispara 'alpine:init' antes de iniciar Alpine
document.addEventListener('alpine:init', () => {
    Alpine.data('cerberusDarkMode', cerberusDarkMode)
})

// ─── Exponer Cropper globalmente ──────────────────────────────────────────────
window.Cropper = Cropper

// ─── Avatar cropper (solo si existe el input en la página) ───────────────────
document.addEventListener('DOMContentLoaded', () => {
    initAvatarCropper({
        inputId:   'fotoInput',
        imageId:   'cropperImage',
        previewId: 'previewFoto',
        modalName: 'crop-photo',
    })
})

// ─── Re-inicializar cropper tras navegación SPA de Livewire ──────────────────
document.addEventListener('livewire:navigated', () => {
    initAvatarCropper({
        inputId:   'fotoInput',
        imageId:   'cropperImage',
        previewId: 'previewFoto',
        modalName: 'crop-photo',
    })
})
