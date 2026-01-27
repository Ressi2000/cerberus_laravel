// resources/js/modules/avatar-cropper.js
// Se renderiza en app.js

export function initAvatarCropper({
    inputId = 'fotoInput',
    imageId = 'cropperImage',
    previewId = 'previewFoto',
    modalName = 'crop-photo',
    aspectRatio = 1,
    outputSize = 256,
}) {
    let cropper = null;

    const fotoInput = document.getElementById(inputId);
    const cropperImage = document.getElementById(imageId);
    const previewFoto = document.getElementById(previewId);
    const cancelBtn = document.getElementById('cancelCrop');
    const confirmBtn = document.getElementById('confirmCrop');

    if (!fotoInput || !cropperImage) return;

    const openModal = () =>
        window.dispatchEvent(new CustomEvent('open-modal', { detail: modalName }));

    const closeModal = () =>
        window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }));

    fotoInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = () => {
            cropperImage.src = reader.result;
            openModal();

            cropper?.destroy();

            requestAnimationFrame(() => {
                cropper = new Cropper(cropperImage, {
                    aspectRatio,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                });
            });
        };

        reader.readAsDataURL(file);
    });

    cancelBtn?.addEventListener('click', () => {
        cropper?.destroy();
        cropper = null;
        fotoInput.value = '';
        closeModal();
    });

    confirmBtn?.addEventListener('click', () => {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: outputSize,
            height: outputSize,
            imageSmoothingQuality: 'high',
        });

        canvas.toBlob(blob => {
            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

            const dt = new DataTransfer();
            dt.items.add(file);
            fotoInput.files = dt.files;

            if (previewFoto) {
                previewFoto.src = URL.createObjectURL(blob);
            }

            cropper.destroy();
            cropper = null;
            closeModal();
        }, 'image/jpeg', 0.9);
    });
}