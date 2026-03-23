<?php

namespace App\Mcp\Resources;

use App\Services\CmsToolService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('cms://site-state')]
#[Description('Aktueller Zustand der Website: alle Seiten, Menüs, Einstellungen und aktive Plugins.')]
class SiteStateResource extends Resource
{
    public function handle(Request $request, CmsToolService $service): Response
    {
        $state = $service->getSiteState();

        return Response::text(json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
