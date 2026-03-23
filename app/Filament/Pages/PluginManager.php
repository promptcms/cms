<?php

namespace App\Filament\Pages;

use App\Models\Plugin;
use App\Services\PluginService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class PluginManager extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Plugins';

    protected static ?string $title = 'Plugins';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.plugin-manager';

    /** @var Collection<int, array<string, mixed>> */
    public Collection $availablePlugins;

    public bool $registryError = false;

    public string $registryErrorMessage = '';

    public function mount(): void
    {
        $this->loadPlugins();
    }

    public function loadPlugins(): void
    {
        try {
            $this->availablePlugins = app(PluginService::class)->getAvailablePlugins();
            $this->registryError = false;
        } catch (\Throwable $e) {
            $this->availablePlugins = collect();
            $this->registryError = true;
            $this->registryErrorMessage = $e->getMessage();

            // Still show locally installed plugins
            $installed = Plugin::all();

            $this->availablePlugins = $installed->map(fn (Plugin $p) => [
                'slug' => $p->slug,
                'name' => $p->name,
                'version' => $p->version,
                'description' => $p->description,
                'icon' => $p->manifest['icon'] ?? 'puzzle-piece',
                'category' => $p->manifest['category'] ?? 'unknown',
                'installed' => true,
                'is_active' => $p->is_active,
                'update_available' => false,
            ]);
        }
    }

    public function installPlugin(string $slug): void
    {
        try {
            app(PluginService::class)->install($slug);

            Notification::make()
                ->title("Plugin '{$slug}' installiert")
                ->body('Das Plugin kann jetzt aktiviert werden.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Installation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->loadPlugins();
    }

    public function activatePlugin(string $slug): void
    {
        try {
            app(PluginService::class)->activate($slug);

            Notification::make()
                ->title("Plugin '{$slug}' aktiviert")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Aktivierung fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->loadPlugins();
    }

    public function deactivatePlugin(string $slug): void
    {
        try {
            app(PluginService::class)->deactivate($slug);

            Notification::make()
                ->title("Plugin '{$slug}' deaktiviert")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Deaktivierung fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->loadPlugins();
    }

    public function uninstallPlugin(string $slug): void
    {
        try {
            app(PluginService::class)->uninstall($slug);

            Notification::make()
                ->title("Plugin '{$slug}' deinstalliert")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Deinstallation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->loadPlugins();
    }
}
