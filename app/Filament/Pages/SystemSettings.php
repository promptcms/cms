<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SystemSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Einstellungen';

    protected static ?string $title = 'Einstellungen';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => Setting::get('site_name', 'PromptCMS'),
            'site_tagline' => Setting::get('site_tagline', ''),
            'contact_email' => Setting::get('contact_email', ''),
            'openai_api_key' => rescue(fn () => decrypt(Setting::get('openai_api_key')), '', false),
            'ai_model' => Setting::get('ai_model', 'gpt-5.4'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Website')
                    ->description('Allgemeine Einstellungen für deine Website.')
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Website-Name')
                            ->required(),
                        TextInput::make('site_tagline')
                            ->label('Tagline'),
                        TextInput::make('contact_email')
                            ->label('Kontakt E-Mail')
                            ->email(),
                    ])
                    ->columns(2),

                Section::make('KI-Konfiguration')
                    ->description('API-Schlüssel und Modell für die KI-Integration.')
                    ->schema([
                        TextInput::make('openai_api_key')
                            ->label('OpenAI API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('sk-...'),
                        TextInput::make('ai_model')
                            ->label('Modell')
                            ->placeholder('z.B. gpt-4.1, o3, o4-mini, chatgpt-4o-latest')
                            ->helperText('Beliebiges OpenAI-Modell eingeben. Aktuell empfohlen: gpt-4.1, o3-mini, o4-mini'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('site_name', $data['site_name']);
        Setting::set('site_tagline', $data['site_tagline'] ?? '');
        Setting::set('contact_email', $data['contact_email'] ?? '');
        Setting::set('ai_model', $data['ai_model'] ?? 'gpt-5.4', 'ai');

        if (! empty($data['openai_api_key'])) {
            Setting::set('openai_api_key', encrypt($data['openai_api_key']), 'ai');
        }

        Notification::make()
            ->title('Einstellungen gespeichert')
            ->success()
            ->send();
    }
}
