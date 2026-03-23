<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Description('Returns the URL of a media file in the requested size (thumb 150px, small 480px, medium 960px, large 1920px, original).')]
class GetMediaUrlTool extends Tool
{
    public function handle(Request $request): Response
    {
        $media = Media::find($request->get('media_id'));

        if (! $media) {
            return Response::text(json_encode([
                'success' => false,
                'message' => 'Media file not found.',
            ], JSON_UNESCAPED_UNICODE));
        }

        $size = $request->get('size', 'original');

        $url = $size === 'original'
            ? $media->getUrl()
            : ($media->hasGeneratedConversion($size) ? $media->getUrl($size) : $media->getUrl());

        $allUrls = ['original' => $media->getUrl()];

        foreach (['thumb', 'small', 'medium', 'large'] as $conversion) {
            if ($media->hasGeneratedConversion($conversion)) {
                $allUrls[$conversion] = $media->getUrl($conversion);
            }
        }

        return Response::text(json_encode([
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'requested_url' => $url,
            'all_urls' => $allUrls,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'media_id' => $schema->integer()->description('Media file ID')->required(),
            'size' => $schema->string()->description('Size: "original", "thumb", "small", "medium", "large"'),
        ];
    }
}
