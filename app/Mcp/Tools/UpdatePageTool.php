<?php

namespace App\Mcp\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Updates an existing page. Fields not provided remain unchanged.')]
class UpdatePageTool extends Tool
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $fields = array_filter([
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'status' => $request->get('status'),
            'meta_description' => $request->get('meta_description'),
            'template' => $request->get('template'),
            'slug' => $request->get('new_slug'),
        ], fn ($v) => $v !== null);

        $result = $service->updatePage($request->get('slug'), $fields);

        return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the page to update')->required(),
            'title' => $schema->string()->description('New title'),
            'content' => $schema->string()->description('New HTML content'),
            'status' => $schema->string()->description('"published" or "draft"'),
            'meta_description' => $schema->string()->description('New meta description'),
            'template' => $schema->string()->description('New template'),
            'new_slug' => $schema->string()->description('New slug (for renaming)'),
        ];
    }
}
