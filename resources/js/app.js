import "./bootstrap";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Initialize Laravel Echo with Reverb
window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
    enabledTransports: ["ws", "wss"],
});

console.log("✓ Laravel Echo initialized");

// Global WebSocket status indicator
window.wsConnected = false;

// Listen for connection events
if (window.Echo.connector && window.Echo.connector.pusher) {
    window.Echo.connector.pusher.connection.bind("connected", () => {
        console.log("✓ WebSocket connected");
        window.wsConnected = true;
    });

    window.Echo.connector.pusher.connection.bind("disconnected", () => {
        console.warn("⚠ WebSocket disconnected");
        window.wsConnected = false;
    });

    window.Echo.connector.pusher.connection.bind("error", (err) => {
        console.error("✗ WebSocket error:", err);
        window.wsConnected = false;
    });
}
