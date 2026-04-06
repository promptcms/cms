<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Migrates a snapshot payload from older format versions to the current one.
 *
 * Whenever the snapshot data shape changes, bump CmsSnapshotService::FORMAT_VERSION
 * and add a new `migrateVxToVy()` method plus an entry in the migrations map.
 */
class SnapshotMigrator
{
    /**
     * Map of "from version" => migration callable that returns the upgraded payload.
     *
     * @var array<int, string>
     */
    private const MIGRATIONS = [
        1 => 'migrateV1ToV2',
        // 2 => 'migrateV2ToV3',
    ];

    /**
     * Bring a snapshot payload to the current FORMAT_VERSION.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function migrate(array $payload, int $targetVersion): array
    {
        $current = $this->detectVersion($payload);

        if ($current > $targetVersion) {
            throw new InvalidArgumentException(
                "Snapshot was created with a newer format (v{$current}) than this installation supports (v{$targetVersion}). Please update PromptCMS."
            );
        }

        while ($current < $targetVersion) {
            $method = self::MIGRATIONS[$current] ?? null;

            if ($method === null) {
                throw new InvalidArgumentException(
                    "No migration available from snapshot format v{$current} to v{$targetVersion}."
                );
            }

            $payload = $this->{$method}($payload);
            $current++;
            $payload['format_version'] = $current;
        }

        return $payload;
    }

    /**
     * Detect the format version of a snapshot payload.
     *
     * Legacy snapshots used `snapshot_version: '1.0'` (string) and no `format_version` key.
     *
     * @param  array<string, mixed>  $payload
     */
    public function detectVersion(array $payload): int
    {
        if (isset($payload['format_version']) && is_int($payload['format_version'])) {
            return $payload['format_version'];
        }

        // Legacy v1: identified by `snapshot_version` string or absence of format_version
        return 1;
    }

    /**
     * v1 → v2: introduce explicit integer format_version, keep all other fields untouched.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function migrateV1ToV2(array $payload): array
    {
        // v1 payloads use a string `snapshot_version`. Drop it in favor of integer `format_version`.
        unset($payload['snapshot_version']);

        return $payload;
    }
}
