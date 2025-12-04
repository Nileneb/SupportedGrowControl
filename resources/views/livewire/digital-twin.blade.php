<div class="relative" x-data="digitalTwin()">
    <!-- Top Controls -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">{{ $layout->name ?? 'Digital Twin' }}</h2>
        <div class="flex gap-2">
            <button 
                wire:click="toggleWebcam" 
                class="px-3 py-1 text-sm border rounded {{ $showWebcam ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700' }}"
            >
                📷 Webcam
            </button>
            <button 
                wire:click="toggleEditMode" 
                class="px-3 py-1 text-sm border rounded {{ $editMode ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700' }}"
            >
                {{ $editMode ? '✓ Edit Mode' : '✏️ Edit' }}
            </button>
        </div>
    </div>

    <!-- Main Canvas Area -->
    <div class="relative border-2 border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden"
         style="width: {{ $layout->width ?? 1000 }}px; height: {{ $layout->height ?? 600 }}px; background-color: {{ $layout->background_color ?? '#1a1a1a' }};">
        
        <!-- Grid background when in edit mode -->
        @if($editMode)
            <div class="absolute inset-0 opacity-20" 
                 style="background-image: repeating-linear-gradient(0deg, #888 0px, #888 1px, transparent 1px, transparent 50px),
                                         repeating-linear-gradient(90deg, #888 0px, #888 1px, transparent 1px, transparent 50px);
                        background-size: 50px 50px;">
            </div>
        @endif

        <!-- Elements -->
        @foreach($elements as $element)
            <div 
                class="absolute cursor-move select-none transition-shadow hover:shadow-lg {{ $editMode ? 'border-2 border-dashed border-blue-400' : '' }}"
                style="left: {{ $element['x_position'] }}px; 
                       top: {{ $element['y_position'] }}px; 
                       width: {{ $element['width'] }}px; 
                       height: {{ $element['height'] }}px;
                       transform: rotate({{ $element['rotation'] }}deg);
                       z-index: {{ $element['z_index'] }};"
                x-data="{ dragging: false }"
                @if($editMode)
                    draggable="true"
                    @dragstart="$wire.set('draggedElement', {{ $element['id'] }})"
                    @dragend="handleDragEnd($event, {{ $element['id'] }})"
                @endif
            >
                <div class="w-full h-full flex flex-col items-center justify-center p-2 rounded {{ $element['color'] ? 'text-white' : 'text-gray-300' }}"
                     style="{{ $element['color'] ? 'background-color: ' . $element['color'] : 'background-color: rgba(255,255,255,0.1)' }}">
                    <div class="text-4xl">{{ $element['icon'] }}</div>
                    <div class="text-xs mt-1 text-center break-words">{{ $element['label'] }}</div>
                    @if($element['device_id'] && isset($element['device']))
                        <div class="text-[10px] mt-1 opacity-75">{{ $element['device']['name'] ?? '' }}</div>
                        <div class="text-[10px] px-1 rounded {{ $element['device']['status'] === 'online' ? 'bg-green-500' : 'bg-gray-500' }}">
                            {{ $element['device']['status'] }}
                        </div>
                    @endif
                </div>
                
                @if($editMode)
                    <button 
                        wire:click="deleteElement({{ $element['id'] }})"
                        class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-xs hover:bg-red-600"
                    >×</button>
                @endif
            </div>
        @endforeach

        <!-- Drop zone overlay for edit mode -->
        @if($editMode)
            <div class="absolute inset-0 pointer-events-none"></div>
        @endif
    </div>

    <!-- Add Elements Toolbar (Edit Mode) -->
    @if($editMode)
        <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded flex gap-2 flex-wrap">
            <span class="text-sm font-semibold self-center">Element hinzufügen:</span>
            <button wire:click="addElement('device')" class="px-3 py-1 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">🔌 Device</button>
            <button wire:click="addElement('plant')" class="px-3 py-1 text-sm bg-green-500 text-white rounded hover:bg-green-600">🌱 Pflanze</button>
            <button wire:click="addElement('light')" class="px-3 py-1 text-sm bg-yellow-500 text-white rounded hover:bg-yellow-600">💡 Licht</button>
            <button wire:click="addElement('fan')" class="px-3 py-1 text-sm bg-cyan-500 text-white rounded hover:bg-cyan-600">🌀 Lüfter</button>
            <button wire:click="addElement('camera')" class="px-3 py-1 text-sm bg-purple-500 text-white rounded hover:bg-purple-600">📷 Kamera</button>
            <button wire:click="addElement('shelf')" class="px-3 py-1 text-sm bg-gray-500 text-white rounded hover:bg-gray-600">▭ Regal</button>
            <button wire:click="addElement('label')" class="px-3 py-1 text-sm bg-orange-500 text-white rounded hover:bg-orange-600">📝 Label</button>
        </div>
    @endif

    <!-- Webcam Feed -->
    @if($showWebcam && count($webcams) > 0)
        <div class="fixed bottom-4 right-4 w-80 bg-black rounded-lg shadow-2xl border-2 border-gray-600 z-50"
             x-data="{ minimized: false }">
            <div class="flex items-center justify-between p-2 bg-gray-800 rounded-t-lg">
                <div class="flex items-center gap-2">
                    <span class="text-white text-sm font-semibold">📷 Webcam</span>
                    @if(count($webcams) > 1)
                        <select wire:model.live="selectedWebcam" class="text-xs px-1 py-0.5 bg-gray-700 text-white rounded">
                            @foreach($webcams as $cam)
                                <option value="{{ $cam['id'] }}">{{ $cam['name'] }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="flex gap-1">
                    <button @click="minimized = !minimized" class="text-white hover:text-gray-300">
                        <span x-show="!minimized">−</span>
                        <span x-show="minimized">□</span>
                    </button>
                    <button wire:click="toggleWebcam" class="text-white hover:text-gray-300">×</button>
                </div>
            </div>
            <div x-show="!minimized" class="relative" style="aspect-ratio: 16/9;">
                @php
                    $currentWebcam = collect($webcams)->firstWhere('id', $selectedWebcam);
                @endphp
                
                @if($currentWebcam)
                    @if($currentWebcam['type'] === 'mjpeg' || $currentWebcam['type'] === 'image')
                        <img 
                            src="{{ $currentWebcam['stream_url'] }}" 
                            class="w-full h-full object-cover"
                            alt="Webcam Feed"
                            @if($currentWebcam['type'] === 'image')
                                x-init="setInterval(() => { $el.src = '{{ $currentWebcam['stream_url'] }}?' + Date.now() }, {{ $currentWebcam['refresh_interval'] ?? 1000 }})"
                            @endif
                        />
                    @elseif($currentWebcam['type'] === 'hls')
                        <video class="w-full h-full" controls autoplay>
                            <source src="{{ $currentWebcam['stream_url'] }}" type="application/x-mpegURL">
                        </video>
                    @endif
                    
                    <!-- Live indicator -->
                    <div class="absolute top-2 left-2 px-2 py-1 bg-red-600 text-white text-xs rounded flex items-center gap-1">
                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                        LIVE
                    </div>
                @endif
            </div>
        </div>
    @endif

    <script>
        function digitalTwin() {
            return {
                handleDragEnd(event, elementId) {
                    const canvas = event.target.closest('[style*="background-color"]');
                    if (!canvas) return;
                    
                    const rect = canvas.getBoundingClientRect();
                    const x = Math.max(0, Math.min(event.clientX - rect.left - 40, rect.width - 80));
                    const y = Math.max(0, Math.min(event.clientY - rect.top - 40, rect.height - 80));
                    
                    @this.call('updateElementPosition', elementId, Math.round(x), Math.round(y));
                }
            }
        }
    </script>
</div>
