<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Review;
use App\Services\NotificationService;
use App\Support\PermissionMatrix;
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

    /** Set from the signed-in moderator's reviews.moderate permission. */
    public bool $canModerate = false;

    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'title' => 'nullable|string|max:120',
        'body' => 'required|string|min:10|max:2000',
    ];

    public function mount(string $hallId): void
    {
        $this->hallId = $hallId;

        $user = Auth::user();
        $this->canModerate = PermissionMatrix::userCan($user, 'reviews.moderate');
    }

    /** Reviews awaiting a moderation decision for this hall only. */
    public function getPendingReviewsProperty()
    {
        if (! $this->canModerate) {
            return collect();
        }

        return Review::query()
            ->where('hall_id', $this->hallId)
            ->where('status', Review::STATUS_PENDING)
            ->with('user:id,name,email')
            ->latest()
            ->take(50)
            ->get();
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

    public function approveReview(string $reviewId): void
    {
        $this->moderateReview($reviewId, Review::STATUS_APPROVED);
    }

    public function rejectReview(string $reviewId): void
    {
        $this->moderateReview($reviewId, Review::STATUS_REJECTED);
    }

    private function moderateReview(string $reviewId, string $newStatus): void
    {
        $user = Auth::user();

        if (! $this->canModerate || ! PermissionMatrix::userCan($user, 'reviews.moderate')) {
            abort(403, 'You do not have permission to moderate reviews.');
        }

        if (! in_array($newStatus, [Review::STATUS_APPROVED, Review::STATUS_REJECTED], true)) {
            abort(422, 'Invalid review moderation status.');
        }

        // Hall scope is part of the query so a tampered review id cannot be
        // used to moderate a review belonging to another venue.
        $review = Review::query()
            ->where('id', $reviewId)
            ->where('hall_id', $this->hallId)
            ->firstOrFail();

        if ($review->status === $newStatus) {
            return;
        }

        $verifiedBooking = $this->verifiedBookingFor($review);

        $review->update([
            'status' => $newStatus,
            'moderated_at' => now(),
            'moderated_by' => $user->id,
            'is_verified_booking' => $newStatus === Review::STATUS_APPROVED && $verifiedBooking !== null,
            'booking_id' => $verifiedBooking?->id ?? $review->booking_id,
        ]);

        AuditLog::log(
            $newStatus === Review::STATUS_APPROVED ? 'REVIEW_APPROVED' : 'REVIEW_REJECTED',
            "Super Admin/Moderator {$user->name} {$newStatus} review [{$review->id}] for hall [{$this->hallId}].",
            $review->hall?->tenant_id,
            'Review',
            $review->id
        );

        app(NotificationService::class)->send(
            (string) $review->user_id,
            'REVIEW_MODERATED',
            $newStatus === Review::STATUS_APPROVED ? 'Your review was approved' : 'Your review was rejected',
            $newStatus === Review::STATUS_APPROVED
                ? 'Thank you — your review is now visible on the venue page.'
                : 'Your review did not pass moderation. Please contact support if you believe this is an error.',
            null,
            ['review_id' => $review->id, 'status' => $newStatus]
        );
    }

    /** A review is verified only when its author has a real booking for this hall. */
    private function verifiedBookingFor(Review $review): ?Booking
    {
        if ($review->booking_id) {
            return Booking::query()
                ->where('id', $review->booking_id)
                ->where('hall_id', $this->hallId)
                ->where('customer_id', $review->user_id)
                ->first();
        }

        return Booking::query()
            ->where('hall_id', $this->hallId)
            ->where('customer_id', $review->user_id)
            ->whereIn('status', ['CONFIRMED', 'COMPLETED'])
            ->latest()
            ->first();
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
