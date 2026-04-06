<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UpdateChecker
{
    private const RELEASES_URL = 'https://api.github.com/repos/promptcms/cms/releases/latest';

    private const RELEASE_PAGE_URL = 'https://github.com/promptcms/cms/releases/latest';

    private const CACHE_KEY = 'promptcms.update_checker.latest';

    private const CACHE_TTL_SECONDS = 21600; // 6 hours

    /**
     * @return array{tag: string, name: string, url: string, published_at: ?string}|null
     */
    public function getLatestRelease(): ?array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): ?array {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['Accept' => 'application/vnd.github+json'])
                    ->get(self::RELEASES_URL);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();

                if (! is_array($data) || ! isset($data['tag_name'])) {
                    return null;
                }

                return [
                    'tag' => (string) $data['tag_name'],
                    'name' => (string) ($data['name'] ?? $data['tag_name']),
                    'url' => (string) ($data['html_url'] ?? self::RELEASE_PAGE_URL),
                    'published_at' => isset($data['published_at']) ? (string) $data['published_at'] : null,
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    public function getCurrentVersion(): string
    {
        return (string) config('app.version', 'dev');
    }

    public function isDevVersion(): bool
    {
        $current = $this->getCurrentVersion();

        return $current === 'dev' || str_contains($current, '-dev');
    }

    public function hasUpdate(): bool
    {
        if ($this->isDevVersion()) {
            return false;
        }

        $latest = $this->getLatestRelease();

        if ($latest === null) {
            return false;
        }

        return version_compare(
            $this->normalizeVersion($latest['tag']),
            $this->normalizeVersion($this->getCurrentVersion()),
            '>'
        );
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim($version, 'vV');
    }
}
