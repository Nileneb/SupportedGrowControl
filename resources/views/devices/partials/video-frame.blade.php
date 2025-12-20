<div>
    <h3>Live Video-Stream</h3>
    <img id="video-frame-img" src="" alt="Live Video" style="max-width:100%;border:1px solid #ccc;" />
    <script>
        // Device-Public-ID dynamisch setzen, z.B. per Blade-Variable
        document.addEventListener('DOMContentLoaded', function() {
            const devicePublicId = "{{ $device->public_id ?? 'DEVICE_PUBLIC_ID' }}";
            if (window.subscribeVideoFrame) {
                window.subscribeVideoFrame(devicePublicId, 'video-frame-img');
            } else if (window.Echo) {
                // Fallback falls import nicht funktioniert
                window.Echo.private(`video.${devicePublicId}`)
                    .listen('.video.frame', function(payload) {
                        if (payload && payload.frame) {
                            document.getElementById('video-frame-img').src = `data:image/jpeg;base64,${payload.frame}`;
                        }
                    });
            }
        });
    </script>
</div>
