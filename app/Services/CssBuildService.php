<?php

namespace App\Services;

use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class CssBuildService
{
    private const HASH_FILE = 'cms/content-hash';

    /**
     * Compile CMS CSS in a background process (non-blocking).
     */
    public static function compile(): void
    {
        try {
            // Write current hash immediately so concurrent requests don't also trigger
            static::writeCurrentHash();

            $phpBinary = PHP_BINARY ?: 'php';

            Process::path(base_path())
                ->timeout(30)
                ->start("{$phpBinary} artisan cms:compile-css");
        } catch (\Throwable $e) {
            Log::warning('CMS CSS compilation failed: '.$e->getMessage());
        }
    }

    /**
     * Compile CMS CSS synchronously (blocking).
     */
    public static function compileSync(): void
    {
        try {
            $exitCode = Artisan::call('cms:compile-css');

            if ($exitCode !== 0) {
                Log::warning('CMS CSS compilation failed with exit code: '.$exitCode);

                return;
            }

            static::writeCurrentHash();
            Setting::set('css_version', (string) time());

            Log::debug('CMS CSS compiled successfully. Version: '.time());
        } catch (\Throwable $e) {
            Log::warning('CMS CSS compilation exception: '.$e->getMessage());
        }
    }

    /**
     * Check if CSS needs recompilation by comparing content hashes.
     */
    public static function needsRecompile(): bool
    {
        $cssFile = public_path('css/cms.css');

        if (! file_exists($cssFile)) {
            return true;
        }

        $storedHash = static::getStoredHash();

        if (! $storedHash) {
            return true;
        }

        return $storedHash !== static::computeCurrentHash();
    }

    /**
     * Recompile only if the content has changed since last compile.
     */
    public static function compileIfNeeded(): void
    {
        if (static::needsRecompile()) {
            static::compileSync();
        }
    }

    /**
     * Compute a hash of all CMS content that affects CSS.
     */
    public static function computeCurrentHash(): string
    {
        $parts = [];

        try {
            $pages = Node::query()
                ->ofType(NodeType::Page)
                ->with('meta')
                ->get();

            foreach ($pages as $page) {
                $content = $page->getMeta('content');

                if ($content) {
                    $parts[] = $content;
                }
            }

            // DB-driven layout parts
            foreach (['layout_header', 'layout_footer', 'layout_body_class', 'layout_head_css'] as $key) {
                $value = Setting::get($key);

                if ($value && is_string($value)) {
                    $parts[] = $value;
                }
            }

            $headerConfig = Setting::get('header_config', []);
            $footerConfig = Setting::get('footer_config', []);

            if (is_array($headerConfig)) {
                $parts[] = json_encode($headerConfig);
            }

            if (is_array($footerConfig)) {
                $parts[] = json_encode($footerConfig);
            }
        } catch (\Throwable) {
            // DB not ready
            return '';
        }

        // Include layout/template blade files
        foreach (glob(resource_path('views/layouts/*/*.blade.php')) ?: [] as $path) {
            $parts[] = file_get_contents($path);
        }

        foreach (glob(resource_path('views/templates/*.blade.php')) ?: [] as $path) {
            $parts[] = file_get_contents($path);
        }

        return md5(implode("\n", $parts));
    }

    /**
     * Get the stored content hash from the last compilation.
     */
    public static function getStoredHash(): ?string
    {
        $path = storage_path(self::HASH_FILE);

        if (! file_exists($path)) {
            return null;
        }

        return trim(file_get_contents($path));
    }

    /**
     * Write the current content hash to storage.
     */
    public static function writeCurrentHash(): void
    {
        $dir = dirname(storage_path(self::HASH_FILE));

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(storage_path(self::HASH_FILE), static::computeCurrentHash());
    }
}
