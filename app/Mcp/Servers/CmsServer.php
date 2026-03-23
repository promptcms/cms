<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\PageContentResource;
use App\Mcp\Resources\SiteStateResource;
use App\Mcp\Tools\CreateMenuTool;
use App\Mcp\Tools\CreatePageTool;
use App\Mcp\Tools\DeletePageTool;
use App\Mcp\Tools\GetMediaUrlTool;
use App\Mcp\Tools\ListMediaTool;
use App\Mcp\Tools\RollbackPageTool;
use App\Mcp\Tools\SetHeaderFooterTool;
use App\Mcp\Tools\SetLayoutHtmlTool;
use App\Mcp\Tools\SetSettingTool;
use App\Mcp\Tools\UpdateMenuTool;
use App\Mcp\Tools\UpdatePageTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('PromptCMS')]
#[Version('1.0.0')]
#[Instructions('PromptCMS MCP Server – Manage pages, menus, settings and media of an AI-driven CMS. Use get_site_state or the SiteState resource to see the current state. Create pages with HTML/Tailwind CSS content.')]
class CmsServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        CreatePageTool::class,
        UpdatePageTool::class,
        DeletePageTool::class,
        CreateMenuTool::class,
        UpdateMenuTool::class,
        SetSettingTool::class,
        SetHeaderFooterTool::class,
        SetLayoutHtmlTool::class,
        ListMediaTool::class,
        GetMediaUrlTool::class,
        RollbackPageTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        SiteStateResource::class,
        PageContentResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
