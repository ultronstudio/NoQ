// Independent of the main bundle so a realtime connection failure cannot block PWA setup.
if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).catch((error) => {
            console.warn('Registrace PWA se nezdařila.', error);
        });
    });
}
