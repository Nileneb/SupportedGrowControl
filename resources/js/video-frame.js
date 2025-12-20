// VideoFrame WebSocket-Listener und Anzeige als <img>
// Fügt einen Listener für den VideoFrame-Event hinzu und aktualisiert ein Bild-Element

export function subscribeVideoFrame(devicePublicId, imgElementId) {
    if (!window.Echo) {
        console.error("Echo not initialized");
        return;
    }
    const channel = window.Echo.private(`video.${devicePublicId}`);
    channel.listen('.video.frame', (payload) => {
        if (payload && payload.frame) {
            const img = document.getElementById(imgElementId);
            if (img) {
                img.src = `data:image/jpeg;base64,${payload.frame}`;
            }
        }
    });
    console.log(`Subscribed to video.${devicePublicId} for video.frame events`);
}
