<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    protected $casts = ['value' => 'array'];

    /** Scalar-friendly getter (unwraps single-value JSON arrays). */
    public static function get(string $key, $default = null)
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        $value = $setting->value;

        if (is_array($value) && count($value) === 1 && array_is_list($value)) {
            return $value[0];
        }

        return $value ?? $default;
    }

    public static function set(string $key, $value, ?string $type = null, ?string $description = null): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type ?? (is_array($value) ? 'json' : 'scalar'),
                'description' => $description,
            ]
        );
    }
}
