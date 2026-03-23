<?php

/*
|--------------------------------------------------------------------------
| Fresh Install: Auto-generate .env & APP_KEY
|--------------------------------------------------------------------------
| The EncryptionServiceProvider requires APP_KEY at boot time, before any
| controller runs. For fresh installations we must ensure .env exists and
| contains a valid key before the framework bootstraps.
*/

$envPath = dirname(__DIR__).'/.env';
$envExamplePath = dirname(__DIR__).'/.env.example';

if (! file_exists($envPath)) {
    // Detect the current URL from the request
    $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $appUrl = "{$scheme}://{$host}";

    if (file_exists($envExamplePath)) {
        $content = file_get_contents($envExamplePath);
        $content = preg_replace('/^APP_URL=.*/m', "APP_URL={$appUrl}", $content);
        file_put_contents($envPath, $content);
    } else {
        file_put_contents($envPath, implode("\n", [
            'APP_NAME=PromptCMS',
            'APP_ENV=production',
            'APP_KEY=',
            'APP_DEBUG=false',
            "APP_URL={$appUrl}",
            '',
            'DB_CONNECTION=sqlite',
            'SESSION_DRIVER=file',
            'CACHE_STORE=database',
            '',
        ]));
    }
}

$envContent = file_get_contents($envPath);

if (preg_match('/^APP_KEY=\s*$/m', $envContent)) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    $envContent = preg_replace('/^APP_KEY=\s*$/m', "APP_KEY={$key}", $envContent);
    file_put_contents($envPath, $envContent);

    // Ensure the key is available for this request
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
    putenv("APP_KEY={$key}");
}
