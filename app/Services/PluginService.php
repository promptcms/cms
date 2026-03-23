<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

class PluginService
{
    private const REGISTRY_URL = 'https://raw.githubusercontent.com/promptcms/plugins/main/registry.json';

    /**
     * Fetch the plugin registry (remote first, local fallback).
     *
     * @return array<string, mixed>
     */
    public function fetchRegistry(): array
    {
        // Try remote registry first
        try {
            $response = Http::timeout(5)->get(self::REGISTRY_URL);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable) {
            // Fall through to local fallback
        }

        // Local fallback for development
        $localPath = config('cms.plugin_registry_path', base_path('../promptcms-plugins/registry.json'));

        if (file_exists($localPath)) {
            $data = json_decode(file_get_contents($localPath), true);

            if (is_array($data)) {
                return $data;
            }
        }

        throw new RuntimeException('Plugin-Registry konnte nicht geladen werden (weder remote noch lokal).');
    }

    /**
     * Get available plugins from registry, enriched with local install status.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getAvailablePlugins(): Collection
    {
        $registry = $this->fetchRegistry();
        $installed = Plugin::all()->keyBy('slug');

        return collect($registry['plugins'] ?? [])->map(function (array $plugin) use ($installed) {
            $local = $installed->get($plugin['slug']);

            return [
                ...$plugin,
                'installed' => $local !== null,
                'is_active' => $local?->is_active ?? false,
                'update_available' => $local && version_compare($plugin['version'], $local->version, '>'),
            ];
        });
    }

    /**
     * Install a plugin from the registry.
     */
    public function install(string $slug): Plugin
    {
        $registry = $this->fetchRegistry();
        $pluginData = collect($registry['plugins'] ?? [])->firstWhere('slug', $slug);

        if (! $pluginData) {
            throw new RuntimeException("Plugin '{$slug}' nicht in der Registry gefunden.");
        }

        $pluginDir = base_path("plugins/{$slug}");

        if (is_dir($pluginDir)) {
            throw new RuntimeException("Plugin '{$slug}' ist bereits installiert.");
        }

        // Download ZIP
        $zipContent = $this->downloadPlugin($pluginData['download_url']);

        // Verify checksum if available
        if (! empty($pluginData['checksum_url'])) {
            $this->verifyChecksum($zipContent, $pluginData['checksum_url']);
        }

        // Extract
        $this->extractPlugin($zipContent, $pluginDir);

        // Validate plugin.json exists
        if (! file_exists("{$pluginDir}/plugin.json")) {
            $this->removeDirectory($pluginDir);
            throw new RuntimeException("Plugin '{$slug}' enthält keine gültige plugin.json.");
        }

        $manifest = json_decode(file_get_contents("{$pluginDir}/plugin.json"), true);

        return Plugin::create([
            'slug' => $slug,
            'name' => $pluginData['name'],
            'version' => $pluginData['version'],
            'description' => $pluginData['description'],
            'homepage' => $pluginData['homepage'] ?? null,
            'is_active' => false,
            'migrated' => false,
            'manifest' => $manifest,
        ]);
    }

    /**
     * Activate a plugin and run its migrations.
     */
    public function activate(string $slug): void
    {
        $plugin = Plugin::findOrFail($slug);
        $pluginDir = $plugin->basePath();

        // Run migrations if present and not yet migrated
        $migrationsPath = $pluginDir.'/'.($plugin->manifest['migrations'] ?? 'migrations');

        if (! $plugin->migrated && is_dir($migrationsPath)) {
            Artisan::call('migrate', [
                '--path' => str_replace(base_path().'/', '', $migrationsPath),
                '--no-interaction' => true,
            ]);
            $plugin->migrated = true;
        }

        $plugin->is_active = true;
        $plugin->save();

        Cache::forget('cms-agent-context');
    }

    /**
     * Deactivate a plugin (does not rollback migrations).
     */
    public function deactivate(string $slug): void
    {
        $plugin = Plugin::findOrFail($slug);
        $plugin->is_active = false;
        $plugin->save();

        Cache::forget('cms-agent-context');
    }

    /**
     * Uninstall a plugin completely.
     */
    public function uninstall(string $slug): void
    {
        $plugin = Plugin::findOrFail($slug);

        $this->deactivate($slug);

        $pluginDir = $plugin->basePath();

        if (is_dir($pluginDir)) {
            $this->removeDirectory($pluginDir);
        }

        $plugin->delete();
    }

    /**
     * Get shortcode documentation for the AI agent.
     */
    public function getShortcodeDocumentation(): string
    {
        $plugins = Plugin::where('is_active', true)->get();
        $lines = [];

        foreach ($plugins as $plugin) {
            foreach ($plugin->getShortcodes() as $shortcode) {
                $tag = $shortcode['tag'];
                $desc = $shortcode['description'] ?? '';
                $example = $shortcode['example'] ?? "[{$tag}]";

                $lines[] = "- [{$tag}]: {$desc}";

                if (! empty($shortcode['attributes'])) {
                    foreach ($shortcode['attributes'] as $attr => $attrDesc) {
                        $lines[] = "  - {$attr}: {$attrDesc}";
                    }
                }

                $lines[] = "  Beispiel: {$example}";
            }
        }

        return implode("\n", $lines);
    }

    private function downloadPlugin(string $url): string
    {
        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            throw new RuntimeException("Plugin konnte nicht heruntergeladen werden: {$url}");
        }

        return $response->body();
    }

    private function verifyChecksum(string $content, string $checksumUrl): void
    {
        $response = Http::timeout(10)->get($checksumUrl);

        if ($response->failed()) {
            return; // Checksum optional — skip if unavailable
        }

        $expectedHash = trim(explode(' ', $response->body())[0]);
        $actualHash = hash('sha256', $content);

        if (! hash_equals($expectedHash, $actualHash)) {
            throw new RuntimeException('Plugin-Checksum stimmt nicht überein. Download möglicherweise manipuliert.');
        }
    }

    private function extractPlugin(string $zipContent, string $targetDir): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'plugin_');
        file_put_contents($tempFile, $zipContent);

        $zip = new ZipArchive;

        if ($zip->open($tempFile) !== true) {
            unlink($tempFile);
            throw new RuntimeException('ZIP-Datei konnte nicht geöffnet werden.');
        }

        // Extract to temp dir first, then move (ZIP might have a wrapper directory)
        $tempExtract = sys_get_temp_dir().'/plugin_extract_'.uniqid();
        $zip->extractTo($tempExtract);
        $zip->close();
        unlink($tempFile);

        // Check if there's a single wrapper directory
        $items = array_diff(scandir($tempExtract), ['.', '..']);

        if (count($items) === 1 && is_dir($tempExtract.'/'.reset($items))) {
            $sourceDir = $tempExtract.'/'.reset($items);
        } else {
            $sourceDir = $tempExtract;
        }

        rename($sourceDir, $targetDir);

        // Cleanup temp extract if wrapper dir was moved
        if (is_dir($tempExtract)) {
            $this->removeDirectory($tempExtract);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
