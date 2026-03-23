<?php

namespace App\Mcp\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sets a global CMS setting (e.g. site_name, contact_email).')]
class SetSettingTool extends Tool
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $result = $service->setSetting(
            $request->get('key'),
            $request->get('value'),
            $request->get('group', 'general'),
        );

        return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->description('Setting key, e.g. "site_name"')->required(),
            'value' => $schema->string()->description('Setting value')->required(),
            'group' => $schema->string()->description('Group: "general", "layout", "ai"'),
        ];
    }
}
