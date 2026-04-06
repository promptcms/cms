# PromptCMS — Project Notes

## Snapshot & Media Library Export/Import

PromptCMS has two independent export/import systems with their own format versions:

1. **CMS Snapshots** — full content state (pages, menus, settings) as JSON
2. **Media Library** — uploaded files as ZIP

They are deliberately decoupled: snapshots reference media by URL/ID, but do not bundle the binary files. To migrate a full site between installations, export both.

---

### CMS Snapshots

#### Format

Snapshots are versioned via an integer `format_version`. The current version is defined in `CmsSnapshotService::FORMAT_VERSION` (currently `2`).

Export envelope shape:

```json
{
  "kind": "promptcms-snapshot",
  "format_version": 2,
  "app_version": "dev",
  "exported_at": "2026-04-06T12:00:00+00:00",
  "label": "Before relaunch",
  "description": "...",
  "data": {
    "format_version": 2,
    "app_version": "dev",
    "exported_at": "...",
    "nodes": [ ... ],
    "menu_items": [ ... ],
    "settings": [ ... ]
  }
}
```

Legacy v1 snapshots (created before the bump) used `snapshot_version: '1.0'` (string) and no `format_version`. The migrator detects these automatically.

#### Components

- [`app/Services/SnapshotMigrator.php`](../app/Services/SnapshotMigrator.php) — pure data transformer, no DB access. Has a `MIGRATIONS` map of `fromVersion => methodName`. Throws `InvalidArgumentException` if a snapshot is newer than the current `FORMAT_VERSION`.
- [`app/Services/CmsSnapshotService.php`](../app/Services/CmsSnapshotService.php) — `buildPayload()`, `createSnapshot()`, `exportSnapshot()`, `importSnapshot()`, `restoreSnapshot()`, `diffSnapshot()`. All read paths run the payload through the migrator, so legacy DB rows stay restorable.
- [`app/Filament/Pages/CmsVersions.php`](../app/Filament/Pages/CmsVersions.php) + [blade](../resources/views/filament/pages/cms-versions.blade.php) — Filament UI with per-snapshot **Export** button and a global **Import** upload.

#### Workflow

- **Export**: Click "Export" next to a snapshot → downloads `promptcms-snapshot-{slug}-{date}.json`.
- **Import**: Upload a `.json` file → creates a **new `CmsSnapshot` record** with label `[Import] ...`. Does **not** restore automatically. The user reviews and clicks "Wiederherstellen" as a separate step (snapshot + freigabe workflow). An auto-backup of the current state is created before any restore.

#### Bumping the format version

When the snapshot data shape changes:

1. Increment `CmsSnapshotService::FORMAT_VERSION`.
2. In `SnapshotMigrator::MIGRATIONS`, add `<oldVersion> => 'migrateVxToVy'`.
3. Add a `private function migrateVxToVy(array $payload): array` method that transforms the payload in memory only — **no DB calls, no model instantiation**.
4. Add a Pest test that feeds an old-version payload through `SnapshotMigrator::migrate()` and asserts the new shape.

The migrator runs on every read path (`restoreSnapshot`, `exportSnapshot`, `importSnapshot`, `diffSnapshot`), so existing DB-stored snapshots are automatically upgraded on access.

#### What is NOT in the snapshot

- Media binaries (use the media library export below)
- AI sessions, plugins, users
- Generated CSS / cache

#### What is NOT checked

- Laravel / PHP / Filament version. Only the snapshot data format matters; runtime versions are noise.

---

### Media Library

#### Format

ZIP archive with its own independent `format_version` (currently `1`, defined in `MediaLibraryExportService::FORMAT_VERSION`).

```
promptcms-media-2026-04-06_12-00.zip
├── manifest.json
└── files/
    ├── 1_logo.png
    ├── 2_hero.jpg
    └── ...
```

The manifest:

```json
{
  "kind": "promptcms-media-library",
  "format_version": 1,
  "app_version": "dev",
  "exported_at": "...",
  "count": 42,
  "items": [
    {
      "id": 1,
      "name": "logo",
      "file_name": "logo.png",
      "mime_type": "image/png",
      "size": 12345,
      "collection_name": "default",
      "archive_path": "files/1_logo.png"
    }
  ]
}
```

Files inside `files/` are prefixed with the source media id to avoid filename collisions in the archive itself.

#### Components

- [`app/Services/MediaLibraryExportService.php`](../app/Services/MediaLibraryExportService.php) — `export()` returns a temp file path (caller must delete after streaming). `import()` validates the manifest, checks the format version, and re-uploads files into the global `MediaContainer`.
- [`app/Filament/Pages/MediaLibrary.php`](../app/Filament/Pages/MediaLibrary.php) + [blade](../resources/views/filament/pages/media-library.blade.php) — Filament UI with **Export (ZIP)** button and **Import (ZIP)** upload.

#### Import behavior

- **Skips duplicates** by lowercased `file_name` against the existing global container. This makes re-imports safe and idempotent.
- Returns `['imported' => N, 'skipped' => N]`, surfaced in the UI notification.
- Rejects archives whose manifest is missing, has the wrong `kind`, or whose `format_version` is newer than supported.

#### Bumping the format version

Same pattern as the snapshot service: bump `FORMAT_VERSION`, add a manifest migration step, write a test. Currently no migration logic is needed since v1 is the only version.

---

### Tests

[`tests/Feature/SnapshotExportImportTest.php`](../tests/Feature/SnapshotExportImportTest.php) covers:

- Export envelope shape and `format_version` correctness
- Import creates a new record but does not auto-restore
- Roundtrip (export → wipe → import → restore) preserves data
- Rejection of malformed JSON, foreign envelopes, and newer-version payloads
- Migrator detection and upgrade of legacy v1 payloads
- `restoreSnapshot()` works on legacy DB rows via the migrator path

Run with:

```bash
php artisan test --compact --filter=SnapshotExportImportTest
```

---

### Design decisions worth remembering

- **Two separate format versions** (snapshot vs. media). They evolve independently and bumping one should not force the other.
- **Import ≠ restore.** Imports always land in the snapshots list as a new entry. Restore is a deliberate second click with an auto-backup beforehand. This is the "snapshot + freigabe" workflow the user explicitly asked for.
- **Migrator is pure.** No Eloquent, no DB. This keeps it testable and safe to call on read paths.
- **No version checks beyond the format.** Cross-checking Laravel/PHP/Filament versions adds noise without preventing real incompatibilities. The format version is the single source of truth for compatibility.
- **Media is opt-in and separate.** Snapshots stay small and fast. Sites that need full migration export both artifacts.
