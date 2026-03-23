<?php

namespace App\Http\Controllers;

use App\Enums\NodeType;
use App\Models\Node;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $pages = Node::query()
            ->ofType(NodeType::Page)
            ->published()
            ->get();

        $content = view('seo.sitemap', ['pages' => $pages])->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
