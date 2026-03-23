<?php

namespace App\Mcp\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates or replaces a navigation menu with items.')]
class CreateMenuTool extends Tool
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $result = $service->createMenu($request->get('name'), $request->get('items'));

        return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Menu name, e.g. "header-menu"')->required(),
            'items' => $schema->array()->items(
                $schema->object([
                    'label' => $schema->string()->description('Displayed text')->required(),
                    'slug_or_url' => $schema->string()->description('CMS slug or external URL')->required(),
                    'target' => $schema->string()->description('"_self" or "_blank"'),
                ])
            )->description('Menu items')->required(),
        ];
    }
}
