<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    protected $fillable = [
        'slug',
        'name',
        'version',
        'description',
        'homepage',
        'is_active',
        'migrated',
        'manifest',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'migrated' => 'boolean',
            'manifest' => 'array',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getShortcodes(): array
    {
        return $this->manifest['shortcodes'] ?? [];
    }

    public function basePath(): string
    {
        return base_path("plugins/{$this->slug}");
    }
}
