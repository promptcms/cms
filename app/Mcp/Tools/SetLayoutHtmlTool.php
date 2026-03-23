<?php

namespace App\Mcp\Tools;

use App\Models\Setting;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sets complete HTML for header, footer, body classes and custom CSS. Use {{nav:header-menu}} as placeholder for navigation links.')]
class SetLayoutHtmlTool extends Tool
{
    public function handle(Request $request, HtmlSanitizer $sanitizer): Response
    {
        $updated = [];

        if ($headerHtml = $request->get('header_html')) {
            Setting::set('layout_header', $sanitizer->sanitize($headerHtml), 'layout');
            $updated[] = 'header';
        }

        if ($footerHtml = $request->get('footer_html')) {
            Setting::set('layout_footer', $sanitizer->sanitize($footerHtml), 'layout');
            $updated[] = 'footer';
        }

        if ($bodyClass = $request->get('body_class')) {
            Setting::set('layout_body_class', $bodyClass, 'layout');
            $updated[] = 'body_class';
        }

        if ($bodyStyle = $request->get('body_style')) {
            Setting::set('layout_body_style', $bodyStyle, 'layout');
            $updated[] = 'body_style';
        }

        if ($headCss = $request->get('head_css')) {
            Setting::set('layout_head_css', $headCss, 'layout');
            $updated[] = 'head_css';
        }

        if ($headJs = $request->get('head_js')) {
            Setting::set('layout_head_js', $headJs, 'layout');
            $updated[] = 'head_js';
        }

        return Response::text(json_encode([
            'success' => true,
            'message' => 'Layout updated: '.implode(', ', $updated),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'header_html' => $schema->string()->description('Complete header HTML. Use {{nav:header-menu}} for navigation.'),
            'footer_html' => $schema->string()->description('Complete footer HTML. Use {{nav:footer-menu}} for footer links.'),
            'body_class' => $schema->string()->description('Tailwind CSS classes for <body>'),
            'body_style' => $schema->string()->description('Inline CSS styles for <body>'),
            'head_css' => $schema->string()->description('Custom CSS injected in <head>'),
            'head_js' => $schema->string()->description('Global JavaScript before </body>. Pure JS without <script> tags.'),
        ];
    }
}
