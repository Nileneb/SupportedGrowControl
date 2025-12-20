import "./bootstrap";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Initialize Laravel Echo with Reverb
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wsPath: import.meta.env.VITE_REVERB_PATH ?? "",
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: "/broadcasting/auth",
    auth: {
        withCredentials: true,
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content"),
        },
    },
});


console.log("✓ Laravel Echo initialized");

// VideoFrame WebSocket-Listener importieren
import { subscribeVideoFrame } from "./video-frame";

// Beispiel: Video-Stream für ein Device abonnieren
// subscribeVideoFrame('DEVICE_PUBLIC_ID', 'video-frame-img');

// Global WebSocket status indicator
window.wsConnected = false;

// Create custom event for WebSocket status changes
window.dispatchEvent(new CustomEvent('ws-initializing'));

// Listen for connection events
if (window.Echo.connector && window.Echo.connector.pusher) {
    window.Echo.connector.pusher.connection.bind("connected", () => {
        console.log("✓ WebSocket connected");
        window.wsConnected = true;
        window.dispatchEvent(new CustomEvent('ws-connected'));
    });

    window.Echo.connector.pusher.connection.bind("disconnected", () => {
        console.warn("⚠ WebSocket disconnected");
        window.wsConnected = false;
        window.dispatchEvent(new CustomEvent('ws-disconnected'));
    });

    window.Echo.connector.pusher.connection.bind("error", (err) => {
        console.error("✗ WebSocket error:", err);
        window.wsConnected = false;
        window.dispatchEvent(new CustomEvent('ws-error', { detail: err }));
    });
}
