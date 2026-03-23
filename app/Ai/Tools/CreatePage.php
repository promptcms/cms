<?php

namespace App\Ai\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreatePage implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Creates a new page in the CMS. Use this for home page, sub-pages, legal pages, etc. Content should be complete HTML.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $service = app(CmsToolService::class);

        $result = $service->createPage(
            slug: $request['slug'],
            title: $request['title'],
            content: $request['content'],
            parentSlug: $request['parent_slug'] ?? null,
            metaDescription: $request['meta_description'] ?? null,
            template: $request['template'] ?? 'default',
        );

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('URL slug, e.g. "home", "about", "legal"')->required(),
            'title' => $schema->string()->description('Page title')->required(),
            'content' => $schema->string()->description('HTML content of the page')->required(),
            'parent_slug' => $schema->string()->description('Parent page slug for sub-pages'),
            'meta_description' => $schema->string()->description('SEO meta description'),
            'template' => $schema->string()->description('Blade template name, default: "default"'),
        ];
    }
}
