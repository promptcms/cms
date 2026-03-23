<?php

namespace App\Mcp\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a new page in the CMS with HTML content and Tailwind CSS classes.')]
class CreatePageTool extends Tool
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $result = $service->createPage(
            slug: $request->get('slug'),
            title: $request->get('title'),
            content: $request->get('content'),
            parentSlug: $request->get('parent_slug'),
            metaDescription: $request->get('meta_description'),
            template: $request->get('template', 'default'),
        );

        return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('URL slug, e.g. "about", "contact"')->required(),
            'title' => $schema->string()->description('Page title')->required(),
            'content' => $schema->string()->description('HTML content of the page (with Tailwind CSS)')->required(),
            'parent_slug' => $schema->string()->description('Parent page slug for sub-pages'),
            'meta_description' => $schema->string()->description('SEO meta description'),
            'template' => $schema->string()->description('Blade template: "default" or "home"'),
        ];
    }
}
