<?php

namespace App\Services;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\MenuItem;
use App\Models\Node;
use App\Models\Plugin;
use App\Models\Setting;

class CmsToolService
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createPage(
        string $slug,
        string $title,
        string $content,
        ?string $parentSlug = null,
        ?string $metaDescription = null,
        string $template = 'default',
    ): array {
        $parentId = null;

        if ($parentSlug) {
            $parent = Node::query()->where('slug', $parentSlug)->first();
            $parentId = $parent?->id;
        }

        $node = Node::query()->create([
            'type' => NodeType::Page,
            'slug' => $slug,
            'title' => $title,
            'status' => NodeStatus::Published,
            'parent_id' => $parentId,
        ]);

        $node->setMeta('content', $this->sanitizer->sanitize($content));
        $node->setMeta('template', $template);

        if ($metaDescription) {
            $node->setMeta('meta_description', $metaDescription);
        }

        $node->load('meta');
        $node->createRevision('Page created: '.$title);

        return [
            'success' => true,
            'message' => "Seite '{$title}' (/{$slug}) wurde erstellt.",
            'slug' => $slug,
            'id' => $node->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function updatePage(string $slug, array $fields): array
    {
        $node = Node::query()->where('slug', $slug)->first();

        if (! $node) {
            return ['success' => false, 'message' => "Seite '{$slug}' nicht gefunden."];
        }

        $updatable = ['title', 'status'];

        foreach ($updatable as $field) {
            if (isset($fields[$field])) {
                $node->{$field} = $fields[$field];
            }
        }

        if (isset($fields['slug']) && $fields['slug'] !== $slug) {
            $node->slug = $fields['slug'];
        }

        $node->save();

        if (isset($fields['content'])) {
            $node->setMeta('content', $this->sanitizer->sanitize($fields['content']));

            // Auto-switch from "welcome" template when content is set
            $currentTemplate = $node->getMeta('template', default: 'default');

            if ($currentTemplate === 'welcome' && ! isset($fields['template'])) {
                $node->setMeta('template', $slug === 'home' ? 'home' : 'default');
            }
        }

        foreach (['meta_description', 'template'] as $metaField) {
            if (isset($fields[$metaField])) {
                $node->setMeta($metaField, $fields[$metaField]);
            }
        }

        $node->load('meta');
        $node->createRevision('Page updated: '.$node->title);

        return [
            'success' => true,
            'message' => "Seite '{$node->title}' (/{$node->slug}) wurde aktualisiert.",
            'slug' => $node->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deletePage(string $slug): array
    {
        $node = Node::query()->where('slug', $slug)->first();

        if (! $node) {
            return ['success' => false, 'message' => "Seite '{$slug}' nicht gefunden."];
        }

        $title = $node->title;
        $node->delete();

        return [
            'success' => true,
            'message' => "Seite '{$title}' (/{$slug}) wurde gelöscht.",
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function createMenu(string $name, array $items): array
    {
        $slug = str($name)->slug()->toString();

        $menu = Node::query()->updateOrCreate(
            ['slug' => $slug, 'type' => NodeType::Menu],
            ['title' => $name, 'status' => NodeStatus::Published],
        );

        $menu->menuItems()->delete();
        $this->createMenuItems($menu, $items);
        $menu->load('menuItems');
        $menu->createRevision('Menu created: '.$name);

        return [
            'success' => true,
            'message' => "Menu '{$name}' wurde erstellt mit ".count($items).' Einträgen.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function updateMenu(string $menuName, array $items): array
    {
        $slug = str($menuName)->slug()->toString();
        $menu = Node::query()->where('slug', $slug)->ofType(NodeType::Menu)->first();

        if (! $menu) {
            return $this->createMenu($menuName, $items);
        }

        $menu->menuItems()->delete();
        $this->createMenuItems($menu, $items);
        $menu->load('menuItems');
        $menu->createRevision('Menu updated: '.$menuName);

        return [
            'success' => true,
            'message' => "Menu '{$menuName}' wurde aktualisiert.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function setSetting(string $key, mixed $value, string $group = 'general'): array
    {
        Setting::set($key, $value, $group);

        return [
            'success' => true,
            'message' => "Einstellung '{$key}' wurde gesetzt.",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSiteState(): array
    {
        $pages = Node::query()
            ->ofType(NodeType::Page)
            ->with('meta')
            ->get()
            ->map(fn (Node $n) => [
                'slug' => $n->slug,
                'title' => $n->title,
                'status' => $n->status->value,
                'template' => $n->getMeta('template', default: 'default'),
                'content_length' => strlen($n->getMeta('content') ?? ''),
                'has_content' => ! empty($n->getMeta('content')),
            ])
            ->all();

        $menus = Node::query()
            ->ofType(NodeType::Menu)
            ->with('menuItems')
            ->get()
            ->map(fn (Node $m) => [
                'name' => $m->title,
                'slug' => $m->slug,
                'items_count' => $m->menuItems->count(),
            ])
            ->all();

        $settings = Setting::all()
            ->mapWithKeys(fn (Setting $s) => [$s->key => Setting::get($s->key)])
            ->all();

        $plugins = Plugin::where('is_active', true)->get()
            ->map(fn (Plugin $p) => [
                'name' => $p->name,
                'slug' => $p->slug,
                'shortcodes' => collect($p->getShortcodes())->map(fn (array $s) => [
                    'tag' => $s['tag'],
                    'description' => $s['description'] ?? '',
                    'example' => $s['example'] ?? "[{$s['tag']}]",
                ])->all(),
            ])
            ->all();

        return [
            'pages' => $pages,
            'menus' => $menus,
            'settings' => $settings,
            'installed_plugins' => $plugins,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageContent(string $slug): array
    {
        $node = Node::query()->where('slug', $slug)->with('meta')->first();

        if (! $node) {
            return ['success' => false, 'message' => "Seite '{$slug}' nicht gefunden."];
        }

        return [
            'slug' => $node->slug,
            'title' => $node->title,
            'status' => $node->status->value,
            'content' => $node->getMeta('content'),
            'meta_description' => $node->getMeta('meta_description'),
            'template' => $node->getMeta('template', default: 'default'),
        ];
    }

    /**
     * @param  array<string, mixed>  $headerConfig
     * @param  array<string, mixed>  $footerConfig
     * @return array<string, mixed>
     */
    public function setHeaderFooter(array $headerConfig, array $footerConfig): array
    {
        if (! empty($headerConfig)) {
            Setting::set('header_config', $headerConfig, 'layout');
        }

        if (! empty($footerConfig)) {
            Setting::set('footer_config', $footerConfig, 'layout');
        }

        return [
            'success' => true,
            'message' => 'Header/Footer-Konfiguration wurde aktualisiert.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createMenuItems(Node $menu, array $items, ?string $parentId = null, int &$order = 0): void
    {
        foreach ($items as $item) {
            $url = null;
            $nodeId = null;

            if (isset($item['slug_or_url'])) {
                $linkedNode = Node::query()->where('slug', $item['slug_or_url'])->first();

                if ($linkedNode) {
                    $nodeId = $linkedNode->id;
                } else {
                    $url = $item['slug_or_url'];
                }
            }

            if (isset($item['url'])) {
                $url = $item['url'];
            }

            $menuItem = MenuItem::query()->create([
                'menu_id' => $menu->id,
                'label' => $item['label'],
                'url' => $url,
                'node_id' => $nodeId,
                'parent_id' => $parentId,
                'sort_order' => $order++,
                'target' => $item['target'] ?? '_self',
            ]);

            if (! empty($item['children'])) {
                $this->createMenuItems($menu, $item['children'], $menuItem->id, $order);
            }
        }
    }
}
