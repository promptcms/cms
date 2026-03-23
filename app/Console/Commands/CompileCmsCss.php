<?php

namespace App\Console\Commands;

use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Setting;
use App\Services\CssBuildService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

#[Signature('cms:compile-css')]
#[Description('Compile Tailwind CSS from all CMS content')]
class CompileCmsCss extends Command
{
    public function handle(): int
    {
        $sourceFile = storage_path('cms/content-source.html');
        $cssInput = storage_path('cms/input.css');
        $cssOutput = public_path('css/cms.css');

        if (! is_dir(dirname($sourceFile))) {
            mkdir(dirname($sourceFile), 0755, true);
        }

        if (! is_dir(dirname($cssOutput))) {
            mkdir(dirname($cssOutput), 0755, true);
        }

        file_put_contents($sourceFile, $this->collectAllHtml());
        file_put_contents($cssInput, $this->buildCssInput());

        $binary = $this->resolveTailwindBinary();

        if (! $binary) {
            $this->warn('No Tailwind CLI available — Tailwind CDN will be used as browser fallback.');

            return self::SUCCESS;
        }

        $env = ['PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin'];

        $result = Process::path(base_path())->env($env)->timeout(30)->run(
            "{$binary} -i {$cssInput} -o {$cssOutput}"
        );

        if ($result->failed()) {
            $this->error('Tailwind compilation failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        $size = round(filesize($cssOutput) / 1024, 1);
        $this->info("CMS CSS compiled: {$size} KB → public/css/cms.css");

        CssBuildService::writeCurrentHash();

        return self::SUCCESS;
    }

    /**
     * Find the Tailwind CLI binary. Priority:
     * 1. Standalone binary in storage/cms/tailwindcss
     * 2. npx @tailwindcss/cli (requires Node.js)
     */
    private function resolveTailwindBinary(): ?string
    {
        // 1. Standalone binary
        $standalone = storage_path('cms/tailwindcss');

        if (file_exists($standalone) && is_executable($standalone)) {
            return $standalone;
        }

        // 2. npx fallback
        $env = ['PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin'];
        $npxCheck = Process::env($env)->run('which npx');

        if ($npxCheck->successful()) {
            return 'npx @tailwindcss/cli';
        }

        return null;
    }

    /**
     * Build the CSS input file containing Tailwind imports + the design system.
     */
    private function buildCssInput(): string
    {
        $parts = [
            '@import "tailwindcss";',
            '@plugin "@tailwindcss/typography";',
            '@source "content-source.html";',
        ];

        $headCss = Setting::get('layout_head_css', '');

        if ($headCss && is_string($headCss)) {
            $parts[] = '';
            $parts[] = '/* === Design System from CMS === */';
            $parts[] = $headCss;
        }

        return implode("\n", $parts);
    }

    /**
     * Collect all HTML from CMS content for Tailwind's class scanner.
     */
    private function collectAllHtml(): string
    {
        $parts = [];

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

        foreach (['layout_header', 'layout_footer', 'layout_body_class'] as $key) {
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

        foreach (glob(resource_path('views/layouts/*/*.blade.php')) ?: [] as $path) {
            $parts[] = file_get_contents($path);
        }

        foreach (glob(resource_path('views/templates/*.blade.php')) ?: [] as $path) {
            $parts[] = file_get_contents($path);
        }

        $html = implode("\n", $parts);

        preg_match_all('/class="([^"]*)"/', $html, $matches);

        $classes = collect($matches[1] ?? [])
            ->flatMap(fn (string $classList) => explode(' ', $classList))
            ->filter()
            ->unique()
            ->values();

        return $classes->map(fn (string $class) => '<div class="'.$class.'"></div>')->implode("\n");
    }
}
