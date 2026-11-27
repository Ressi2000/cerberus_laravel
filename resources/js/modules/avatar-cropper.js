// resources/js/modules/avatar-cropper.js
//
// Estrategia para funcionar con Livewire WithFileUploads:
//   1. Input VISIBLE (#fotoInput) — solo para el cropper JS
//   2. Input OCULTO (#fotoInputLivewire) — wire:model, lo maneja Livewire
//   3. Al confirmar el crop, inyectamos el blob en el input de Livewire via DataTransfer
//
// En "Mi perfil" no hay Livewire: es un formulario normal. Ahí no existe
// #fotoInputLivewire y la foto recortada se deja en #fotoInput (vía DataTransfer)
// y además en #fotoRecortadaInput (base64 JPEG de ~20 KB) como respaldo garantizado
// para navegadores o proxies donde DataTransfer o multipart puedan fallar.

export function initAvatarCropper({
    inputId         = 'fotoInput',
    livewireInputId = 'fotoInputLivewire',
    hiddenDataId    = 'fotoRecortadaInput',
    statusId        = 'fotoEstado',
    imageId         = 'cropperImage',
    previewId       = 'previewFoto',
    modalName       = 'crop-photo',
    aspectRatio     = 1,
    outputSize      = 256,
}) {
    let cropper = null
    let previewObjectUrl = null

    const fotoInput     = document.getElementById(inputId)
    const livewireInput = document.getElementById(livewireInputId)
    const hiddenData    = document.getElementById(hiddenDataId)
    const statusEl      = document.getElementById(statusId)
    const cropperImage  = document.getElementById(imageId)
    const previewFoto   = document.getElementById(previewId)
    const cancelBtn     = document.getElementById('cancelCrop')
    const confirmBtn    = document.getElementById('confirmCrop')

    // Si no existe ninguno de los inputs, no hay formulario con foto en esta página
    if (!fotoInput && !livewireInput) return

    // Se llama al cargar y también en livewire:navigated: no enganchar dos veces.
    const marca = fotoInput ?? livewireInput
    if (marca.dataset.cropperListo) return
    marca.dataset.cropperListo = '1'

    const openModal  = () => window.dispatchEvent(new CustomEvent('open-modal',  { detail: modalName }))
    const closeModal = () => window.dispatchEvent(new CustomEvent('close-modal', { detail: modalName }))

    const setStatus = (texto, listo = false) => {
        if (!statusEl) return
        statusEl.textContent = texto
        statusEl.classList.toggle('text-green-600', listo)
        statusEl.classList.toggle('dark:text-green-400', listo)
        statusEl.classList.toggle('font-medium', listo)
    }

    const destruirCropper = () => {
        cropper?.destroy()
        cropper = null
    }

    /**
     * Espera a que el modal sea visible (offsetWidth > 0) y la imagen haya cargado
     * antes de montar Cropper.js; así nunca se inicializa con tamaño 0×0.
     */
    const montarCropperCuandoSeaVisible = (intentos = 0) => {
        if (!cropperImage) return
        const contenedor = cropperImage.parentElement
        const listo = cropperImage.complete
            && cropperImage.naturalWidth > 0
            && contenedor
            && contenedor.offsetWidth > 0

        if (!listo && intentos < 30) {
            setTimeout(() => montarCropperCuandoSeaVisible(intentos + 1), 35)
            return
        }

        destruirCropper()
        cropper = new Cropper(cropperImage, {
            aspectRatio,
            viewMode: 1,
            autoCropArea: 1,
            background: false,
            responsive: true,
        })
    }

    // Escuchar el input VISIBLE (no el de Livewire)
    fotoInput?.addEventListener('change', e => {
        const file = e.target.files?.[0]
        if (!file || !file.type.startsWith('image/')) return

        if (hiddenData) hiddenData.value = ''

        const reader = new FileReader()
        reader.onload = () => {
            if (!cropperImage) return
            destruirCropper()
            cropperImage.onload = () => {
                cropperImage.onload = null
                montarCropperCuandoSeaVisible()
            }
            cropperImage.src = reader.result
            openModal()
            // Si ya estaba decodificada en memoria, montar tras el siguiente frame
            if (cropperImage.complete && cropperImage.naturalWidth > 0) {
                cropperImage.onload = null
                montarCropperCuandoSeaVisible()
            }
        }
        reader.readAsDataURL(file)
    })

    cancelBtn?.addEventListener('click', () => {
        destruirCropper()
        if (fotoInput) fotoInput.value = ''
        if (hiddenData) hiddenData.value = ''
        closeModal()
    })

    // Limpiar instancia si el modal se cierra por backdrop o tecla Escape
    window.addEventListener('close-modal', e => {
        if (e.detail === modalName) destruirCropper()
    })

    confirmBtn?.addEventListener('click', () => {
        if (!cropper) return

        const canvas = cropper.getCroppedCanvas({
            width: outputSize,
            height: outputSize,
            fillColor: '#ffffff',
            imageSmoothingQuality: 'high',
        })

        if (!canvas) {
            destruirCropper()
            closeModal()
            return
        }

        const dataUrl = canvas.toDataURL('image/jpeg', 0.9)

        canvas.toBlob(blob => {
            if (blob) {
                const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' })

                // Inyectar en el input de Livewire via DataTransfer
                if (livewireInput) {
                    const dt = new DataTransfer()
                    dt.items.add(file)
                    livewireInput.files = dt.files
                    livewireInput.dispatchEvent(new Event('change', { bubbles: true }))
                    if (fotoInput) fotoInput.value = ''
                } else {
                    // Formulario normal (Mi perfil): guardar en respaldo base64 y en #fotoInput
                    if (hiddenData) hiddenData.value = dataUrl
                    if (fotoInput) {
                        try {
                            const dt = new DataTransfer()
                            dt.items.add(file)
                            fotoInput.files = dt.files
                        } catch {
                            // Si el navegador bloquea asignar .files, vaciar para que viaje solo hiddenData
                            fotoInput.value = ''
                        }
                    }
                    setStatus('Foto ajustada. Pulsa «Guardar foto» para aplicarla.', true)
                }

                // Actualizar el preview visual
                if (previewFoto) {
                    if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl)
                    previewObjectUrl = URL.createObjectURL(blob)
                    previewFoto.src = previewObjectUrl
                }
            } else if (!livewireInput && hiddenData && dataUrl) {
                // Fallback puro por dataUrl si toBlob devolviera null
                hiddenData.value = dataUrl
                if (fotoInput) fotoInput.value = ''
                if (previewFoto) previewFoto.src = dataUrl
                setStatus('Foto ajustada. Pulsa «Guardar foto» para aplicarla.', true)
            }

            destruirCropper()
            closeModal()
        }, 'image/jpeg', 0.9)
    })
}
