<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PresetService
{
    private const REGISTRY_URL = 'https://raw.githubusercontent.com/promptcms/plugins/main/themes/registry.json';

    private const THEME_BASE_URL = 'https://raw.githubusercontent.com/promptcms/plugins/main/themes';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all available themes from the remote registry.
     * Falls back to bundled themes if registry is unreachable.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        try {
            $data = Cache::remember('promptcms.themes', self::CACHE_TTL, function () {
                $themes = $this->fetchRemoteRegistry();

                if ($themes->isNotEmpty()) {
                    return $themes->all();
                }

                return $this->getBundledThemes()->all();
            });

            return collect($data);
        } catch (\Throwable) {
            // Cache table may not exist yet (pre-migration) — skip cache
            $themes = $this->fetchRemoteRegistry();

            return $themes->isNotEmpty() ? $themes : $this->getBundledThemes();
        }
    }

    /**
     * Get a single theme by slug (metadata only).
     *
     * @return array<string, mixed>|null
     */
    public function get(string $slug): ?array
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * Fetch a theme's full data (theme.json + theme.css) and apply it.
     */
    public function apply(string $slug): bool
    {
        $theme = $this->fetchThemeData($slug);

        if (! $theme) {
            return false;
        }

        Setting::set('active_preset', $slug);
        Setting::set('layout_head_css', $theme['css']);
        Setting::set('layout_body_class', $theme['body_class'] ?? 'min-h-screen flex flex-col antialiased');
        Setting::set('layout_body_style', $theme['body_style'] ?? '');
        Setting::set('google_fonts_url', $theme['google_fonts_url'] ?? '');

        try {
            Cache::forget('promptcms.themes');
            Cache::forget('cms-agent-context');
        } catch (\Throwable) {
            // Cache table may not exist yet
        }

        return true;
    }

    /**
     * Get the prompt addition for the active preset.
     */
    public function getActivePrompt(): ?string
    {
        $slug = Setting::get('active_preset');

        if (! $slug) {
            return null;
        }

        $theme = $this->fetchThemeData($slug);

        return $theme['prompt'] ?? null;
    }

    /**
     * Fetch theme registry from remote.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchRemoteRegistry(): Collection
    {
        try {
            $response = Http::timeout(5)->get(self::REGISTRY_URL);

            if ($response->successful()) {
                $data = $response->json('themes', []);

                return collect($data);
            }
        } catch (\Throwable $e) {
            Log::debug('Theme registry fetch failed: '.$e->getMessage());
        }

        return collect();
    }

    /**
     * Fetch full theme data (theme.json + theme.css) for a specific theme.
     *
     * @return array<string, mixed>|null
     */
    private function fetchThemeData(string $slug): ?array
    {
        try {
            return Cache::remember("promptcms.theme.{$slug}", self::CACHE_TTL, function () use ($slug) {
                $data = $this->fetchRemoteTheme($slug);

                if ($data) {
                    return $data;
                }

                return $this->getBundledTheme($slug);
            });
        } catch (\Throwable) {
            // Cache table may not exist yet — skip cache
            return $this->fetchRemoteTheme($slug) ?? $this->getBundledTheme($slug);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRemoteTheme(string $slug): ?array
    {
        try {
            $jsonResponse = Http::timeout(5)->get(self::THEME_BASE_URL."/{$slug}/theme.json");
            $cssResponse = Http::timeout(5)->get(self::THEME_BASE_URL."/{$slug}/theme.css");

            if ($jsonResponse->successful() && $cssResponse->successful()) {
                $json = $jsonResponse->json();
                $json['css'] = $cssResponse->body();

                return $json;
            }
        } catch (\Throwable $e) {
            Log::debug("Theme fetch failed for {$slug}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Get bundled themes from local files (fallback).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getBundledThemes(): Collection
    {
        $themesDir = base_path('themes');

        if (! is_dir($themesDir)) {
            return collect();
        }

        $themes = [];

        foreach (glob($themesDir.'/*/theme.json') ?: [] as $path) {
            $json = json_decode(file_get_contents($path), true);

            if ($json) {
                $themes[] = $json;
            }
        }

        return collect($themes);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getBundledTheme(string $slug): ?array
    {
        $jsonPath = base_path("themes/{$slug}/theme.json");
        $cssPath = base_path("themes/{$slug}/theme.css");

        if (! file_exists($jsonPath) || ! file_exists($cssPath)) {
            return null;
        }

        $json = json_decode(file_get_contents($jsonPath), true);

        if (! $json) {
            return null;
        }

        $json['css'] = file_get_contents($cssPath);

        return $json;
    }
}
