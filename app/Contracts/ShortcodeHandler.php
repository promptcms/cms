<?php

namespace App\Contracts;

interface ShortcodeHandler
{
    /**
     * Render the shortcode to HTML.
     *
     * @param  array<string, string>  $attributes
     */
    public function render(array $attributes, ?string $innerContent = null): string;
}
