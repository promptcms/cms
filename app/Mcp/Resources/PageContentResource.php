<?php

namespace App\Mcp\Resources;

use App\Services\CmsToolService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Gibt den vollständigen Inhalt einer CMS-Seite zurück (Titel, Content, Template, Meta).')]
class PageContentResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('cms://pages/{slug}');
    }

    public function handle(Request $request, CmsToolService $service): Response
    {
        $slug = $request->get('slug');
        $content = $service->getPageContent($slug);

        return Response::text(json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
