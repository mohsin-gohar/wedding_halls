<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Client as OpenAIClient;
use Illuminate\Support\Str;

class AIMoodBoardService
{
    protected OpenAIClient $client;
    public string $lastPrompt = '';

    public function __construct()
    {
        $this->client = new OpenAIClient(
            config('services.openai.key') ?: env('OPENAI_API_KEY')
        );
    }

    public function generateMoodBoard(array $preferences): array
    {
        $theme = $preferences['theme'] ?? 'Royal Mughal';
        $colorPalette = $preferences['color_palette'] ?? ['Gold', 'Maroon', 'Cream'];
        $guestCount = $preferences['guest_count'] ?? 200;
        
        // Build AI prompt
        $prompt = $this->buildAIPrompt($theme, $colorPalette, $guestCount);
        $this->lastPrompt = $prompt;
        
        // Call AI service
        try {
            $response = $this->client->images()->generate([
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'size' => '1024x1024',
                'quality' 'standard',
                'n' => 1,
            ]);
            
            $imageUrl = $response['data'][0]['url'];
            
            // Generate vendor matches based on theme
            $vendorMatches = $this->matchVendorsByTheme($theme, $colorPalette);
            
            // Generate color scheme
            $colorScheme = $this->generateColorScheme($colorPalette);
            
            return [
                'image_url' => $imageUrl,
                'vendor_matches' => $vendorMatches,
                'color_scheme' => $colorScheme,
                'style_notes' => $this->generateStyleNotes($theme, $colorPalette),
            ];
        } catch (\Exception $e) {
            Log::error('AI Mood Board generation failed: ' . $e->getMessage());
            
            // Fallback: generate deterministic mood board data
            return $this->fallbackMoodBoard($theme, $colorPalette, $guestCount);
        }
    }
    
    private function buildAIPrompt(string $theme, array $colors, int $guests): string
    {
        $colorStr = implode(', ', $colors);
        
        return "Create a luxury wedding mood board for $guests guests with $theme theme featuring color palette $colorStr. " .
            "Include décor elements, table settings, lighting, floral arrangements, and venue styling. " .
            "Focus on elegant, sophisticated design suitable for high-end celebrations. " .
            "Generate a visual concept that captures the essence of $theme wedding style.";
    }
    
    private function matchVendorsByTheme(string $theme, array $colors): array
    {
        // In a real implementation, this would query the vendor database
        // For now, return vendor category suggestions based on theme
        
        $themeLower = strtolower($theme);
        
        $matches = match($themeLower) {
            'royal mughal', 'mughal' => [
                'category' => 'luxury_catering',
                'styles' => ['traditional', 'opulent', 'gold_accents'],
            ],
            'modern', 'contemporary' => [
                'category' => 'modern_catering', 
                'styles' => ['minimalist', 'sleek', 'innovative'],
            ],
            'classic', 'traditional' => [
                'category' 'classic_catering',
                'styles' => ['timeless', 'elegant', 'formal'],
            ],
            'beach', 'destination' => [
                'category' => 'beach_catering',
                'styles' => ['light', 'fresh', 'tropical'],
            ],
            default => [
                'category' => 'luxury_catering',
                'styles' => ['elegant', 'versatile', 'customizable'],
            ],
        };
        
        return $matches;
    }
    
    private function generateColorScheme(array $colors): array
    {
        // Enhance and expand the color palette
        $scheme = [
            'primary' => $colors[0] ?? 'Gold',
            'secondary' => $colors[1] ?? 'Maroon',
            'accent' => $colors[2] ?? 'Cream',
            'background' => 'Ivory',
            'text' => 'Deep Black',
        ];
        
        // Add complementary colors based on primary
        switch (strtolower($scheme['primary'])) {
            case 'gold':
            case 'champagne':
                $scheme['complement'] = 'Maroon or Burgundy';
                break;
            case 'maroon':
            case 'burgundy':
                $scheme['complement'] = 'Gold or Cream';
                break;
            case 'cream':
            case 'ivory':
                $scheme['complement'] = 'Gold or Blush';
                break;
            default:
                $scheme['complement'] = 'Coordinating accent color';
        }
        
        return $scheme;
    }
    
    private function generateStyleNotes(string $theme, array $colors): string
    {
        $colorStr = implode(', ', $colors);
        
        return "{$theme} wedding style with {$colorStr} color palette. " .
            "Elegant décor with traditional elements mixed with modern touches. " .
            "Floral arrangements featuring seasonal blooms in the specified color scheme. " .
            "Lighting design emphasizing warm, golden tones. " .
            "Table settings with premium linens and fine tableware. " .
            "Overall aesthetic: sophisticated, romantic, and luxurious.";
    }
    
    private function fallbackMoodBoard(string $theme, array $colors, int $guests): array
    {
        // Generate fallback mood board without AI
        $colorStr = implode(', ', $colors);
        
        return [
            'image_url' => null,
            'vendor_matches' => $this->matchVendorsByTheme($theme, $colors),
            'color_scheme' => $this->generateColorScheme($colors),
            'style_notes' => $this->generateStyleNotes($theme, $colors),
        ];
    }
}