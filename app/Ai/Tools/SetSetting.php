<?php

namespace App\Ai\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SetSetting implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Sets a global CMS setting (e.g. site_name, site_tagline, contact_email, logo_url).';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $service = app(CmsToolService::class);

        $result = $service->setSetting(
            $request['key'],
            $request['value'],
            $request['group'] ?? 'general',
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
            'key' => $schema->string()->description('Setting key, e.g. "site_name"')->required(),
            'value' => $schema->string()->description('Setting value (can also be JSON)')->required(),
            'group' => $schema->string()->description('Group, default: "general"'),
        ];
    }
}
