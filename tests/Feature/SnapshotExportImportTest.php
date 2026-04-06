<?php

use App\Models\CmsSnapshot;
use App\Models\Node;
use App\Models\Setting;
use App\Services\CmsSnapshotService;
use App\Services\SnapshotMigrator;
use Illuminate\Support\Str;

it('exports a snapshot as a JSON envelope with current format version', function () {
    $node = Node::factory()->create(['slug' => 'home', 'title' => 'Home']);
    $node->meta()->create(['key' => 'body_html', 'value' => '<h1>Hi</h1>', 'locale' => 'de']);
    Setting::create(['key' => 'site_name', 'value' => 'Acme', 'group' => 'general']);

    $service = app(CmsSnapshotService::class);
    $snapshot = $service->createSnapshot('Test', 'demo');

    $json = $service->exportSnapshot($snapshot);
    $envelope = json_decode($json, true);

    expect($envelope)
        ->toBeArray()
        ->and($envelope['kind'])->toBe('promptcms-snapshot')
        ->and($envelope['format_version'])->toBe(CmsSnapshotService::FORMAT_VERSION)
        ->and($envelope['data']['format_version'])->toBe(CmsSnapshotService::FORMAT_VERSION)
        ->and($envelope['data']['nodes'])->toHaveCount(1)
        ->and($envelope['data']['nodes'][0]['slug'])->toBe('home')
        ->and($envelope['data']['settings'])->toHaveCount(1);
});

it('imports a snapshot file as a new database record without restoring', function () {
    $node = Node::factory()->create(['slug' => 'about', 'title' => 'About']);
    $service = app(CmsSnapshotService::class);
    $original = $service->createSnapshot('Original');
    $json = $service->exportSnapshot($original);

    // Wipe the source data so we can prove the import does NOT auto-restore
    Node::query()->delete();
    CmsSnapshot::query()->delete();

    $imported = $service->importSnapshot($json, createdBy: 'tester');

    expect($imported)->toBeInstanceOf(CmsSnapshot::class)
        ->and($imported->label)->toStartWith('[Import]')
        ->and($imported->created_by)->toBe('tester')
        ->and(Node::count())->toBe(0); // not restored yet

    $service->restoreSnapshot($imported);

    expect(Node::count())->toBe(1)
        ->and(Node::first()->slug)->toBe('about');
});

it('rejects files that are not PromptCMS snapshot exports', function () {
    app(CmsSnapshotService::class)->importSnapshot('{"foo":"bar"}');
})->throws(InvalidArgumentException::class, 'not a PromptCMS snapshot');

it('rejects malformed JSON', function () {
    app(CmsSnapshotService::class)->importSnapshot('not json');
})->throws(InvalidArgumentException::class, 'valid JSON');

it('rejects snapshots from a newer format version', function () {
    $envelope = json_encode([
        'kind' => 'promptcms-snapshot',
        'format_version' => 99,
        'data' => [
            'format_version' => 99,
            'nodes' => [],
            'menu_items' => [],
            'settings' => [],
        ],
    ]);

    app(CmsSnapshotService::class)->importSnapshot($envelope);
})->throws(InvalidArgumentException::class, 'newer format');

it('migrates legacy v1 snapshots (snapshot_version: "1.0") to current format', function () {
    $migrator = new SnapshotMigrator;

    $legacy = [
        'snapshot_version' => '1.0',
        'nodes' => [],
        'menu_items' => [],
        'settings' => [],
        'created_at' => '2026-01-01T00:00:00+00:00',
    ];

    expect($migrator->detectVersion($legacy))->toBe(1);

    $migrated = $migrator->migrate($legacy, CmsSnapshotService::FORMAT_VERSION);

    expect($migrated['format_version'])->toBe(CmsSnapshotService::FORMAT_VERSION)
        ->and($migrated)->not->toHaveKey('snapshot_version');
});

it('restores legacy v1 snapshot rows from the database via migrator', function () {
    // Simulate a snapshot row that was written before the format-version bump
    $snapshot = CmsSnapshot::create([
        'label' => 'Legacy',
        'snapshot' => [
            'snapshot_version' => '1.0',
            'nodes' => [
                [
                    'id' => (string) Str::ulid(),
                    'type' => 'page',
                    'slug' => 'legacy-home',
                    'title' => 'Legacy Home',
                    'status' => 'published',
                    'parent_id' => null,
                    'sort_order' => 0,
                    'meta' => [],
                ],
            ],
            'menu_items' => [],
            'settings' => [],
        ],
        'created_by' => 'system',
    ]);

    app(CmsSnapshotService::class)->restoreSnapshot($snapshot);

    expect(Node::where('slug', 'legacy-home')->exists())->toBeTrue();
});
