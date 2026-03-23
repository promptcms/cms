<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stringable;

class GetMediaUrl implements Tool
{
    public function description(): Stringable|string
    {
        return 'Returns the URL of a media file in the requested size. Available sizes: original, thumb (150px), small (480px), medium (960px), large (1920px).';
    }

    public function handle(Request $request): Stringable|string
    {
        $mediaId = $request['media_id'];
        $size = $request['size'] ?? 'original';

        $media = Media::find($mediaId);

        if (! $media) {
            return json_encode([
                'success' => false,
                'message' => "Media file with ID '{$mediaId}' not found.",
            ], JSON_UNESCAPED_UNICODE);
        }

        $url = $size === 'original'
            ? $media->getUrl()
            : ($media->hasGeneratedConversion($size) ? $media->getUrl($size) : $media->getUrl());

        $allUrls = ['original' => $media->getUrl()];

        foreach (['thumb', 'small', 'medium', 'large'] as $conversion) {
            if ($media->hasGeneratedConversion($conversion)) {
                $allUrls[$conversion] = $media->getUrl($conversion);
            }
        }

        return json_encode([
            'success' => true,
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'requested_url' => $url,
            'all_urls' => $allUrls,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'media_id' => $schema->integer()->description('Media file ID')->required(),
            'size' => $schema->string()->description('Requested size: "original", "thumb" (150px), "small" (480px), "medium" (960px), "large" (1920px). Default: original'),
        ];
    }
}
