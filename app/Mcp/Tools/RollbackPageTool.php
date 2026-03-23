<?php

namespace App\Mcp\Tools;

use App\Models\Node;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Reverts a page to a previous revision. Without revision_id, available revisions are listed.')]
class RollbackPageTool extends Tool
{
    public function handle(Request $request): Response
    {
        $slug = $request->get('slug');
        $revisionId = $request->get('revision_id');

        $node = Node::query()->where('slug', $slug)->with('revisions')->first();

        if (! $node) {
            return Response::text(json_encode([
                'success' => false,
                'message' => "Page '{$slug}' not found.",
            ], JSON_UNESCAPED_UNICODE));
        }

        if (! $revisionId) {
            $revisions = $node->revisions()->limit(10)->get()->map(fn ($r) => [
                'id' => $r->id,
                'prompt' => $r->prompt,
                'created_at' => $r->created_at->format('d.m.Y H:i'),
            ])->all();

            return Response::text(json_encode([
                'success' => true,
                'message' => "Available revisions for {$slug}",
                'revisions' => $revisions,
            ], JSON_UNESCAPED_UNICODE));
        }

        $revision = $node->revisions()->find($revisionId);

        if (! $revision) {
            return Response::text(json_encode([
                'success' => false,
                'message' => 'Revision not found.',
            ], JSON_UNESCAPED_UNICODE));
        }

        $node->restoreRevision($revision);

        return Response::text(json_encode([
            'success' => true,
            'message' => "Page '{$slug}' rolled back to revision from {$revision->created_at->format('Y-m-d H:i')}.",
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Page slug')->required(),
            'revision_id' => $schema->string()->description('Revision ID (empty = list revisions)'),
        ];
    }
}
