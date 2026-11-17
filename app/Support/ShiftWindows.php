<?php

namespace App\Support;

class ShiftWindows
{
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
