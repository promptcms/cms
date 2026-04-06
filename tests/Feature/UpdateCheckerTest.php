<?php

use App\Services\UpdateChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('returns null when github api fails', function () {
    Http::fake([
        'api.github.com/*' => Http::response(null, 503),
    ]);
    config()->set('app.version', '0.1.0');

    $checker = new UpdateChecker;

    expect($checker->getLatestRelease())->toBeNull();
    expect($checker->hasUpdate())->toBeFalse();
});

it('detects available update when latest tag is newer', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v0.2.0',
            'name' => 'v0.2.0',
            'html_url' => 'https://github.com/promptcms/cms/releases/tag/v0.2.0',
            'published_at' => '2026-04-06T12:00:00Z',
        ]),
    ]);
    config()->set('app.version', '0.1.0');

    $checker = new UpdateChecker;

    expect($checker->hasUpdate())->toBeTrue();
    expect($checker->getLatestRelease())->toMatchArray([
        'tag' => 'v0.2.0',
        'url' => 'https://github.com/promptcms/cms/releases/tag/v0.2.0',
    ]);
});

it('reports no update when current version equals latest', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v0.1.1',
            'name' => 'v0.1.1',
            'html_url' => 'https://github.com/promptcms/cms/releases/tag/v0.1.1',
        ]),
    ]);
    config()->set('app.version', '0.1.1');

    expect((new UpdateChecker)->hasUpdate())->toBeFalse();
});

it('handles v-prefix and missing prefix the same way', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v0.1.2',
            'name' => 'v0.1.2',
            'html_url' => 'https://github.com/promptcms/cms/releases/tag/v0.1.2',
        ]),
    ]);
    config()->set('app.version', '0.1.1');

    expect((new UpdateChecker)->hasUpdate())->toBeTrue();
});

it('skips update check on dev versions', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v9.9.9',
            'name' => 'v9.9.9',
            'html_url' => 'https://github.com/promptcms/cms/releases/tag/v9.9.9',
        ]),
    ]);
    config()->set('app.version', '0.1.0-dev');

    expect((new UpdateChecker)->hasUpdate())->toBeFalse();
});

it('caches the api response', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v0.2.0',
            'name' => 'v0.2.0',
            'html_url' => 'https://github.com/promptcms/cms/releases/tag/v0.2.0',
        ]),
    ]);
    config()->set('app.version', '0.1.0');

    $checker = new UpdateChecker;
    $checker->getLatestRelease();
    $checker->getLatestRelease();
    $checker->getLatestRelease();

    Http::assertSentCount(1);
});

it('forgets the cached release', function () {
    Http::fake([
        'api.github.com/*' => Http::response([
            'tag_name' => 'v0.2.0',
            'name' => 'v0.2.0',
            'html_url' => 'https://github.com/promptcms/cms/releases/tag/v0.2.0',
        ]),
    ]);
    config()->set('app.version', '0.1.0');

    $checker = new UpdateChecker;
    $checker->getLatestRelease();
    $checker->forget();
    $checker->getLatestRelease();

    Http::assertSentCount(2);
});
