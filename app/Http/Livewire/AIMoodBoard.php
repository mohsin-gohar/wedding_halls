<?php

namespace App\Http\Livewire;

use App\Models\MoodBoardGeneration;
use App\Services\AIMoodBoardService;
use Livewire\Component;

class AIMoodBoard extends Component
{
    public string $theme = 'Royal Mughal';

    public string $color_palette = 'Gold,Maroon,Cream';

    public int $guest_count = 200;

    public string $ai_result = '';

    public bool $is_generating = false;

    public string $prompt = '';

    public array $vendor_matches = [];

    public array $color_scheme = [];

    public function mount(): void
    {
        // Load any previous generation if exists
        $lastGen = MoodBoardGeneration::where('tenant_id', auth()->user()?->tenant?->id)
            ->latest('created_at')
            ->first();

        if ($lastGen && $lastGen->image_url) {
            $this->ai_result = json_encode([
                'image_url' => $lastGen->image_url,
                'vendor_matches' => $lastGen->vendor_matches ?? [],
                'color_scheme' => $lastGen->color_scheme ?? [],
            ]);
        }
    }

    public function generate(): void
    {
        $this->is_generating = true;
        $this->ai_result = '';
        $this->vendor_matches = [];
        $this->color_scheme = [];

        $service = new AIMoodBoardService;
        $result = $service->generateMoodBoard([
            'theme' => $this->theme,
            'color_palette' => explode(',', $this->color_palette),
            'guest_count' => $this->guest_count,
        ]);

        $this->ai_result = json_encode($result);
        $this->vendor_matches = $result['vendor_matches'] ?? [];
        $this->color_scheme = $result['color_scheme'] ?? [];
        $this->prompt = $service->lastPrompt ?? '';

        // Save to database
        $tenant = auth()->user()?->tenant;
        if ($tenant) {
            MoodBoardGeneration::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'prompt' => $this->prompt,
                'status' => 'COMPLETED',
                'image_url' => $result['image_url'] ?? '',
                'vendor_matches' => $result['vendor_matches'] ?? [],
                'color_scheme' => $result['color_scheme'] ?? [],
            ]);
        }

        $this->is_generating = false;
    }

    public function copyPrompt(): void
    {
        // Copy prompt to clipboard
        // This would use JS to copy to clipboard
    }

    public function render()
    {
        return view('livewire.ai-mood-board');
    }
}
