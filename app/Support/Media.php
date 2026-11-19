<?php

namespace App\Support;

class Media
{
    public static function publicUrl(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return asset(ltrim($path, '/'));
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }

    /**
     * Convert any video URL (YouTube, Vimeo, direct file) into an embeddable URL.
     */
    public static function videoEmbedUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        // Already an embed / direct file
        if (str_contains($url, '/embed/') || preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $url)) {
            // YouTube embed passthrough (normalize to a clean embed URL)
            if (preg_match('#youtube(?:-nocookie)?\.com/embed/([a-zA-Z0-9_-]+)#', $url, $m)) {
                return 'https://www.youtube.com/embed/'.$m[1].'?rel=0';
            }
            // Direct file
            if (preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $url)) {
                return $url;
            }
        }

        // YouTube variants
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            $id = '';
            if (str_contains($url, 'youtu.be/')) {
                $path = parse_url($url, PHP_URL_PATH) ?? '';
                $id = trim($path, '/');
                $id = explode('/', $id)[0];
                $id = explode('?', $id)[0];
            } elseif (preg_match('#youtube\.com/(?:watch|embed|v|e|live)/?#', $url)) {
                if (str_contains($url, 'youtube.com/watch')) {
                    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $q);
                    $id = $q['v'] ?? '';
                } elseif (preg_match('#youtube\.com/(?:embed|v|e|live)/([a-zA-Z0-9_-]+)#', $url, $m)) {
                    $id = $m[1] ?? '';
                }
            } elseif (str_contains($url, 'youtube.com/shorts/')) {
                preg_match('#shorts/([a-zA-Z0-9_-]+)#', $url, $m);
                $id = $m[1] ?? '';
            }
            // Fallback: regex any 11-char id
            if ($id === '' && preg_match('#[?&/]([a-zA-Z0-9_-]{11})(?:[?&]|$)#', $url, $m)) {
                $id = $m[1];
            }
            if ($id !== '') {
                return 'https://www.youtube.com/embed/'.$id.'?rel=0';
            }
        }

        // Vimeo
        if (str_contains($url, 'vimeo.com')) {
            preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m);
            if (! empty($m[1])) {
                return 'https://player.vimeo.com/video/'.$m[1];
            }
        }

        // Dailymotion
        if (str_contains($url, 'dailymotion.com') || str_contains($url, 'dai.ly')) {
            preg_match('#(?:video/|dai\.ly/)([a-zA-Z0-9]+)#', $url, $m);
            if (! empty($m[1])) {
                return 'https://www.dailymotion.com/embed/video/'.$m[1];
            }
        }

        return $url;
    }

    public static function isDirectVideo(?string $url): bool
    {
        $url = (string) $url;

        return (bool) preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $url);
    }

    public static function youtubeThumbnail(?string $url): ?string
    {
        $embed = self::videoEmbedUrl($url);
        if (preg_match('#youtube\.com/embed/([a-zA-Z0-9_-]+)#', $embed, $m)) {
            return 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
        }

        return null;
    }
}
