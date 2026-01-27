import './bootstrap';

// import Alpine from 'alpinejs';

import 'flowbite';

import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
window.Cropper = Cropper;

// resources/js/app.js
import { initAvatarCropper } from './modules/avatar-cropper';

document.addEventListener('DOMContentLoaded', () => {
    initAvatarCropper({
        inputId: 'fotoInput',
        imageId: 'cropperImage',
        previewId: 'previewFoto',
        modalName: 'crop-photo',
    });
});

// window.Alpine = Alpine;

// Alpine.start();

