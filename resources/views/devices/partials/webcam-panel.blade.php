<div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50">Webcam Feeds</h2>
        <button 
            @click="refreshWebcams()" 
            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
            Refresh
        </button>
    </div>

    <div id="webcam-list" class="space-y-3">
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Webcams werden automatisch vom Agent registriert, wenn verfügbar.
        </p>
    </div>
</div>

<script>
    // Use existing devicePublicId from parent page (defined in show-workstation.blade.php)
    // const devicePublicId is already defined globally
    let webcams = [];

    async function refreshWebcams() {
        try {
            const response = await fetch(`/api/devices/${devicePublicId}/webcams`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                console.error('Failed to load webcams:', response.statusText);
                return;
            }

            const data = await response.json();
            webcams = data.webcams || [];
            renderWebcams();
        } catch (error) {
            console.error('Error loading webcams:', error);
        }
    }

    function renderWebcams() {
        const container = document.getElementById('webcam-list');
        
        if (webcams.length === 0) {
            container.innerHTML = `
                <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 text-center dark:border-neutral-700 dark:bg-neutral-800">
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        📷 Keine Webcams verfügbar
                    </p>
                    <p class="mt-2 text-xs text-neutral-500 dark:text-neutral-500">
                        Der Agent registriert Webcams automatisch beim Start.
                    </p>
                </div>
            `;
            return;
        }

        container.innerHTML = webcams.map(cam => `
            <div class="flex items-center justify-between rounded-lg border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📷</span>
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-neutral-50">${escapeHtml(cam.name)}</p>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">${escapeHtml(cam.device_path)}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${cam.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-300'}">
                        ${cam.is_active ? 'Active' : 'Inactive'}
                    </span>
                    <button 
                        onclick="toggleWebcam(${cam.id}, ${!cam.is_active})" 
                        class="rounded px-2 py-1 text-xs font-semibold ${cam.is_active ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700'}">
                        ${cam.is_active ? 'Disable' : 'Enable'}
                    </button>
                    ${cam.is_active ? `
                        <a href="${escapeHtml(cam.stream_url)}" target="_blank" class="rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-700">
                            View Stream
                        </a>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    async function toggleWebcam(webcamId, isActive) {
        try {
            const response = await fetch(`/api/webcams/${webcamId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ is_active: isActive })
            });

            if (!response.ok) {
                console.error('Failed to toggle webcam');
                return;
            }

            refreshWebcams();
        } catch (error) {
            console.error('Error toggling webcam:', error);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load webcams on page load
    document.addEventListener('DOMContentLoaded', () => {
        refreshWebcams();
    });
</script>
