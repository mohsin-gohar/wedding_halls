@props([
    'currentStep' => 1
])

@php
    $steps = [
        1 => [
            'name' => 'Explore Venues',
            'desc' => 'Select Luxury Hall',
            'route' => 'public.venues',
        ],
        2 => [
            'name' => 'Check Shifts',
            'desc' => 'Afternoon, Evening, Night',
            'route' => 'public.availability',
        ],
        3 => [
            'name' => 'Catering & Menus',
            'desc' => 'Food & Headcount Calc',
            'route' => 'public.menus',
        ],
        4 => [
            'name' => 'Reserve Date',
            'desc' => 'Instant Redis Lock',
            'route' => null, // modal
        ],
        5 => [
            'name' => 'Guest RSVP',
            'desc' => 'Digital WhatsApp RSVPs',
            'route' => 'public.rsvp',
        ],
    ];
@endphp

<!-- AppFlow Step-by-Step Progress Track (Riwaj Grand Obsidian Luxury Standard) -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-4">
    <div class="glass-panel p-4 sm:p-5 border border-[#C6A15B]/25 bg-[#171513]/90 backdrop-blur-xl shadow-2xl rounded-2xl">
        
        <!-- Header Sub-Label -->
        <div class="flex items-center justify-between mb-3 pb-2 border-b border-white/5">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-[#C6A15B] animate-pulse"></span>
                <span class="text-[10px] uppercase tracking-[0.22em] text-white/70 font-medium">
                    Booking Journey • Step {{ $currentStep }} of 5
                </span>
            </div>
            <span class="text-[10px] text-white/40 font-mono tracking-wider hidden sm:inline">
                RESERVATION ARCHITECTURE
            </span>
        </div>

        <!-- 5 Steps Horizontal Stepper -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @foreach($steps as $stepNum => $step)
                @php
                    $isCurrent = $stepNum === $currentStep;
                    $isPassed = $stepNum < $currentStep;
                @endphp

                @if($step['route'])
                    <a href="{{ route($step['route']) }}" class="flex items-center gap-3 p-2.5 rounded-xl border transition-all duration-200 group {{ $isCurrent ? 'bg-white/10 border-white text-white shadow-sm' : ($isPassed ? 'bg-[#171513]/85 border-[#C6A15B]/40 hover:border-[#C6A15B]/60' : 'bg-white/[0.02] border-white/5 hover:border-white/20') }}">
                @else
                    <button type="button" onclick="Livewire.dispatch('openWalkInModal')" class="flex items-center gap-3 p-2.5 rounded-xl border transition-all duration-200 text-left w-full {{ $isCurrent ? 'bg-white/10 border-white text-white shadow-sm' : ($isPassed ? 'bg-[#171513]/85 border-[#C6A15B]/40' : 'bg-white/[0.02] border-white/5 hover:border-white/20') }}">
                @endif

                    <!-- Step Number / Status Icon -->
                    <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0 transition {{ $isCurrent ? 'bg-[#C6A15B] text-[#171513]' : ($isPassed ? 'bg-[#C6A15B] text-white' : 'bg-white/10 text-white/50 group-hover:text-white') }}">
                        @if($isPassed)
                            <span>✓</span>
                        @else
                            <span>{{ $stepNum }}</span>
                        @endif
                    </div>

                    <!-- Step Label & Desc -->
                    <div class="min-w-0 flex-1">
                        <span class="text-[11px] font-medium tracking-wide uppercase truncate block {{ $isCurrent ? 'text-white' : ($isPassed ? 'text-[#C6A15B]' : 'text-white/60 group-hover:text-white') }}">
                            {{ $step['name'] }}
                        </span>
                        <span class="text-[9px] text-white/40 truncate block font-light">
                            {{ $step['desc'] }}
                        </span>
                    </div>

                @if($step['route'])
                    </a>
                @else
                    </button>
                @endif
            @endforeach
        </div>

    </div>
</div>
