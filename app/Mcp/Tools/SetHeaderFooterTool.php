<?php

namespace App\Mcp\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Configures the website header and footer (logo, CTA, footer columns, copyright).')]
class SetHeaderFooterTool extends Tool
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $result = $service->setHeaderFooter(
            $request->get('header_config', []),
            $request->get('footer_config', []),
        );

        return Response::text(json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'header_config' => $schema->object([
                'logo_text' => $schema->string()->description('Text logo in the header'),
                'cta_text' => $schema->string()->description('CTA button text'),
                'cta_url' => $schema->string()->description('CTA button URL'),
            ])->description('Header configuration'),
            'footer_config' => $schema->object([
                'copyright' => $schema->string()->description('Copyright text'),
            ])->description('Footer configuration'),
        ];
    }
}
