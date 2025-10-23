require('./bootstrap');


        function CopyToClipboard () {
        // 1. Mostrar estado de carga y deshabilitar botón
            statusMessage.textContent = 'Generando imagen...';
            statusMessage.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800');
            statusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
            $(this).disabled = true;
            outputContainer.innerHTML = ''; // Limpiar previsualización anterior

            try {
                // 2. Generar el Canvas a partir del elemento DOM (ya corregido a 'printableArea[0]')
                const canvas = await html2canvas(printableArea, {
                    scale: 2, // Aumenta la escala para mejor calidad de imagen
                    logging: false, // Desactiva logs de html2canvas
                    useCORS: true // Necesario si hay imágenes o recursos externos
                });

                // Opcional: Mostrar el canvas generado en el DOM
            // outputContainer.appendChild(canvas);

                // 3. Convertir el Canvas a un Blob (formato de datos binarios)
                const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                
                if (!imageBlob) {
                    throw new Error('No se pudo generar el Blob de la imagen.');
                }

                // 4. Copiar la imagen (Blob) al portapapeles usando el Clipboard API
                const item = new ClipboardItem({ "image/png": imageBlob });
                await navigator.clipboard.write([item]);

                // 5. Éxito
                statusMessage.textContent = '¡Éxito! La imagen ha sido copiada al portapapeles. Ahora puedes pegarla (Ctrl+V).';
                statusMessage.classList.replace('bg-yellow-100', 'bg-green-100');
                statusMessage.classList.replace('text-yellow-800', 'text-green-800');

            } catch (error) {
                // 6. Manejo de Errores
                let errorMessage = 'Error desconocido al copiar.';

                if (error.name === 'NotAllowedError' || (error.message && error.message.includes('permission'))) {
                    errorMessage = 'Permiso denegado: El navegador requiere que la página esté en un contexto seguro (HTTPS) o que el usuario interactúe primero para usar el Clipboard API.';
                } else {
                    console.error('Error durante la captura o copia:', error);
                    errorMessage = `Error al generar/copiar la imagen: ${error.message}`;
                }
                
                statusMessage.textContent = errorMessage;
                statusMessage.classList.replace('bg-yellow-100', 'bg-red-100');
                statusMessage.classList.replace('text-yellow-800', 'text-red-800');

            } finally {
                // 7. Reestablecer el botón
                $(this).disabled = false;
            }
        }
