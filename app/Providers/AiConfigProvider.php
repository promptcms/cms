<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class AiConfigProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $encryptedKey = Setting::get('openai_api_key');

            if ($encryptedKey) {
                $apiKey = decrypt($encryptedKey);
                config(['ai.providers.openai.key' => $apiKey]);
            }

            $model = Setting::get('ai_model');

            if ($model) {
                config(['ai.model' => $model]);
            }
        } catch (\Throwable) {
            // DB not ready yet (migrations not run), skip
        }
    }
}
