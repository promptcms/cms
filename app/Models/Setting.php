<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'group',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);

        if (! $setting) {
            return $default;
        }

        $decoded = json_decode($setting->value, true);

        return $decoded === null && $setting->value !== 'null' ? $setting->value : $decoded;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): static
    {
        $encodedValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encodedValue,
                'group' => $group,
                'updated_at' => now(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getGroup(string $group = 'general'): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => static::get($s->key)])
            ->all();
    }
}
