<?php

use App\Http\Controllers\AiChatStreamController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\EnsureCmsCssCompiled;
use Illuminate\Support\Facades\Route;

// Installer (public, only works when no users exist)
Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'install']);

Route::post('/admin/ai-chat/stream', [AiChatStreamController::class, 'stream'])
    ->middleware(['web', 'auth'])
    ->name('admin.ai-chat.stream');

Route::middleware(EnsureCmsCssCompiled::class)->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9\-\/]+')->name('page.show');
});

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
