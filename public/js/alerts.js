/**
 * @fileoverview Biblioteca de funciones para mostrar alertas de SweetAlert2 de forma centralizada.
 * Permite reutilizar los mismos estilos y comportamientos en toda la aplicación.
 */

/**
 * Muestra una alerta de éxito.
 * @param {string} title El título de la alerta.
 * @param {string} text El texto del mensaje.
 * @param {function} [callback] Función opcional que se ejecuta al cerrar la alerta.
 */
function showSuccessAlert( text, title='¡Éxito!', callback = null) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'success',
        confirmButtonText: 'Aceptar'
    }).then(() => {
        if (callback) {
            callback();
        }
    });
}

/**
 * Muestra una alerta de error.
 * @param {string} title El título de la alerta.
 * @param {string} text El texto del mensaje.
 * @param {function} [callback] Función opcional que se ejecuta al cerrar la alerta.
 */
function showErrorAlert( text, title='Error', callback = null) {
    Swal.fire({
        title: title,
        text: text,
        icon: 'error',
        confirmButtonText: 'Cerrar'
    }).then(() => {
        if (callback) {
            callback();
        }
    });
}

/**
 * Muestra una alerta de confirmación con opciones de Sí/No.
 * @param {string} title El título de la alerta.
 * @param {string} text El texto del mensaje.
 * @param {string} confirmText El texto del botón de confirmación.
 * @param {string} cancelText El texto del botón de cancelación.
 * @returns {Promise<boolean>} Retorna una promesa que se resuelve en `true` si el usuario confirma.
 */
function showConfirmAlert(title, text, confirmText = 'Sí', cancelText = 'No') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    }).then((result) => {
        return result.isConfirmed;
    });
}

/**
 * Muestra una alerta para solicitar un valor de entrada.
 * @param {string} title El título de la alerta.
 * @param {string} text El texto del mensaje.
 * @param {string} placeholder El texto del marcador de posición en el campo de entrada.
 * @param {string} inputType El tipo de entrada ('text', 'number', etc.).
 * @returns {Promise<string|null>} Retorna una promesa con el valor ingresado o `null` si se cancela.
 */
function showPromptAlert(title, text, placeholder = '', inputType = 'text') {
    return Swal.fire({
        title: title,
        text: text,
        input: inputType,
        inputPlaceholder: placeholder,
        showCancelButton: true,
        confirmButtonText: 'Aceptar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return '¡Necesitas ingresar algo!';
            }
        }
    }).then((result) => {
        return result.value || null;
    });
}

//example usage in other
// javascript
// if (response.ok && result.success) {
//     showSuccessAlert('¡Éxito!', 'Pedido realizado con éxito.', () => {
//         bootstrap.Modal.getInstance(pedidoModal).hide();
//         window.location.reload();
//     });
// } else {
//     showErrorAlert('Error', 'Error al realizar el pedido: ' + result.message);
// }
