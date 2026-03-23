<?php

namespace App\Mcp\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Deletes a page from the CMS.')]
class DeletePageTool extends Tool
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $result = $service->deletePage($request->get('slug'));

        return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the page to delete')->required(),
        ];
    }
}
