<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppIsInstalled
{
    /**
     * Redirect to the installer if no admin user exists yet.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip installer routes
        if ($request->is('install', 'install/*')) {
            return $next($request);
        }

        // Skip static assets, health check, and Livewire internals
        if ($request->is('up', 'css/*', 'js/*', 'build/*', 'storage/*', 'favicon.ico', 'livewire/*')) {
            return $next($request);
        }

        try {
            if (User::count() === 0) {
                return redirect('/install');
            }
        } catch (\Throwable) {
            // DB doesn't exist or table not migrated — redirect to installer
            return redirect('/install');
        }

        return $next($request);
    }
}
