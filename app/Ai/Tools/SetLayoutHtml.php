<?php

namespace App\Ai\Tools;

use App\Models\Setting;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SetLayoutHtml implements Tool
{
    public function description(): Stringable|string
    {
        return <<<'DESC'
        Sets the HTML for header, footer and body styling of the website. HTML is rendered directly and replaces the default templates.
        Use {{nav:header-menu}} and {{nav:footer-menu}} as placeholders in the HTML — these are automatically replaced with the current menu links.
        DESC;
    }

    public function handle(Request $request): Stringable|string
    {
        $sanitizer = app(HtmlSanitizer::class);
        $updated = [];

        if (isset($request['header_html'])) {
            Setting::set('layout_header', $sanitizer->sanitize($request['header_html']), 'layout');
            $updated[] = 'header';
        }

        if (isset($request['footer_html'])) {
            Setting::set('layout_footer', $sanitizer->sanitize($request['footer_html']), 'layout');
            $updated[] = 'footer';
        }

        if (isset($request['body_class'])) {
            Setting::set('layout_body_class', $request['body_class'], 'layout');
            $updated[] = 'body_class';
        }

        if (isset($request['body_style'])) {
            Setting::set('layout_body_style', $request['body_style'], 'layout');
            $updated[] = 'body_style';
        }

        if (isset($request['head_css'])) {
            Setting::set('layout_head_css', $request['head_css'], 'layout');
            $updated[] = 'head_css';
        }

        if (isset($request['head_js'])) {
            Setting::set('layout_head_js', $request['head_js'], 'layout');
            $updated[] = 'head_js';
        }

        return json_encode([
            'success' => true,
            'message' => 'Layout updated: '.implode(', ', $updated),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'header_html' => $schema->string()->description(
                'Complete header HTML with Tailwind CSS. Use {{nav:header-menu}} as placeholder for navigation links. '
                .'Example: <header class="bg-slate-900"><nav class="mx-auto max-w-7xl px-6 flex h-16 items-center justify-between">'
                .'<a href="/" class="text-xl font-bold text-white">Logo</a>'
                .'<div class="flex gap-6 text-sm text-gray-300">{{nav:header-menu}}</div></nav></header>'
            ),
            'footer_html' => $schema->string()->description(
                'Complete footer HTML with Tailwind CSS. Use {{nav:footer-menu}} for footer navigation. '
                .'Can contain copyright, columns with links, social media icons, etc.'
            ),
            'body_class' => $schema->string()->description(
                'Tailwind CSS classes for <body>. Default: "min-h-screen flex flex-col bg-white text-gray-900 antialiased". '
                .'Change to e.g. "min-h-screen flex flex-col bg-gray-950 text-white antialiased" for dark background.'
            ),
            'body_style' => $schema->string()->description('Optional inline CSS styles for <body>, e.g. "font-family: Lato, sans-serif"'),
            'head_css' => $schema->string()->description('Custom CSS injected in <head>. For global styles, animations, design system classes, etc.'),
            'head_js' => $schema->string()->description(
                'Global JavaScript injected before </body>. For interactive features like: '
                .'header shrink on scroll, hamburger menu toggle, scroll spy, scroll animations (IntersectionObserver), '
                .'smooth scroll, parallax effects, countdown timers, etc. '
                .'IMPORTANT: No <script> tag — pure JavaScript only. '
                .'Use document.addEventListener("DOMContentLoaded", ...) as wrapper.'
            ),
        ];
    }
}
