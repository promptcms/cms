<?php

namespace App\Http\Controllers;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Setting;
use App\Services\ShortcodeRenderer;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function __construct(
        private ShortcodeRenderer $shortcodeRenderer,
    ) {}

    public function show(string $slug): View
    {
        $page = Node::query()
            ->where('slug', $slug)
            ->ofType(NodeType::Page)
            ->published()
            ->with('meta')
            ->first();

        if (! $page) {
            throw new NotFoundHttpException;
        }

        return $this->renderPage($page);
    }

    public function home(): View
    {
        $page = Node::query()
            ->where('slug', 'home')
            ->ofType(NodeType::Page)
            ->published()
            ->with('meta')
            ->first();

        // Show welcome page if no home page exists or it has no content yet
        if (! $page || ! $page->getMeta('content')) {
            return view('templates.welcome');
        }

        return $this->renderPage($page);
    }

    private function renderPage(Node $page): View
    {
        $template = $page->getMeta('template', default: 'default');

        // If template is "welcome" but page has content, use "home" instead
        if ($template === 'welcome' && $page->getMeta('content')) {
            $template = 'home';
        }

        $viewName = "templates.{$template}";

        if (! view()->exists($viewName)) {
            $viewName = 'templates.default';
        }

        $headerMenu = $this->getMenu('header-menu');
        $footerMenu = $this->getMenu('footer-menu');
        $settings = Setting::all()->mapWithKeys(fn (Setting $s) => [$s->key => Setting::get($s->key)])->all();

        // Process shortcodes in page content
        $content = $page->getMeta('content', '');

        if ($content) {
            $content = $this->shortcodeRenderer->render($content);
        }

        // Process {{nav:slug}} placeholders in DB-driven layout parts
        if (! empty($settings['layout_header'])) {
            $settings['layout_header'] = $this->renderNavPlaceholders($settings['layout_header']);
        }

        if (! empty($settings['layout_footer'])) {
            $settings['layout_footer'] = $this->renderNavPlaceholders($settings['layout_footer']);
        }

        return view($viewName, [
            'page' => $page,
            'renderedContent' => $content,
            'headerMenu' => $headerMenu,
            'footerMenu' => $footerMenu,
            'settings' => $settings,
        ]);
    }

    /**
     * Replace {{nav:menu-slug}} placeholders with rendered menu HTML.
     */
    private function renderNavPlaceholders(string $html): string
    {
        return (string) preg_replace_callback('/\{\{nav:([a-z0-9\-]+)\}\}/', function (array $matches) {
            $menu = $this->getMenu($matches[1]);

            if (! $menu || $menu->menuItems->isEmpty()) {
                return '';
            }

            $links = $menu->menuItems->map(function ($item) {
                $url = $item->resolveUrl();
                $target = $item->target !== '_self' ? " target=\"{$item->target}\"" : '';

                return "<a href=\"{$url}\"{$target}>{$item->label}</a>";
            })->implode("\n");

            return $links;
        }, $html);
    }

    private function getMenu(string $slug): ?Node
    {
        return Node::query()
            ->where('slug', $slug)
            ->ofType(NodeType::Menu)
            ->where('status', NodeStatus::Published)
            ->with(['menuItems' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order')->with('children', 'linkedNode')])
            ->first();
    }
}
