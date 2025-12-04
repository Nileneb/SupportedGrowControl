<?php

namespace App\Livewire\Admin;

use App\Models\WebcamFeed;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WebcamManagement extends Component
{
    public $webcams = [];
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedWebcamId = null;

    public $name = '';
    public $stream_url = '';
    public $snapshot_url = '';
    public $type = 'mjpeg';
    public $device_id = null;
    public $refresh_interval = 1000;
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'stream_url' => 'required|url',
        'snapshot_url' => 'nullable|url',
        'type' => 'required|in:mjpeg,hls,webrtc,image',
        'device_id' => 'nullable|exists:devices,id',
        'refresh_interval' => 'required|integer|min:100|max:10000',
        'is_active' => 'boolean',
    ];

    public function mount()
    {
        $this->loadWebcams();
    }

    public function loadWebcams()
    {
        $this->webcams = WebcamFeed::where('user_id', Auth::id())
            ->with('device')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'stream_url', 'snapshot_url', 'type', 'device_id', 'refresh_interval', 'is_active']);
        $this->is_active = true;
        $this->type = 'mjpeg';
        $this->refresh_interval = 1000;
        $this->showCreateModal = true;
    }

    public function createWebcam()
    {
        $this->validate();

        WebcamFeed::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'stream_url' => $this->stream_url,
            'snapshot_url' => $this->snapshot_url,
            'type' => $this->type,
            'device_id' => $this->device_id,
            'refresh_interval' => $this->refresh_interval,
            'is_active' => $this->is_active,
        ]);

        $this->showCreateModal = false;
        $this->loadWebcams();
        session()->flash('message', 'Webcam erfolgreich hinzugefügt!');
    }

    public function editWebcam($webcamId)
    {
        $webcam = WebcamFeed::where('user_id', Auth::id())->findOrFail($webcamId);

        $this->selectedWebcamId = $webcam->id;
        $this->name = $webcam->name;
        $this->stream_url = $webcam->stream_url;
        $this->snapshot_url = $webcam->snapshot_url;
        $this->type = $webcam->type;
        $this->device_id = $webcam->device_id;
        $this->refresh_interval = $webcam->refresh_interval;
        $this->is_active = $webcam->is_active;

        $this->showEditModal = true;
    }

    public function updateWebcam()
    {
        $this->validate();

        $webcam = WebcamFeed::where('user_id', Auth::id())->findOrFail($this->selectedWebcamId);

        $webcam->update([
            'name' => $this->name,
            'stream_url' => $this->stream_url,
            'snapshot_url' => $this->snapshot_url,
            'type' => $this->type,
            'device_id' => $this->device_id,
            'refresh_interval' => $this->refresh_interval,
            'is_active' => $this->is_active,
        ]);

        $this->showEditModal = false;
        $this->loadWebcams();
        session()->flash('message', 'Webcam erfolgreich aktualisiert!');
    }

    public function deleteWebcam($webcamId)
    {
        WebcamFeed::where('user_id', Auth::id())
            ->where('id', $webcamId)
            ->delete();

        $this->loadWebcams();
        session()->flash('message', 'Webcam erfolgreich gelöscht!');
    }

    public function toggleActive($webcamId)
    {
        $webcam = WebcamFeed::where('user_id', Auth::id())->findOrFail($webcamId);
        $webcam->update(['is_active' => !$webcam->is_active]);
        $this->loadWebcams();
    }

    public function render()
    {
        $devices = \App\Models\Device::where('user_id', Auth::id())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.webcam-management', ['devices' => $devices]);
    }
}
