<div>
    @if(session()->has('remote_support_active'))
        <div class="fixed top-0 left-0 right-0 bg-yellow-500 text-black px-4 py-2 z-50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-semibold">🔧 Remote Support Session Active</span>
                <span class="text-sm">Admin is viewing this account</span>
            </div>
            <button 
                wire:click="exitRemoteSupport" 
                class="px-4 py-1 bg-black text-yellow-500 rounded hover:bg-gray-800 font-semibold"
            >
                Exit Support Mode
            </button>
        </div>
        <div class="h-12"></div> <!-- Spacer to prevent content overlap -->
    @endif
</div>
