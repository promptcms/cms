<?php

use App\Providers\AiConfigProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    AiConfigProvider::class,
    AdminPanelProvider::class,
];
