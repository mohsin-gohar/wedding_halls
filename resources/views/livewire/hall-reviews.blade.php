<div class="mt-12 space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <span class="text-[11px] uppercase font-semibold tracking-[0.16em] text-[#8F7239] dark:text-[#C6A15B] block">Guest Reviews</span>
            <h2 class="font-serif-luxury text-2xl sm:text-3xl text-[#171513] dark:text-white font-normal mt-1">What Couples Say</h2>
        </div>
        <div class="text-right">
            <div class="text-3xl font-serif-luxury text-[#8F7239] dark:text-[#C6A15B]">{{ number_format($average, 1) }}</div>
            <div class="text-xs text-stone-500">{{ $count }} {{ Str::plural('review', $count) }}</div>
        </div>
    </div>

    @if($count > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6">
        <div class="flex items-center gap-3">
            <div class="flex text-[#C6A15B] text-lg">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= round($average)) ★ @else ☆ @endif
                @endfor
            </div>
            <span class="text-sm text-stone-600 dark:text-stone-300">{{ number_format($average, 1) }} / 5</span>
        </div>
        <div class="space-y-1">
            @for($i = 5; $i >= 1; $i--)
                @php $pct = $count > 0 ? round(($distribution[$i] / $count) * 100) : 0; @endphp
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-6 text-stone-500">{{ $i }}★</span>
                    <div class="flex-1 h-2 bg-stone-200 dark:bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full bg-[#C6A15B]" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="w-8 text-right text-stone-500">{{ $distribution[$i] }}</span>
                </div>
            @endfor
        </div>
    </div>
    @endif

    <div class="space-y-4">
        @forelse($reviews as $review)
            <div class="bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/20 rounded-xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#C6A15B]/20 flex items-center justify-center text-[#8F7239] dark:text-[#C6A15B] font-semibold text-sm">
                            {{ strtoupper(substr($review->user->name ?? 'G', 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-medium text-sm text-[#171513] dark:text-white">{{ $review->user->name ?? 'Guest' }}</div>
                            <div class="text-[11px] text-stone-500">{{ $review->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                    <div class="text-[#C6A15B]">
                        @for($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor
                    </div>
                </div>
                @if($review->title)
                    <h4 class="font-semibold text-[#171513] dark:text-white mb-1">{{ $review->title }}</h4>
                @endif
                <p class="text-sm text-stone-600 dark:text-stone-300 leading-relaxed">{{ $review->body }}</p>
            </div>
        @empty
            <div class="text-center py-10 text-stone-500 text-sm">
                No reviews yet. Be the first to share your experience!
            </div>
        @endforelse
    </div>

    <div class="bg-white dark:bg-[#1a1714] border border-[#E7E5E4] dark:border-[#C6A15B]/30 rounded-2xl p-6 space-y-4">
        <h3 class="font-serif-luxury text-xl text-[#171513] dark:text-white">Share Your Experience</h3>

        @if($submitted)
            <div class="p-4 rounded-xl bg-[#C6A15B]/15 border border-[#C6A15B]/60 text-[#8F7239] dark:text-[#E8DCBE] text-sm">
                Thank you! Your review has been submitted for moderation and will appear once approved.
            </div>
        @else
            @if($error)
                <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">{{ $error }}</div>
            @endif

            @guest
                <p class="text-sm text-stone-600 dark:text-stone-300">
                    <a href="{{ route('login') }}" class="text-[#8F7239] dark:text-[#C6A15B] font-semibold hover:underline">Log in</a> to leave a review.
                </p>
            @else
                <form wire:submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 dark:text-stone-300 mb-1">Rating</label>
                        <div class="flex gap-1 text-2xl cursor-pointer text-[#C6A15B]">
                            @for($i = 1; $i <= 5; $i++)
                                <span wire:click="$set('rating', {{ $i }})">{{ $i <= $rating ? '★' : '☆' }}</span>
                            @endfor
                        </div>
                        @error('rating') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 dark:text-stone-300 mb-1">Title (optional)</label>
                        <input type="text" wire:model="title" class="w-full rounded-lg border border-stone-300 dark:border-white/10 bg-white dark:bg-[#171513] px-3 py-2 text-sm" placeholder="e.g. Magical evening">
                        @error('title') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 dark:text-stone-300 mb-1">Your Review</label>
                        <textarea wire:model="body" rows="4" class="w-full rounded-lg border border-stone-300 dark:border-white/10 bg-white dark:bg-[#171513] px-3 py-2 text-sm" placeholder="Share your experience..."></textarea>
                        @error('body') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-[#C6A15B] text-[#171513] font-semibold text-sm hover:bg-[#8F7239] hover:text-white transition">
                        Submit Review
                    </button>
                </form>
            @endguest
        @endif
    </div>
</div>
