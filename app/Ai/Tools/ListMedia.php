<?php

namespace App\Ai\Tools;

use App\Models\MediaContainer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stringable;

class ListMedia implements Tool
{
    public function description(): Stringable|string
    {
        return 'Searches the media library for files. Returns file names, URLs and available sizes. Use this to find suitable images for pages.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Media::query()
            ->where('model_type', MediaContainer::class)
            ->latest();

        $search = $request['search'] ?? null;

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $mimeFilter = $request['mime_type'] ?? null;

        if ($mimeFilter) {
            $query->where('mime_type', 'like', "{$mimeFilter}%");
        }

        $media = $query->limit((int) ($request['limit'] ?? 20))->get();

        $results = $media->map(function (Media $m) {
            $urls = ['original' => $m->getUrl()];

            foreach (['thumb', 'small', 'medium', 'large'] as $conversion) {
                if ($m->hasGeneratedConversion($conversion)) {
                    $urls[$conversion] = $m->getUrl($conversion);
                }
            }

            return [
                'id' => $m->id,
                'name' => $m->name,
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'size' => $m->human_readable_size,
                'urls' => $urls,
            ];
        })->all();

        return json_encode([
            'success' => true,
            'count' => count($results),
            'media' => $results,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search term for file names (optional)'),
            'mime_type' => $schema->string()->description('MIME type filter, e.g. "image/" for all images, "image/svg" for SVGs (optional)'),
            'limit' => $schema->integer()->description('Maximum number of results (default: 20)'),
        ];
    }
}
