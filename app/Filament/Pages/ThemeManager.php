<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\CmsSnapshotService;
use App\Services\CssBuildService;
use App\Services\PresetService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ThemeManager extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Themes';

    protected static ?string $title = 'Themes';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.theme-manager';

    /** @var Collection<int, array<string, mixed>> */
    public Collection $themes;

    public bool $registryError = false;

    public string $registryErrorMessage = '';

    public ?string $activeTheme = null;

    public function mount(): void
    {
        $this->activeTheme = Setting::get('active_preset');
        $this->loadThemes();
    }

    public function loadThemes(): void
    {
        try {
            $this->themes = app(PresetService::class)->all();
            $this->registryError = $this->themes->isEmpty();

            if ($this->registryError) {
                $this->registryErrorMessage = 'Keine Themes in der Registry gefunden.';
            }
        } catch (\Throwable $e) {
            $this->themes = collect();
            $this->registryError = true;
            $this->registryErrorMessage = $e->getMessage();
        }
    }

    public function activateTheme(string $slug): void
    {
        try {
            // Auto-snapshot before theme change
            $currentTheme = $this->activeTheme ?? 'unbekannt';
            app(CmsSnapshotService::class)->createSnapshot(
                "Vor Theme-Wechsel zu '{$slug}'",
                "Automatisches Backup vor dem Wechsel von '{$currentTheme}' zu '{$slug}'.",
                'theme-manager',
            );

            $result = app(PresetService::class)->apply($slug);

            if (! $result) {
                Notification::make()
                    ->title('Theme nicht gefunden')
                    ->body("Das Theme '{$slug}' konnte nicht geladen werden.")
                    ->danger()
                    ->send();

                return;
            }

            $this->activeTheme = $slug;

            // Recompile CSS with new design system
            CssBuildService::compileSync();

            $theme = app(PresetService::class)->get($slug);

            Notification::make()
                ->title("Theme '{$theme['name']}' aktiviert")
                ->body('Ein Backup wurde erstellt. Du kannst es unter "Versionen" wiederherstellen.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Aktivierung fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
