<?php

namespace App\Ai\Tools;

use App\Services\CmsToolService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SetHeaderFooter implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Configures the website header and footer. Header can contain logo, navigation and CTA. Footer can contain columns with links, copyright and social media links.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $service = app(CmsToolService::class);

        $result = $service->setHeaderFooter(
            $request['header_config'] ?? [],
            $request['footer_config'] ?? [],
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
            'header_config' => $schema->object([
                'logo_text' => $schema->string()->description('Text for the logo in the header'),
                'cta_text' => $schema->string()->description('Text for the Call-to-Action button'),
                'cta_url' => $schema->string()->description('URL for the CTA button'),
                'bg_class' => $schema->string()->description('Tailwind CSS classes for header background, e.g. "bg-slate-900 border-b border-slate-800"'),
                'text_class' => $schema->string()->description('Tailwind CSS classes for logo text, e.g. "text-white"'),
                'link_class' => $schema->string()->description('Tailwind CSS classes for navigation links, e.g. "text-gray-300 hover:text-white"'),
                'cta_bg_class' => $schema->string()->description('Tailwind CSS classes for CTA button, e.g. "bg-rose-500 hover:bg-rose-400 text-white"'),
                'style' => $schema->string()->description('Optional inline CSS styles for the header'),
            ])->description('Header configuration'),
            'footer_config' => $schema->object([
                'copyright' => $schema->string()->description('Copyright text'),
                'bg_class' => $schema->string()->description('Tailwind CSS classes for footer background, e.g. "bg-slate-900 text-gray-300"'),
                'style' => $schema->string()->description('Optional inline CSS styles for the footer'),
                'columns' => $schema->array()->items(
                    $schema->object([
                        'title' => $schema->string()->description('Column heading')->required(),
                        'links' => $schema->array()->items(
                            $schema->object([
                                'label' => $schema->string()->description('Link text')->required(),
                                'url' => $schema->string()->description('Link URL')->required(),
                            ])
                        )->description('Links in this column'),
                    ])
                )->description('Footer columns with links'),
            ])->description('Footer configuration'),
        ];
    }
}
