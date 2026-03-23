<?php

namespace App\Services;

use App\Models\CmsSnapshot;
use App\Models\MenuItem;
use App\Models\Node;
use App\Models\NodeMeta;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class CmsSnapshotService
{
    /**
     * Create a full snapshot of the entire CMS state.
     */
    public function createSnapshot(string $label, ?string $description = null, string $createdBy = 'admin'): CmsSnapshot
    {
        $snapshot = [
            'nodes' => Node::with('meta')->get()->map(fn (Node $node) => [
                'id' => $node->id,
                'type' => $node->type->value,
                'slug' => $node->slug,
                'title' => $node->title,
                'status' => $node->status->value,
                'parent_id' => $node->parent_id,
                'sort_order' => $node->sort_order,
                'meta' => $node->meta->map(fn (NodeMeta $m) => [
                    'key' => $m->key,
                    'value' => $m->value,
                    'locale' => $m->locale,
                ])->all(),
            ])->all(),

            'menu_items' => MenuItem::all()->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'menu_id' => $item->menu_id,
                'label' => $item->label,
                'url' => $item->url,
                'node_id' => $item->node_id,
                'parent_id' => $item->parent_id,
                'sort_order' => $item->sort_order,
                'target' => $item->target,
            ])->all(),

            'settings' => Setting::all()->map(fn (Setting $s) => [
                'key' => $s->key,
                'value' => $s->getRawOriginal('value'),
                'group' => $s->group,
            ])->all(),

            'snapshot_version' => '1.0',
            'created_at' => now()->toIso8601String(),
        ];

        return CmsSnapshot::create([
            'label' => $label,
            'description' => $description,
            'snapshot' => $snapshot,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Restore the entire CMS to a snapshot state.
     */
    public function restoreSnapshot(CmsSnapshot $snapshot): void
    {
        $data = $snapshot->snapshot;

        DB::transaction(function () use ($data) {
            // Clear existing data
            MenuItem::query()->delete();
            NodeMeta::query()->delete();
            Node::query()->delete();
            Setting::query()->delete();

            // Restore nodes
            foreach ($data['nodes'] ?? [] as $nodeData) {
                $node = Node::query()->create([
                    'id' => $nodeData['id'],
                    'type' => $nodeData['type'],
                    'slug' => $nodeData['slug'],
                    'title' => $nodeData['title'],
                    'status' => $nodeData['status'],
                    'parent_id' => $nodeData['parent_id'] ?? null,
                    'sort_order' => $nodeData['sort_order'] ?? 0,
                ]);

                foreach ($nodeData['meta'] ?? [] as $meta) {
                    $node->meta()->create([
                        'key' => $meta['key'],
                        'value' => $meta['value'],
                        'locale' => $meta['locale'] ?? 'de',
                    ]);
                }
            }

            // Restore menu items
            foreach ($data['menu_items'] ?? [] as $item) {
                MenuItem::query()->create([
                    'id' => $item['id'],
                    'menu_id' => $item['menu_id'],
                    'label' => $item['label'],
                    'url' => $item['url'] ?? null,
                    'node_id' => $item['node_id'] ?? null,
                    'parent_id' => $item['parent_id'] ?? null,
                    'sort_order' => $item['sort_order'] ?? 0,
                    'target' => $item['target'] ?? '_self',
                ]);
            }

            // Restore settings
            foreach ($data['settings'] ?? [] as $setting) {
                Setting::query()->create([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'group' => $setting['group'] ?? 'general',
                ]);
            }
        });
    }

    /**
     * Get a summary of differences between current state and a snapshot.
     *
     * @return array<string, mixed>
     */
    public function diffSnapshot(CmsSnapshot $snapshot): array
    {
        $data = $snapshot->snapshot;
        $snapshotSlugs = collect($data['nodes'] ?? [])->pluck('slug')->all();
        $currentSlugs = Node::pluck('slug')->all();

        return [
            'pages_added' => array_values(array_diff($currentSlugs, $snapshotSlugs)),
            'pages_removed' => array_values(array_diff($snapshotSlugs, $currentSlugs)),
            'pages_in_both' => array_values(array_intersect($currentSlugs, $snapshotSlugs)),
            'settings_count' => count($data['settings'] ?? []),
            'current_settings_count' => Setting::count(),
        ];
    }
}
