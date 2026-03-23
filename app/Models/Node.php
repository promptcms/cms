<?php

namespace App\Models;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use Database\Factories\NodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Node extends Model implements HasMedia
{
    /** @use HasFactory<NodeFactory> */
    use HasFactory, HasUlids, InteractsWithMedia;

    protected $fillable = [
        'type',
        'slug',
        'title',
        'status',
        'parent_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NodeType::class,
            'status' => NodeStatus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<NodeMeta, $this>
     */
    public function meta(): HasMany
    {
        return $this->hasMany(NodeMeta::class);
    }

    /**
     * @return HasMany<Node, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'parent_id');
    }

    /**
     * @return HasMany<NodeRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(NodeRevision::class)->latest('created_at');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'menu_id')->orderBy('sort_order');
    }

    /**
     * @param  Builder<Node>  $query
     * @return Builder<Node>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', NodeStatus::Published);
    }

    /**
     * @param  Builder<Node>  $query
     * @return Builder<Node>
     */
    public function scopeOfType(Builder $query, NodeType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function getMeta(string $key, string $locale = 'de', mixed $default = null): mixed
    {
        $meta = $this->meta->first(fn (NodeMeta $m) => $m->key === $key && $m->locale === $locale);

        if (! $meta) {
            return $default;
        }

        $decoded = json_decode($meta->value, true);

        return $decoded === null && $meta->value !== 'null' ? $meta->value : $decoded;
    }

    public function setMeta(string $key, mixed $value, string $locale = 'de'): NodeMeta
    {
        $encodedValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return $this->meta()->updateOrCreate(
            ['key' => $key, 'locale' => $locale],
            ['value' => $encodedValue],
        );
    }

    /**
     * Create a revision snapshot of the current state.
     */
    public function createRevision(?string $prompt = null): NodeRevision
    {
        $snapshot = [
            'node' => $this->toArray(),
            'meta' => $this->meta->toArray(),
        ];

        // Include menu items for menu nodes
        if ($this->type === NodeType::Menu) {
            $snapshot['menu_items'] = $this->menuItems()->with('children')->get()->toArray();
        }

        return $this->revisions()->create([
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'prompt' => $prompt,
        ]);
    }

    /**
     * Restore this node to a previous revision state.
     */
    public function restoreRevision(NodeRevision $revision): void
    {
        $snapshot = $revision->snapshot;

        if (! is_array($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }

        // Restore node attributes
        $nodeData = $snapshot['node'] ?? [];
        $restorableFields = ['title', 'slug', 'status', 'parent_id', 'sort_order'];

        foreach ($restorableFields as $field) {
            if (array_key_exists($field, $nodeData)) {
                $this->{$field} = $nodeData[$field];
            }
        }

        $this->save();

        // Restore meta
        if (isset($snapshot['meta']) && is_array($snapshot['meta'])) {
            $this->meta()->delete();

            foreach ($snapshot['meta'] as $meta) {
                $this->meta()->create([
                    'key' => $meta['key'],
                    'value' => $meta['value'],
                    'locale' => $meta['locale'] ?? 'de',
                ]);
            }

            $this->load('meta');
        }

        // Restore menu items for menu nodes
        if ($this->type === NodeType::Menu && isset($snapshot['menu_items'])) {
            $this->menuItems()->delete();
            $this->restoreMenuItems($snapshot['menu_items']);
        }

        // Create a new revision to record the rollback
        $this->createRevision('Rollback to revision '.$revision->id);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function restoreMenuItems(array $items, ?string $parentId = null): void
    {
        foreach ($items as $item) {
            $menuItem = MenuItem::query()->create([
                'menu_id' => $this->id,
                'label' => $item['label'],
                'url' => $item['url'] ?? null,
                'node_id' => $item['node_id'] ?? null,
                'parent_id' => $parentId,
                'sort_order' => $item['sort_order'] ?? 0,
                'target' => $item['target'] ?? '_self',
            ]);

            if (! empty($item['children'])) {
                $this->restoreMenuItems($item['children'], $menuItem->id);
            }
        }
    }
}
