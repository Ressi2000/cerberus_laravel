import './bootstrap'
// import Alpine from 'alpinejs'
import { cerberusDarkMode } from './dark-mode'
import 'flowbite'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import { initAvatarCropper } from './modules/avatar-cropper'

// ─── Registrar componentes Alpine globales ────────────────────────────────────
Alpine.data('cerberusDarkMode', cerberusDarkMode)

// ─── Exponer Cropper globalmente ──────────────────────────────────────────────
window.Cropper = Cropper

// ─── Iniciar Alpine ───────────────────────────────────────────────────────────
// window.Alpine = Alpine
// Alpine.start()

// ─── Avatar cropper (solo si existe el input en la página) ───────────────────
document.addEventListener('DOMContentLoaded', () => {
    initAvatarCropper({
        inputId:   'fotoInput',
        imageId:   'cropperImage',
        previewId: 'previewFoto',
        modalName: 'crop-photo',
    })
})
