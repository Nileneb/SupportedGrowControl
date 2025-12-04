<?php

namespace App\Livewire;

use App\Models\GrowroomLayout;
use App\Models\GrowroomElement;
use App\Models\WebcamFeed;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DigitalTwin extends Component
{
    public $layout;
    public $elements = [];
    public $webcams = [];
    public $selectedWebcam = null;
    public $showWebcam = true;
    public $editMode = false;

    // For dragging elements
    public $draggedElement = null;

    public function mount()
    {
        $this->loadLayout();
        $this->loadWebcams();
    }

    public function loadLayout()
    {
        $this->layout = GrowroomLayout::where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        if (!$this->layout) {
            // Create default layout
            $this->layout = GrowroomLayout::create([
                'user_id' => Auth::id(),
                'name' => 'Mein Growroom',
                'width' => 1000,
                'height' => 600,
                'background_color' => '#1a1a1a',
            ]);
        }

        $this->elements = $this->layout->elements()
            ->with('device')
            ->orderBy('z_index')
            ->get()
            ->toArray();
    }

    public function loadWebcams()
    {
        $this->webcams = WebcamFeed::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get()
            ->toArray();

        if (count($this->webcams) > 0 && !$this->selectedWebcam) {
            $this->selectedWebcam = $this->webcams[0]['id'];
        }
    }

    public function toggleEditMode()
    {
        $this->editMode = !$this->editMode;
    }

    public function toggleWebcam()
    {
        $this->showWebcam = !$this->showWebcam;
    }

    public function selectWebcam($webcamId)
    {
        $this->selectedWebcam = $webcamId;
    }

    public function updateElementPosition($elementId, $x, $y)
    {
        $element = GrowroomElement::where('growroom_layout_id', $this->layout->id)
            ->findOrFail($elementId);
        
        $element->update([
            'x_position' => (int) $x,
            'y_position' => (int) $y,
        ]);

        $this->loadLayout();
    }

    public function addElement($type, $x = 100, $y = 100)
    {
        $icons = [
            'device' => '🔌',
            'plant' => '🌱',
            'light' => '💡',
            'fan' => '🌀',
            'camera' => '📷',
            'shelf' => '▭',
            'label' => '📝',
        ];

        GrowroomElement::create([
            'growroom_layout_id' => $this->layout->id,
            'type' => $type,
            'label' => ucfirst($type),
            'x_position' => $x,
            'y_position' => $y,
            'width' => 80,
            'height' => 80,
            'icon' => $icons[$type] ?? '⬜',
            'z_index' => GrowroomElement::where('growroom_layout_id', $this->layout->id)->max('z_index') + 1,
        ]);

        $this->loadLayout();
    }

    public function deleteElement($elementId)
    {
        GrowroomElement::where('growroom_layout_id', $this->layout->id)
            ->where('id', $elementId)
            ->delete();

        $this->loadLayout();
    }

    public function render()
    {
        return view('livewire.digital-twin');
    }
}
