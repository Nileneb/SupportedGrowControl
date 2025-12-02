<?php

namespace App\Livewire\Admin;

use App\Models\Feedback;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class FeedbackList extends Component
{
    use WithPagination;

    #[Url]
    public ?string $filterContext = null;

    #[Url]
    public ?int $filterRating = null;

    #[Url]
    public string $sortBy = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['filterContext', 'filterRating']);
    }

    public function delete(int $feedbackId): void
    {
        $feedback = Feedback::findOrFail($feedbackId);
        $feedback->delete();

        session()->flash('success', 'Feedback deleted successfully');
    }

    public function render()
    {
        $query = Feedback::with('user');

        // Apply filters
        if ($this->filterContext) {
            $query->where('context', $this->filterContext);
        }

        if ($this->filterRating) {
            $query->where('rating', $this->filterRating);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $feedbacks = $query->paginate(20);

        // Get available contexts and stats
        $contexts = Feedback::select('context')
            ->distinct()
            ->whereNotNull('context')
            ->pluck('context');

        $stats = [
            'total' => Feedback::count(),
            'with_rating' => Feedback::whereNotNull('rating')->count(),
            'avg_rating' => Feedback::whereNotNull('rating')->avg('rating'),
            'by_context' => Feedback::selectRaw('context, COUNT(*) as count')
                ->groupBy('context')
                ->pluck('count', 'context'),
        ];

        return view('livewire.admin.feedback-list', [
            'feedbacks' => $feedbacks,
            'contexts' => $contexts,
            'stats' => $stats,
        ]);
    }
}
