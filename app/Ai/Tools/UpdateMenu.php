<?php

namespace App\Ai\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateMenu implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Updates an existing navigation menu with new items.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $service = app(CmsToolService::class);

        $result = $service->updateMenu($request['menu_name'], $request['items']);

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
            'menu_name' => $schema->string()->description('Name of the menu to update')->required(),
            'items' => $schema->array()->items(
                $schema->object([
                    'label' => $schema->string()->description('Displayed text of the menu item')->required(),
                    'slug_or_url' => $schema->string()->description('Slug of a CMS page or external URL')->required(),
                    'target' => $schema->string()->description('Link target: "_self" or "_blank"'),
                ])
            )->description('New menu items')->required(),
        ];
    }
}
