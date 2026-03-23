<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizer
{
    private SymfonySanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            // Allow all safe HTML elements
            ->allowSafeElements()

            // Allow additional structural elements
            ->allowElement('section', ['class', 'id', 'style'])
            ->allowElement('article', ['class', 'id', 'style'])
            ->allowElement('aside', ['class', 'id', 'style'])
            ->allowElement('nav', ['class', 'id', 'style'])
            ->allowElement('header', ['class', 'id', 'style'])
            ->allowElement('footer', ['class', 'id', 'style'])
            ->allowElement('main', ['class', 'id', 'style'])
            ->allowElement('figure', ['class', 'id', 'style'])
            ->allowElement('figcaption', ['class', 'id', 'style'])
            ->allowElement('details', ['class', 'id', 'style', 'open'])
            ->allowElement('summary', ['class', 'id', 'style'])

            // Allow media elements with safe attributes
            ->allowElement('img', ['src', 'alt', 'title', 'class', 'width', 'height', 'loading', 'style'])
            ->allowElement('picture', ['class'])
            ->allowElement('source', ['srcset', 'media', 'type'])
            ->allowElement('video', ['src', 'poster', 'controls', 'class', 'width', 'height', 'autoplay', 'muted', 'loop', 'playsinline'])
            ->allowElement('audio', ['src', 'controls', 'class'])
            ->allowElement('iframe', ['src', 'class', 'width', 'height', 'title', 'loading', 'allow', 'allowfullscreen', 'frameborder', 'style'])

            // Allow style attribute globally for Tailwind inline styles
            ->allowAttribute('class', '*')
            ->allowAttribute('id', '*')
            ->allowAttribute('style', '*')
            ->allowAttribute('role', '*')
            ->allowAttribute('aria-label', '*')
            ->allowAttribute('aria-hidden', '*')
            ->allowAttribute('data-*', '*')
            ->allowAttribute('target', ['a'])
            ->allowAttribute('rel', ['a'])

            // SVG support for icons
            ->allowElement('svg', ['class', 'viewBox', 'fill', 'xmlns', 'width', 'height', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'aria-hidden'])
            ->allowElement('path', ['d', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule'])
            ->allowElement('circle', ['cx', 'cy', 'r', 'fill', 'stroke', 'stroke-width'])
            ->allowElement('rect', ['x', 'y', 'width', 'height', 'rx', 'ry', 'fill', 'stroke'])
            ->allowElement('line', ['x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width'])
            ->allowElement('polyline', ['points', 'fill', 'stroke', 'stroke-width'])
            ->allowElement('polygon', ['points', 'fill', 'stroke'])
            ->allowElement('g', ['class', 'fill', 'stroke', 'transform'])
            ->allowElement('defs', [])
            ->allowElement('clipPath', ['id'])
            ->allowElement('use', ['href', 'xlink:href'])

            // Block dangerous elements
            ->blockElement('script')
            ->blockElement('noscript')
            ->blockElement('object')
            ->blockElement('embed')
            ->blockElement('applet')
            ->blockElement('form')
            ->blockElement('input')
            ->blockElement('textarea')
            ->blockElement('button')
            ->blockElement('select')

            // Allow safe link protocols
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['http', 'https', 'data']);

        $this->sanitizer = new SymfonySanitizer($config);
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}
