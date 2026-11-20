<div class="space-y-8 max-w-7xl mx-auto pb-16">
    <!-- Header Banner -->
    <div class="bg-white dark:bg-[#171513] border border-[#C6A15B]/50 gold-foil-border p-6 sm:p-8 shadow-xl transition-colors duration-200">
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 border border-[#C6A15B]/50 bg-[#C6A15B]/10 text-[#8F7239] dark:text-[#C6A15B] text-[10px] font-mono uppercase tracking-widest font-bold">
                🖼️ Vendor Marketplace ERP • Portfolio
            </div>
            <h1 class="font-serif-luxury text-3xl sm:text-4xl text-[#171513] dark:text-white font-normal">
                Portfolio Media
            </h1>
            <p class="text-xs text-stone-600 dark:text-stone-300 font-sans max-w-2xl leading-relaxed">
                Upload and curate images / videos that appear on your public marketplace profile gallery.
            </p>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#C6A15B] text-xs font-semibold shadow-md">
            ✓ {{ $feedbackMessage }}
        </div>
    @endif

    @if($errorMessage)
        <div class="p-4 bg-[#171513]/90 border border-[#C6A15B]/60 text-[#F8F5EF] text-xs font-semibold shadow-md">
            ⚠️ {{ $errorMessage }}
        </div>
    @endif

    <!-- Upload form -->
    @if($this->hasPortalPermission('portfolio.manage'))
    <div class="bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-5 shadow-sm dark:shadow-xl">
        <form wire:submit.prevent="addMedia" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-1">
                <label for="media-file" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Image / Video</label>
                <input id="media-file" type="file" wire:model="file" accept="image/jpeg,image/png,image/webp,image/jpg,video/mp4,video/quicktime,video/x-matroska" class="input-luxury w-full text-sm">
            </div>
            <div class="w-full sm:w-40">
                <label for="media-category" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Category</label>
                <input id="media-category" type="text" wire:model="mediaCategory" maxLength="80" placeholder="e.g. Baraat" class="input-luxury w-full text-sm">
            </div>
            <div class="w-full sm:w-64">
                <label for="media-caption" class="block text-[10px] font-semibold uppercase tracking-[0.08em] text-stone-600 dark:text-stone-400 mb-1.5">Caption</label>
                <input id="media-caption" type="text" wire:model="mediaCaption" maxLength="150" placeholder="e.g. Baraat stage" class="input-luxury w-full text-sm">
            </div>
            <button type="submit" class="btn-gold !h-11 !px-6 text-[11px] font-mono uppercase self-end">
                Upload
            </button>
        </form>
    </div>
    @endif

    <!-- Media grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($media as $item)
            @php
                $url = $this->mediaUrls[$item['id']] ?? null;
            @endphp
            <figure class="relative rounded-xl overflow-hidden border border-[#C6A15B]/30 aspect-[4/3] bg-[#171513] group">
                @if($item['type'] === 'VIDEO')
                    <video src="{{ $url }}" muted class="w-full h-full object-cover"></video>
                @else
                    <img src="{{ $url }}" alt="{{ $item['caption'] ?? 'Portfolio image' }}" loading="lazy" class="w-full h-full object-cover">
                @endif
                @if($this->hasPortalPermission('portfolio.manage'))
                    <button type="button" wire:click="removeMedia('{{ $item['id'] }}')" class="absolute top-2 right-2 w-8 h-8 rounded-full bg-[#171513]/85 text-[#C6A15B] text-xs font-bold opacity-0 group-hover:opacity-100 transition">
                        ×
                    </button>
                @endif
                <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-3 py-2 text-[10px] text-white font-sans">
                    {{ $item['caption'] ?? 'Portfolio media' }}@if(! empty($item['category'])) · {{ $item['category'] }}@endif
                </figcaption>
            </figure>
        @empty
            <div class="col-span-full bg-white dark:bg-[#171513] border border-[#E7E5E4] dark:border-[#C6A15B]/30 p-10 text-center">
                <p class="text-sm text-stone-600 dark:text-stone-300">No portfolio media yet — upload your first image or video.</p>
            </div>
        @endforelse
    </div>
</div>
