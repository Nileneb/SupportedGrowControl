<?php

namespace App\Livewire\Admin;

use App\Models\DeviceScript;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeviceScriptManagement extends Component
{
    public $scripts = [];
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedScriptId = null;
    public $name = '';
    public $description = '';
    public $code = '';
    public $device_id = null;
    public $language = 'cpp';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'code' => 'required|string',
        'device_id' => 'nullable|exists:devices,id',
        'language' => 'required|in:cpp',
    ];

    public function mount()
    {
        $this->loadScripts();
    }
    public function loadScripts()
    {
        $this->scripts = DeviceScript::where('user_id', Auth::id())
            ->with('device')
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function openCreateModal()
    {
        $this->reset(['name','description','code','device_id','language']);
        $this->language = 'cpp';
        $this->showCreateModal = true;
    }
    public function createScript()
    {
        $this->validate();
        DeviceScript::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'device_id' => $this->device_id,
            'language' => $this->language,
        ]);
        $this->showCreateModal = false;
        $this->loadScripts();
        session()->flash('message', 'Script erfolgreich gespeichert!');
    }
    public function editScript($scriptId)
    {
        $script = DeviceScript::where('user_id', Auth::id())->findOrFail($scriptId);
        $this->selectedScriptId = $script->id;
        $this->name = $script->name;
        $this->description = $script->description;
        $this->code = $script->code;
        $this->device_id = $script->device_id;
        $this->language = $script->language;
        $this->showEditModal = true;
    }
    public function updateScript()
    {
        $this->validate();
        $script = DeviceScript::where('user_id', Auth::id())->findOrFail($this->selectedScriptId);
        $script->update([
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'device_id' => $this->device_id,
            'language' => $this->language,
        ]);
        $this->showEditModal = false;
        $this->loadScripts();
        session()->flash('message', 'Script aktualisiert!');
    }
    public function deleteScript($scriptId)
    {
        DeviceScript::where('user_id', Auth::id())
            ->where('id', $scriptId)
            ->delete();
        $this->loadScripts();
        session()->flash('message', 'Script gelöscht!');
    }

    public function compileScript($scriptId)
    {
        $script = DeviceScript::where('user_id', Auth::id())->findOrFail($scriptId);

        // Call Arduino compile via HTTP (since Livewire can't easily use services directly in async context)
        $this->dispatch('open-compile-modal', scriptId: $scriptId);
    }

    public function uploadScript($scriptId)
    {
        $script = DeviceScript::where('user_id', Auth::id())->findOrFail($scriptId);

        if ($script->status !== 'compiled') {
            session()->flash('error', 'Script muss zuerst kompiliert werden!');
            return;
        }

        $this->dispatch('open-upload-modal', scriptId: $scriptId);
    }

    /**
     * Update script code (called from LLM Fix Apply)
     */
    public function updateScriptCode($scriptId, $newCode)
    {
        $script = DeviceScript::where('user_id', Auth::id())->findOrFail($scriptId);
        $script->update([
            'code' => $newCode,
            'status' => 'draft', // Reset status after fix
        ]);
        
        $this->loadScripts();
        session()->flash('message', 'Script-Code wurde aktualisiert!');
    }

    public function render()
    {
        $devices = \App\Models\Device::where('user_id', Auth::id())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return view('livewire.admin.device-script-management', ['devices' => $devices]);
    }
}
