<?php

namespace App\Ai\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdatePage implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Updates an existing page. Can change title, content, status, template and meta description.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $service = app(CmsToolService::class);

        $fields = array_filter([
            'title' => $request['title'] ?? null,
            'content' => $request['content'] ?? null,
            'status' => $request['status'] ?? null,
            'meta_description' => $request['meta_description'] ?? null,
            'template' => $request['template'] ?? null,
        ], fn ($v) => $v !== null);

        $result = $service->updatePage($request['slug'], $fields);

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
            'slug' => $schema->string()->description('Slug of the page to update')->required(),
            'title' => $schema->string()->description('New page title'),
            'content' => $schema->string()->description('New HTML content'),
            'status' => $schema->string()->description('Status: "draft" or "published"'),
            'meta_description' => $schema->string()->description('New SEO meta description'),
            'template' => $schema->string()->description('New template name'),
        ];
    }
}
