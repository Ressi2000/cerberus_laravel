// resources/js/modules/avatar-cropper.js
//
// Estrategia para funcionar con Livewire WithFileUploads:
//   1. Input VISIBLE (#fotoInput) — solo para el cropper JS
//   2. Input OCULTO (#fotoInputLivewire) — wire:model, lo maneja Livewire
//   3. Al confirmar el crop, inyectamos el blob en el input de Livewire via DataTransfer

export function initAvatarCropper({
    inputId       = 'fotoInput',
    livewireInputId = 'fotoInputLivewire',
    imageId       = 'cropperImage',
    previewId     = 'previewFoto',
    modalName     = 'crop-photo',
    aspectRatio   = 1,
    outputSize    = 256,
}) {
    let cropper = null

    const fotoInput         = document.getElementById(inputId)
    const livewireInput     = document.getElementById(livewireInputId)
    const cropperImage      = document.getElementById(imageId)
    const previewFoto       = document.getElementById(previewId)
    const cancelBtn         = document.getElementById('cancelCrop')
    const confirmBtn        = document.getElementById('confirmCrop')

    // Si no existe ninguno de los inputs, no hay formulario con foto en esta página
    if (!fotoInput && !livewireInput) return

    const openModal  = () => window.dispatchEvent(new CustomEvent('open-modal',  { detail: modalName }))
    const closeModal = () => window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }))

    // Escuchar el input VISIBLE (no el de Livewire)
    fotoInput?.addEventListener('change', e => {
        const file = e.target.files[0]
        if (!file || !file.type.startsWith('image/')) return

        const reader = new FileReader()
        reader.onload = () => {
            cropperImage.src = reader.result
            openModal()

            cropper?.destroy()

            requestAnimationFrame(() => {
                cropper = new Cropper(cropperImage, {
                    aspectRatio,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                })
            })
        }
        reader.readAsDataURL(file)
    })

    cancelBtn?.addEventListener('click', () => {
        cropper?.destroy()
        cropper = null
        if (fotoInput) fotoInput.value = ''
        closeModal()
    })

    confirmBtn?.addEventListener('click', () => {
        if (!cropper) return

        const canvas = cropper.getCroppedCanvas({
            width: outputSize,
            height: outputSize,
            imageSmoothingQuality: 'high',
        })

        canvas.toBlob(blob => {
            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' })

            // Inyectar en el input de Livewire via DataTransfer
            if (livewireInput) {
                const dt = new DataTransfer()
                dt.items.add(file)
                livewireInput.files = dt.files
                // Disparar el evento 'change' para que Livewire lo detecte
                livewireInput.dispatchEvent(new Event('change', { bubbles: true }))
            }

            // Actualizar el preview visual
            if (previewFoto) {
                previewFoto.src = URL.createObjectURL(blob)
            }

            // Limpiar el input visible
            if (fotoInput) fotoInput.value = ''

            cropper.destroy()
            cropper = null
            closeModal()
        }, 'image/jpeg', 0.9)
    })
}
