<?php

namespace App\Services;

use App\Contracts\ShortcodeHandler;
use App\Models\Plugin;

class ShortcodeRenderer
{
    /** @var array<string, ShortcodeHandler>|null */
    private ?array $shortcodeMap = null;

    /**
     * Shortcode pattern:
     * - Self-closing: [tag attr="val" attr2="val2"]
     * - With content: [tag attr="val"]inner content[/tag]
     */
    private const PATTERN = '/\[([a-z0-9\-]+)((?:\s+[a-z0-9_\-]+="[^"]*")*)\s*\](?:(.*?)\[\/\1\])?/s';

    /**
     * Process content and replace shortcodes with rendered HTML.
     */
    public function render(string $content): string
    {
        $map = $this->getShortcodeMap();

        if (empty($map)) {
            return $content;
        }

        return (string) preg_replace_callback(self::PATTERN, function (array $matches) use ($map) {
            $tag = $matches[1];
            $attrString = $matches[2] ?? '';
            $innerContent = $matches[3] ?? null;

            if (! isset($map[$tag])) {
                return $matches[0]; // Leave unknown shortcodes as-is
            }

            $attributes = $this->parseAttributes($attrString);

            return $map[$tag]->render($attributes, $innerContent);
        }, $content);
    }

    /**
     * Check if content contains any known shortcodes.
     */
    public function hasShortcodes(string $content): bool
    {
        $map = $this->getShortcodeMap();

        if (empty($map)) {
            return false;
        }

        $tags = implode('|', array_keys($map));

        return (bool) preg_match("/\[({$tags})[\s\]]/", $content);
    }

    /**
     * Build the shortcode tag → handler map (cached per request).
     *
     * @return array<string, ShortcodeHandler>
     */
    private function getShortcodeMap(): array
    {
        if ($this->shortcodeMap !== null) {
            return $this->shortcodeMap;
        }

        $this->shortcodeMap = [];

        try {
            $plugins = Plugin::where('is_active', true)->get();
        } catch (\Throwable) {
            return $this->shortcodeMap;
        }

        foreach ($plugins as $plugin) {
            foreach ($plugin->getShortcodes() as $shortcode) {
                $tag = $shortcode['tag'] ?? null;
                $handlerClass = $shortcode['handler'] ?? null;

                if (! $tag || ! $handlerClass) {
                    continue;
                }

                $handlerPath = $plugin->basePath().'/src/'.$handlerClass.'.php';

                if (! file_exists($handlerPath)) {
                    continue;
                }

                require_once $handlerPath;

                if (class_exists($handlerClass) && is_a($handlerClass, ShortcodeHandler::class, true)) {
                    $this->shortcodeMap[$tag] = new $handlerClass;
                }
            }
        }

        return $this->shortcodeMap;
    }

    /**
     * Parse shortcode attribute string into key-value pairs.
     *
     * @return array<string, string>
     */
    private function parseAttributes(string $attrString): array
    {
        $attributes = [];

        preg_match_all('/([a-z0-9_\-]+)="([^"]*)"/', $attrString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return $attributes;
    }
}
