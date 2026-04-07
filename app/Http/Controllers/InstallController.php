<?php

namespace App\Http\Controllers;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Setting;
use App\Models\User;
use App\Services\PresetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function show(): View|RedirectResponse
    {
        // Prepare everything needed before the page can even render
        $this->ensureDirectories();
        $this->ensureEnvFile();
        $this->ensureAppKey();
        $this->ensureDatabase();

        if ($this->isInstalled()) {
            return redirect('/admin');
        }

        $checks = $this->runChecks();

        return view('install', compact('checks'));
    }

    public function install(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect('/admin');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'site_name' => 'required|string|max:255',
            'site_title' => 'nullable|string|max:255',
            'preset' => 'required|string',
            'openai_api_key' => 'required|string|min:10',
        ], [
            'openai_api_key.required' => 'Ein OpenAI API-Key ist erforderlich.',
        ]);

        // Step 1: Ensure infrastructure
        $this->ensureDirectories();
        $this->ensureEnvFile();
        $this->ensureAppKey();
        $this->ensureDatabase();

        // Step 2: Validate OpenAI API key
        $keyValidation = $this->validateOpenAiKey($validated['openai_api_key']);

        if (! $keyValidation['ok']) {
            return back()
                ->withErrors(['openai_api_key' => $keyValidation['error']])
                ->withInput();
        }

        // Step 3: Store API key encrypted in database (persists across container redeploys)
        Setting::set('openai_api_key', encrypt($validated['openai_api_key']), 'ai');

        // Step 4: Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['database' => 'Migration fehlgeschlagen: '.$e->getMessage()])
                ->withInput();
        }

        // Step 5: Create admin user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        // Step 6: Seed default CMS data
        $this->seedCmsData(
            $validated['site_name'],
            $validated['site_title'] ?? $validated['site_name'],
            $validated['email'],
        );

        // Step 7: Apply design preset
        $presetApplied = app(PresetService::class)->apply($validated['preset']);

        if (! $presetApplied) {
            // Fallback: set preset as active even if theme data couldn't be fetched
            Setting::set('active_preset', $validated['preset']);
        }

        // Step 8: Post-install tasks
        $this->runPostInstall();

        // Step 9: Auto-login and redirect to welcome page
        auth()->login($user);

        return redirect('/');
    }

    /**
     * Validate an OpenAI API key by making a lightweight API call.
     *
     * @return array{ok: bool, error?: string}
     */
    private function validateOpenAiKey(string $key): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$key,
                ])
                ->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                return ['ok' => true];
            }

            if ($response->status() === 401) {
                return ['ok' => false, 'error' => 'Ungültiger API-Key. Bitte überprüfe den Schlüssel.'];
            }

            if ($response->status() === 429) {
                return ['ok' => true]; // Rate limited but key is valid
            }

            return ['ok' => false, 'error' => 'API-Fehler: '.($response->json('error.message') ?? 'Unbekannt')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Verbindung zur OpenAI API fehlgeschlagen: '.$e->getMessage()];
        }
    }

    /**
     * Write a key=value pair to the .env file.
     */
    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);

        // Replace existing key or append
        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $content);
    }

    /**
     * Ensure all required directories exist (ZIP uploads strip empty folders).
     */
    private function ensureDirectories(): void
    {
        $dirs = [
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            storage_path('cms'),
            storage_path('app/public'),
            base_path('database'),
            base_path('plugins'),
            public_path('css'),
            base_path('bootstrap/cache'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Create .env from .env.example if it doesn't exist.
     */
    private function ensureEnvFile(): void
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            return;
        }

        $examplePath = base_path('.env.example');

        if (file_exists($examplePath)) {
            copy($examplePath, $envPath);
        } else {
            file_put_contents($envPath, implode("\n", [
                'APP_NAME=PromptCMS',
                'APP_ENV=production',
                'APP_KEY=',
                'APP_DEBUG=false',
                'APP_URL='.url('/'),
                '',
                'DB_CONNECTION=sqlite',
                '',
                'SESSION_DRIVER=file',
                'QUEUE_CONNECTION=database',
                'CACHE_STORE=database',
                'FILESYSTEM_DISK=local',
                '',
                'OPENAI_API_KEY=',
                'OPENAI_MODEL=gpt-4.1',
                'CMS_DEFAULT_LOCALE=de',
                '',
            ]));
        }
    }

    /**
     * Generate APP_KEY if not set, writing it to .env.
     */
    private function ensureAppKey(): void
    {
        if (! empty(config('app.key'))) {
            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
        Artisan::call('config:clear');
    }

    /**
     * Create SQLite database file if using SQLite.
     */
    private function ensureDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $dbPath = config('database.connections.sqlite.database');

        if ($dbPath && ! file_exists($dbPath)) {
            $dir = dirname($dbPath);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            touch($dbPath);
        }
    }

    private function isInstalled(): bool
    {
        try {
            return User::count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function seedCmsData(string $siteName, string $siteTitle, string $contactEmail): void
    {
        Setting::set('site_name', $siteName);
        Setting::set('site_title', $siteTitle);
        Setting::set('site_tagline', 'Erstellt mit PromptCMS');
        Setting::set('contact_email', $contactEmail);

        $menuTypes = ['header-menu' => 'Header Menu', 'footer-menu' => 'Footer Menu'];

        foreach ($menuTypes as $slug => $title) {
            if (! Node::where('slug', $slug)->exists()) {
                Node::create([
                    'type' => NodeType::Menu,
                    'slug' => $slug,
                    'title' => $title,
                    'status' => NodeStatus::Published,
                ]);
            }
        }

        if (! Node::where('slug', 'home')->exists()) {
            $home = Node::create([
                'type' => NodeType::Page,
                'slug' => 'home',
                'title' => 'Startseite',
                'status' => NodeStatus::Published,
            ]);
            $home->setMeta('template', 'home');
            $home->setMeta('meta_description', $siteName.' — Erstellt mit PromptCMS');
        }
    }

    private function runPostInstall(): void
    {
        try {
            Artisan::call('storage:link', ['--force' => true]);
        } catch (\Throwable) {
            // Non-critical
        }

        // Download Tailwind standalone CLI if npx is not available
        if (! file_exists(storage_path('cms/tailwindcss'))) {
            try {
                Artisan::call('cms:download-tailwind');
            } catch (\Throwable) {
                // Non-critical — CDN fallback will be used
            }
        }

        try {
            Artisan::call('filament:upgrade', ['--no-interaction' => true]);
        } catch (\Throwable) {
            // Non-critical
        }

        try {
            Artisan::call('cms:compile-css');
        } catch (\Throwable) {
            // Non-critical
        }
    }

    /**
     * @return array<string, array{ok: bool, label: string, detail: string}>
     */
    private function runChecks(): array
    {
        $checks = [];

        $checks['php'] = [
            'ok' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'label' => 'PHP Version',
            'detail' => PHP_VERSION.' (min. 8.4)',
        ];

        foreach (['pdo_sqlite', 'mbstring', 'openssl', 'gd', 'fileinfo'] as $ext) {
            $checks[$ext] = [
                'ok' => extension_loaded($ext),
                'label' => "PHP Extension: {$ext}",
                'detail' => extension_loaded($ext) ? 'OK' : 'Fehlt',
            ];
        }

        $checks['storage'] = [
            'ok' => is_writable(storage_path()),
            'label' => 'Storage-Verzeichnis',
            'detail' => is_writable(storage_path()) ? 'Beschreibbar' : 'Nicht beschreibbar',
        ];

        $envExists = file_exists(base_path('.env'));
        $checks['env'] = [
            'ok' => true,
            'label' => '.env Datei',
            'detail' => $envExists ? 'Vorhanden' : 'Wird automatisch erstellt',
        ];

        $hasKey = ! empty(config('app.key'));
        $checks['app_key'] = [
            'ok' => true,
            'label' => 'APP_KEY',
            'detail' => $hasKey ? 'Gesetzt' : 'Wird automatisch generiert',
        ];

        $dbReady = false;

        try {
            DB::connection()->getPdo();
            $dbReady = true;
        } catch (\Throwable) {
            // Will be created during install
        }

        $checks['database'] = [
            'ok' => true,
            'label' => 'Datenbank (SQLite)',
            'detail' => $dbReady ? 'Verbindung OK' : 'Wird automatisch erstellt',
        ];

        return $checks;
    }
}
