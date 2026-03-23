<?php

namespace App\Mcp\Tools;

use App\Models\MediaContainer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Description('Searches the media library. Returns file names and URLs in all sizes (thumb, small, medium, large).')]
class ListMediaTool extends Tool
{
    public function handle(Request $request): Response
    {
        $query = Media::query()
            ->where('model_type', MediaContainer::class)
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($mimeFilter = $request->get('mime_type')) {
            $query->where('mime_type', 'like', "{$mimeFilter}%");
        }

        $media = $query->limit((int) ($request->get('limit') ?? 20))->get();

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

        return Response::text(json_encode([
            'count' => count($results),
            'media' => $results,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Search term for file names'),
            'mime_type' => $schema->string()->description('MIME type filter, e.g. "image/" for all images'),
            'limit' => $schema->integer()->description('Max. results (default: 20)'),
        ];
    }
}
