<?php

namespace App\Support;

class ShiftWindows
{
    /** Shifts a guest may reserve individually. FULL_DAY is an exclusive combination. */
    public const BOOKABLE_KEYS = ['AFTERNOON', 'EVENING', 'NIGHT'];

    public const KEYS = ['AFTERNOON', 'EVENING', 'NIGHT', 'FULL_DAY'];

    /**
     * @return array<string, array{start: string, end: string}>
     */
    public static function defaults(): array
    {
        return [
            'AFTERNOON' => ['start' => '12:00', 'end' => '16:00'],
            'EVENING' => ['start' => '18:00', 'end' => '22:00'],
            'NIGHT' => ['start' => '22:00', 'end' => '02:00'],
            'FULL_DAY' => ['start' => '12:00', 'end' => '02:00'],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, array{start: string, end: string}>
     */
    public static function normalize(?array $stored): array
    {
        $defaults = self::defaults();
        $windows = [];

        foreach (self::KEYS as $key) {
            $row = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            $start = self::validTime($row['start'] ?? null) ? $row['start'] : $defaults[$key]['start'];
            $end = self::validTime($row['end'] ?? null) ? $row['end'] : $defaults[$key]['end'];
            $windows[$key] = ['start' => $start, 'end' => $end];
        }

        return $windows;
    }

    /**
     * Shared presenter for every guest-facing shift label/time. Never format
     * stored windows directly: always normalize first so bad data cannot leak.
     *
     * @param  array<string, array{start: string, end: string}>  $window
     */
    public static function formatWindow(array $window): string
    {
        return self::formatTime($window['start'] ?? '00:00').' - '.self::formatTime($window['end'] ?? '00:00');
    }

    /**
     * @param  array<string, mixed>|null  $stored
     */
    public static function labelForSlot(?array $stored, string $slot): string
    {
        $slot = strtoupper($slot);
        $windows = self::normalize($stored);

        if (! isset($windows[$slot])) {
            return self::formatWindow($windows['EVENING']);
        }

        return self::formatWindow($windows[$slot]);
    }

    /**
     * One canonical sentence for FAQs, chat bots and seeders.
     *
     * @param  array<string, mixed>|null  $stored
     * @param  array<int, string>  $keys
     */
    public static function summary(?array $stored, array $keys = self::BOOKABLE_KEYS): string
    {
        $windows = self::normalize($stored);
        $parts = [];

        foreach ($keys as $key) {
            $key = strtoupper($key);
            if (isset($windows[$key])) {
                $parts[] = ucfirst(strtolower($key)).' ('.self::formatWindow($windows[$key]).')';
            }
        }

        return implode(' | ', $parts);
    }

    private static function formatTime(string $time): string
    {
        if (! self::validTime($time)) {
            return $time;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));
        $suffix = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12;

        return sprintf('%d:%02d %s', $displayHour === 0 ? 12 : $displayHour, $minute, $suffix);
    }

    public static function validTime(mixed $time): bool
    {
        return is_string($time) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    /**
     * True when the two shifts overlap, or the gap between them is shorter
     * than the configured turnover buffer. A zero buffer only rejects overlap.
     */
    public static function conflicts(array $windows, string $left, string $right, int $bufferMinutes): bool
    {
        $left = strtoupper($left);
        $right = strtoupper($right);
        $windows = self::normalize($windows);

        if (! isset($windows[$left], $windows[$right])) {
            return $left === $right || $left === 'FULL_DAY' || $right === 'FULL_DAY';
        }

        [$leftStart, $leftEnd] = self::interval($windows[$left]);
        [$rightStart, $rightEnd] = self::interval($windows[$right]);

        return self::intervalsConflict($leftStart, $leftEnd, $rightStart, $rightEnd, $bufferMinutes)
            || self::intervalsConflict($leftStart, $leftEnd, $rightStart + 1440, $rightEnd + 1440, $bufferMinutes)
            || self::intervalsConflict($leftStart + 1440, $leftEnd + 1440, $rightStart, $rightEnd, $bufferMinutes);
    }

    /**
     * @param  array{start: string, end: string}  $window
     * @return array{0: int, 1: int}
     */
    public static function interval(array $window): array
    {
        $start = self::minutes($window['start']);
        $end = self::minutes($window['end']);
        if ($end <= $start) {
            $end += 1440;
        }

        return [$start, $end];
    }

    private static function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }

    private static function intervalsConflict(int $leftStart, int $leftEnd, int $rightStart, int $rightEnd, int $bufferMinutes): bool
    {
        if ($leftEnd <= $rightStart) {
            return ($rightStart - $leftEnd) < $bufferMinutes;
        }

        if ($rightEnd <= $leftStart) {
            return ($leftStart - $rightEnd) < $bufferMinutes;
        }

        return true;
    }
}
