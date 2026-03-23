<?php

namespace App\Http\Middleware;

use App\Services\CssBuildService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsCssCompiled
{
    /**
     * Ensure CMS CSS is up-to-date before serving a frontend page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        CssBuildService::compileIfNeeded();

        return $next($request);
    }
}
