<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'menu_id',
        'label',
        'url',
        'node_id',
        'parent_id',
        'sort_order',
        'target',
    ];

    /**
     * @return BelongsTo<Node, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'menu_id');
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function linkedNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Resolve the URL for this menu item.
     */
    public function resolveUrl(): string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->node_id) {
            $node = $this->linkedNode;

            if ($node && $node->slug) {
                return $node->slug === 'home' ? '/' : '/'.$node->slug;
            }
        }

        return '#';
    }
}
