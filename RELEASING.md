# Releasing PromptCMS

PromptCMS uses tag-triggered GitHub Actions for releases. To cut a new version, push a SemVer tag and the rest is automatic.

## Quick steps

```bash
# 1. Make sure master is clean and CI is green
git checkout master
git pull
git status

# 2. Tag the release (use SemVer: vMAJOR.MINOR.PATCH)
git tag v0.1.0
git push origin v0.1.0
```

The [release workflow](.github/workflows/release.yml) then:

1. Stamps the version into `config/app.php`
2. Runs `composer install --no-dev --optimize-autoloader`
3. Runs `npm ci && npm run build` to compile frontend assets
4. Packs everything into `promptcms-0.1.0.zip` (excluding `.git`, `node_modules`, `tests`, dev caches, the local SQLite DB, etc.)
5. Generates release notes from the commit log since the previous tag
6. Creates a GitHub release with the ZIP attached

You'll find the published release at `https://github.com/stack74/ccms/releases`.

## Versioning

Use SemVer (`vMAJOR.MINOR.PATCH`):

- **MAJOR** — breaking changes (snapshot format bump that requires manual migration, removed features, incompatible API changes)
- **MINOR** — new features, backwards-compatible
- **PATCH** — bug fixes, backwards-compatible
- **Pre-release** — append `-alpha.1`, `-beta.2`, `-rc.1`. The workflow detects the dash and marks the GitHub release as a pre-release automatically.

## Where the version lives

- **`config/app.php`** → `'version' => '0.1.0-dev'`. This is the source of truth at runtime, available via `config('app.version')`. The release workflow rewrites this string before building the ZIP, so released artifacts contain the actual version. Local checkouts stay on `-dev`.
- **`composer.json`** intentionally has no `version` field. Composer recommends omitting it for applications (only packages need it).
- The version surfaces in:
  - Snapshot exports (`app_version` field in the JSON envelope)
  - Media library exports (`app_version` field in `manifest.json`)
  - Anywhere else you call `config('app.version')`

## Continuous Integration

The [CI workflow](.github/workflows/ci.yml) runs on every push and pull request to `master`/`main`:

1. Composer install
2. `npm ci && npm run build`
3. Migrations against an SQLite database
4. `vendor/bin/pint --test` (style check, fails on diffs)
5. `php artisan test --compact` (Pest)

Make sure CI is green **before** tagging a release.

## Pre-release checklist

- [ ] CI is green on `master`
- [ ] `CHANGELOG.md` updated (if you keep one)
- [ ] Snapshot format version bump documented in [.claude/PROJECT.md](.claude/PROJECT.md) if the snapshot data shape changed
- [ ] Manual smoke test on a fresh install (optional but recommended for major/minor releases)
- [ ] Tag is SemVer-compliant and prefixed with `v`

## Rolling back a bad release

```bash
# Delete the tag locally and on the remote
git tag -d v0.1.0
git push origin :refs/tags/v0.1.0

# Then delete the GitHub release manually via the web UI
# (or with `gh release delete v0.1.0` if you have the gh CLI)
```

After fixing the issue, push a new patch tag (e.g. `v0.1.1`) — never re-use a deleted tag, since users may already have pulled it.

## Manual local build (if you ever need it)

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Optional: stamp the version yourself
sed -i '' "s/'version' => '[^']*'/'version' => '0.1.0'/" config/app.php

zip -r promptcms-0.1.0.zip . \
  -x "node_modules/*" ".git/*" ".github/*" "tests/*" \
     ".env" "database/database.sqlite" \
     "storage/logs/*" "storage/framework/cache/data/*" \
     "storage/framework/sessions/*" "storage/framework/views/*"

# Restore composer dev dependencies for local development
composer install
```

The CI workflow does all of this in a clean Ubuntu environment, so prefer the tag-based flow whenever possible.
