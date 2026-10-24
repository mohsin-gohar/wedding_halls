<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HallReviews extends Component
{
    public string $hallId = '';

    public int $rating = 5;

    public string $title = '';

    public string $body = '';

    public bool $submitted = false;

    public string $error = '';

    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'title' => 'nullable|string|max:120',
        'body' => 'required|string|min:10|max:2000',
    ];

    public function mount(string $hallId): void
    {
        $this->hallId = $hallId;
    }

    public function submit(): void
    {
        if (! Auth::check()) {
            $this->error = 'Please log in to leave a review.';

            return;
        }

        $this->validate();

        $userId = (string) Auth::id();

        // Prevent duplicate reviews from the same user for the same hall
        $existing = Review::where('user_id', $userId)->where('hall_id', $this->hallId)->first();
        if ($existing) {
            $this->error = 'You have already reviewed this venue.';

            return;
        }

        Review::create([
            'user_id' => $userId,
            'hall_id' => $this->hallId,
            'rating' => $this->rating,
            'title' => trim($this->title) ?: null,
            'body' => trim($this->body),
            'status' => Review::STATUS_PENDING,
        ]);

        $this->rating = 5;
        $this->title = '';
        $this->body = '';
        $this->submitted = true;
        $this->error = '';
    }

    public function render()
    {
        $reviews = Review::approved()
            ->where('hall_id', $this->hallId)
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        $average = Review::averageForHall($this->hallId);
        $count = Review::countForHall($this->hallId);

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = Review::approved()->where('hall_id', $this->hallId)->where('rating', $i)->count();
        }

        return view('livewire.hall-reviews', [
            'reviews' => $reviews,
            'average' => $average,
            'count' => $count,
            'distribution' => $distribution,
        ]);
    }
}
