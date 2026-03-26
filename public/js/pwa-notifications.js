// public/js/pwa-notifications.js

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

async function suscribirUsuario(vapidPublicKey) {
    try {
        const registration = await navigator.serviceWorker.ready;
        
        // Verificar si ya existe una suscripción para no duplicar
        const existingSubscription = await registration.pushManager.getSubscription();
        if (existingSubscription) {
            console.log('Usuario ya suscrito.');
            return existingSubscription;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        // Enviar al servidor mediante Axios
        await axios.post('/notifications/subscribe', subscription);
        
        console.log('Suscripción exitosa en TuCombustible');
    } catch (error) {
        console.error('Error al suscribir el dispositivo:', error);
    }
}