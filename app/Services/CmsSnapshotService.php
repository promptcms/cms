<?php

namespace App\Services;

use App\Models\CmsSnapshot;
use App\Models\MenuItem;
use App\Models\Node;
use App\Models\NodeMeta;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CmsSnapshotService
{
    /**
     * Current snapshot data format. Bump this whenever the payload shape changes
     * and add a corresponding migration in SnapshotMigrator::MIGRATIONS.
     */
    public const FORMAT_VERSION = 2;

    public function __construct(private readonly SnapshotMigrator $migrator) {}

    /**
     * Create a full snapshot of the entire CMS state.
     */
    public function createSnapshot(string $label, ?string $description = null, string $createdBy = 'admin'): CmsSnapshot
    {
        return CmsSnapshot::create([
            'label' => $label,
            'description' => $description,
            'snapshot' => $this->buildPayload(),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Build a snapshot payload from the current CMS state.
     *
     * @return array<string, mixed>
     */
    public function buildPayload(): array
    {
        return [
            'format_version' => self::FORMAT_VERSION,
            'app_version' => (string) config('app.version', 'dev'),
            'exported_at' => now()->toIso8601String(),

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

            'settings' => Setting::query()
                ->where('group', '!=', 'ai')
                ->get()
                ->map(fn (Setting $s) => [
                    'key' => $s->key,
                    'value' => $s->getRawOriginal('value'),
                    'group' => $s->group,
                ])->all(),
        ];
    }

    /**
     * Restore the entire CMS to a snapshot state.
     */
    public function restoreSnapshot(CmsSnapshot $snapshot): void
    {
        $data = $this->migrator->migrate(
            $snapshot->snapshot ?? [],
            self::FORMAT_VERSION,
        );

        DB::transaction(function () use ($data) {
            // Clear existing content data (preserve system settings like API keys)
            MenuItem::query()->delete();
            NodeMeta::query()->delete();
            Node::query()->delete();
            Setting::query()->where('group', '!=', 'ai')->delete();

            // Restore nodes (force ID to preserve snapshot references)
            foreach ($data['nodes'] ?? [] as $nodeData) {
                $node = new Node;
                $node->id = $nodeData['id'];
                $node->type = $nodeData['type'];
                $node->slug = $nodeData['slug'];
                $node->title = $nodeData['title'];
                $node->status = $nodeData['status'];
                $node->parent_id = $nodeData['parent_id'] ?? null;
                $node->sort_order = $nodeData['sort_order'] ?? 0;
                $node->save();

                foreach ($nodeData['meta'] ?? [] as $meta) {
                    $node->meta()->create([
                        'key' => $meta['key'],
                        'value' => $meta['value'],
                        'locale' => $meta['locale'] ?? 'de',
                    ]);
                }
            }

            // Restore menu items (force ID to preserve parent/child references)
            foreach ($data['menu_items'] ?? [] as $item) {
                $menuItem = new MenuItem;
                $menuItem->id = $item['id'];
                $menuItem->menu_id = $item['menu_id'];
                $menuItem->label = $item['label'];
                $menuItem->url = $item['url'] ?? null;
                $menuItem->node_id = $item['node_id'] ?? null;
                $menuItem->parent_id = $item['parent_id'] ?? null;
                $menuItem->sort_order = $item['sort_order'] ?? 0;
                $menuItem->target = $item['target'] ?? '_self';
                $menuItem->save();
            }

            // Restore settings (skip ai group — those are installation-specific)
            foreach ($data['settings'] ?? [] as $setting) {
                $group = $setting['group'] ?? 'general';

                if ($group === 'ai') {
                    continue;
                }

                Setting::query()->updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value'], 'group' => $group],
                );
            }
        });
    }

    /**
     * Export a snapshot as a JSON string ready for file download.
     */
    public function exportSnapshot(CmsSnapshot $snapshot): string
    {
        $payload = $this->migrator->migrate(
            $snapshot->snapshot ?? [],
            self::FORMAT_VERSION,
        );

        $envelope = [
            'kind' => 'promptcms-snapshot',
            'format_version' => self::FORMAT_VERSION,
            'app_version' => (string) config('app.version', 'dev'),
            'exported_at' => now()->toIso8601String(),
            'label' => $snapshot->label,
            'description' => $snapshot->description,
            'data' => $payload,
        ];

        return (string) json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import a snapshot file as a new CmsSnapshot record (does NOT restore — that is a separate step).
     */
    public function importSnapshot(string $json, string $createdBy = 'admin'): CmsSnapshot
    {
        $envelope = json_decode($json, true);

        if (! is_array($envelope)) {
            throw new InvalidArgumentException('Snapshot file is not valid JSON.');
        }

        if (($envelope['kind'] ?? null) !== 'promptcms-snapshot') {
            throw new InvalidArgumentException('File is not a PromptCMS snapshot export.');
        }

        $data = $envelope['data'] ?? null;

        if (! is_array($data)) {
            throw new InvalidArgumentException('Snapshot export is missing the data payload.');
        }

        // Migrate to current format (also throws if newer than supported)
        $data = $this->migrator->migrate($data, self::FORMAT_VERSION);

        $label = (string) ($envelope['label'] ?? 'Imported snapshot');
        $description = $envelope['description'] ?? null;
        $exportedAt = $envelope['exported_at'] ?? null;

        $importNote = 'Imported'.($exportedAt ? ' (originally exported '.$exportedAt.')' : '');
        $description = $description ? $description.' — '.$importNote : $importNote;

        return CmsSnapshot::create([
            'label' => '[Import] '.$label,
            'description' => $description,
            'snapshot' => $data,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Get a summary of differences between current state and a snapshot.
     *
     * @return array<string, mixed>
     */
    public function diffSnapshot(CmsSnapshot $snapshot): array
    {
        $data = $this->migrator->migrate(
            $snapshot->snapshot ?? [],
            self::FORMAT_VERSION,
        );

        $snapshotSlugs = collect($data['nodes'] ?? [])->pluck('slug')->all();
        $currentSlugs = Node::pluck('slug')->all();

        return [
            'pages_added' => array_values(array_diff($currentSlugs, $snapshotSlugs)),
            'pages_removed' => array_values(array_diff($snapshotSlugs, $currentSlugs)),
            'pages_in_both' => array_values(array_intersect($currentSlugs, $snapshotSlugs)),
            'settings_count' => \count($data['settings'] ?? []),
            'current_settings_count' => Setting::count(),
        ];
    }
}
