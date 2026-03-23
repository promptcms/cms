<?php

namespace App\Ai\Tools;

use App\Models\Node;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RollbackPage implements Tool
{
    public function description(): Stringable|string
    {
        return 'Reverts a page to a previous version. Shows available revisions when no revision_id is provided.';
    }

    public function handle(Request $request): Stringable|string
    {
        $slug = $request['slug'];
        $revisionId = $request['revision_id'] ?? null;

        $node = Node::query()->where('slug', $slug)->with('revisions')->first();

        if (! $node) {
            return json_encode(['success' => false, 'message' => "Page '{$slug}' not found."], JSON_UNESCAPED_UNICODE);
        }

        // If no revision_id, list available revisions
        if (! $revisionId) {
            $revisions = $node->revisions()->limit(10)->get()->map(fn ($r) => [
                'id' => $r->id,
                'prompt' => $r->prompt,
                'created_at' => $r->created_at->format('d.m.Y H:i'),
            ])->all();

            return json_encode([
                'success' => true,
                'message' => 'Available revisions for '.$slug,
                'revisions' => $revisions,
            ], JSON_UNESCAPED_UNICODE);
        }

        $revision = $node->revisions()->find($revisionId);

        if (! $revision) {
            return json_encode(['success' => false, 'message' => "Revision '{$revisionId}' not found."], JSON_UNESCAPED_UNICODE);
        }

        $node->restoreRevision($revision);

        return json_encode([
            'success' => true,
            'message' => "Page '{$slug}' has been rolled back to revision from {$revision->created_at->format('Y-m-d H:i')}.",
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('Slug of the page to roll back')->required(),
            'revision_id' => $schema->string()->description('Revision ID. If empty, available revisions will be listed.'),
        ];
    }
}
