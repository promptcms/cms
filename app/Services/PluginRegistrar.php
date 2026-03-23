<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class PluginRegistrar
{
    /**
     * Register all active plugins (routes, views, config).
     * Called from AppServiceProvider::boot().
     */
    public function register(): void
    {
        if (! $this->isReady()) {
            return;
        }

        $plugins = Plugin::where('is_active', true)->get();

        foreach ($plugins as $plugin) {
            $this->registerViews($plugin);
            $this->registerRoutes($plugin);
            $this->registerConfig($plugin);
        }
    }

    private function registerViews(Plugin $plugin): void
    {
        $viewsDir = $plugin->manifest['views'] ?? 'views';
        $viewsPath = $plugin->basePath().'/'.$viewsDir;

        if (is_dir($viewsPath)) {
            View::addNamespace("plugin-{$plugin->slug}", $viewsPath);
        }
    }

    private function registerRoutes(Plugin $plugin): void
    {
        $routesFile = $plugin->manifest['routes'] ?? null;

        if (! $routesFile) {
            return;
        }

        $routesPath = $plugin->basePath().'/'.$routesFile;

        if (file_exists($routesPath)) {
            Route::middleware('web')
                ->prefix("p/{$plugin->slug}")
                ->group($routesPath);
        }
    }

    private function registerConfig(Plugin $plugin): void
    {
        $configFile = $plugin->manifest['config'] ?? null;

        if (! $configFile) {
            return;
        }

        $configPath = $plugin->basePath().'/'.$configFile;

        if (file_exists($configPath)) {
            $configName = pathinfo($configFile, PATHINFO_FILENAME);
            $values = require $configPath;

            config()->set($configName, array_merge(
                config($configName, []),
                $values
            ));
        }
    }

    /**
     * Check if the plugins table exists (avoids errors during migration).
     */
    private function isReady(): bool
    {
        try {
            return Schema::hasTable('plugins');
        } catch (\Throwable) {
            return false;
        }
    }
}
