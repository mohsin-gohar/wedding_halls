<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Single source of truth for the public booking-calendar horizon.
 *
 * The calendar always opens on the current year and stays navigable for the
 * next five years. Computing the window at runtime prevents the application
 * from silently freezing in the past once a hard-coded year range expires.
 */
final class CalendarWindow
{
    public const HORIZON_YEARS = 5;

    public static function startYear(): int
    {
        return (int) now()->year;
    }

    public static function endYear(): int
    {
        return self::startYear() + self::HORIZON_YEARS;
    }

    /**
     * @return array<int, int>
     */
    public static function years(): array
    {
        return range(self::startYear(), self::endYear());
    }

    public static function clampYear(int $year): int
    {
        return max(self::startYear(), min(self::endYear(), $year));
    }

    public static function clampMonth(int $month): int
    {
        return max(1, min(12, $month));
    }

    public static function firstMonth(): Carbon
    {
        return Carbon::create(self::startYear(), 1, 1)->startOfMonth();
    }

    /**
     * The earliest month a guest may actually navigate to. Past months in the
     * current year are intentionally excluded so the booking calendar can
     * never offer a date that has already elapsed.
     */
    public static function firstNavigableMonth(): Carbon
    {
        return now()->startOfMonth();
    }

    public static function lastMonth(): Carbon
    {
        return Carbon::create(self::endYear(), 12, 1)->startOfMonth();
    }

    /**
     * Parse a strict Y-m-d value without allowing PHP's date overflow
     * (for example 2026-02-31 silently becoming 2026-03-03).
     */
    public static function parseDate(?string $value): ?Carbon
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('!Y-m-d', $value);

        if (! $date || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->startOfDay();
    }

    public static function parseMonth(?string $value): ?Carbon
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('!Y-m', $value);

        if (! $date || $date->format('Y-m') !== $value) {
            return null;
        }

        return $date->startOfMonth();
    }

    /**
     * Normalise a user-supplied month into the navigable booking window.
     */
    public static function normalizeMonth(?string $value): Carbon
    {
        $month = self::parseMonth($value) ?? self::firstNavigableMonth();

        if ($month->lt(self::firstNavigableMonth())) {
            return self::firstNavigableMonth();
        }

        if ($month->gt(self::lastMonth())) {
            return self::lastMonth();
        }

        return $month;
    }

    public static function contains(Carbon $date): bool
    {
        return $date->gte(now()->startOfDay())
            && $date->lte(self::lastMonth()->endOfMonth());
    }
}
