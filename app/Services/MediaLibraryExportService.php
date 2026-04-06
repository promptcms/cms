<?php

namespace App\Services;

use App\Models\MediaContainer;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use ZipArchive;

class MediaLibraryExportService
{
    public const FORMAT_VERSION = 1;

    private const MANIFEST_FILE = 'manifest.json';

    private const FILES_DIR = 'files/';

    /**
     * Build a ZIP archive of the entire media library and return the temp file path.
     * Caller is responsible for deleting the file after streaming it.
     */
    public function export(): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'promptcms-media-');

        if ($tmpPath === false) {
            throw new RuntimeException('Could not create temp file for media export.');
        }

        $zip = new ZipArchive;

        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open ZIP archive for writing.');
        }

        $items = [];

        Media::query()
            ->where('model_type', MediaContainer::class)
            ->orderBy('id')
            ->each(function (Media $media) use ($zip, &$items): void {
                $sourcePath = $media->getPath();

                if (! is_file($sourcePath)) {
                    return;
                }

                // Use media id as filename inside the archive to avoid collisions
                $archiveName = self::FILES_DIR.$media->id.'_'.$media->file_name;
                $zip->addFile($sourcePath, $archiveName);

                $items[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'collection_name' => $media->collection_name,
                    'archive_path' => $archiveName,
                ];
            });

        $manifest = [
            'kind' => 'promptcms-media-library',
            'format_version' => self::FORMAT_VERSION,
            'app_version' => (string) config('app.version', 'dev'),
            'exported_at' => now()->toIso8601String(),
            'count' => \count($items),
            'items' => $items,
        ];

        $zip->addFromString(
            self::MANIFEST_FILE,
            (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $zip->close();

        return $tmpPath;
    }

    /**
     * Import a media-library ZIP archive. Skips files whose original filename already exists
     * in the global container (avoids duplicates on re-import).
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(UploadedFile|string $zipFile): array
    {
        $path = $zipFile instanceof UploadedFile ? $zipFile->getRealPath() : $zipFile;

        if (! is_string($path) || ! is_file($path)) {
            throw new InvalidArgumentException('ZIP file not found.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Could not open ZIP archive.');
        }

        $manifestRaw = $zip->getFromName(self::MANIFEST_FILE);

        if ($manifestRaw === false) {
            $zip->close();
            throw new InvalidArgumentException('Archive is not a PromptCMS media-library export (manifest.json missing).');
        }

        $manifest = json_decode($manifestRaw, true);

        if (! is_array($manifest) || ($manifest['kind'] ?? null) !== 'promptcms-media-library') {
            $zip->close();
            throw new InvalidArgumentException('Archive is not a PromptCMS media-library export.');
        }

        $manifestVersion = (int) ($manifest['format_version'] ?? 0);

        if ($manifestVersion > self::FORMAT_VERSION) {
            $zip->close();
            throw new InvalidArgumentException(
                "Archive was created with a newer media format (v{$manifestVersion}) than this installation supports (v".self::FORMAT_VERSION.'). Please update PromptCMS.'
            );
        }

        $container = MediaContainer::firstOrCreate(['name' => 'global']);

        // Build set of existing file names to skip duplicates
        $existing = Media::query()
            ->where('model_type', MediaContainer::class)
            ->pluck('file_name')
            ->map(fn ($n) => strtolower((string) $n))
            ->all();
        $existingSet = array_flip($existing);

        $imported = 0;
        $skipped = 0;

        $tmpDir = sys_get_temp_dir().'/promptcms-media-import-'.uniqid();
        @mkdir($tmpDir, 0o755, true);

        try {
            foreach ($manifest['items'] ?? [] as $item) {
                $archivePath = $item['archive_path'] ?? null;
                $fileName = $item['file_name'] ?? null;

                if (! is_string($archivePath) || ! is_string($fileName)) {
                    $skipped++;

                    continue;
                }

                if (isset($existingSet[strtolower($fileName)])) {
                    $skipped++;

                    continue;
                }

                $contents = $zip->getFromName($archivePath);

                if ($contents === false) {
                    $skipped++;

                    continue;
                }

                $tmpFile = $tmpDir.'/'.basename($archivePath);
                file_put_contents($tmpFile, $contents);

                $container->addMedia($tmpFile)
                    ->usingFileName($fileName)
                    ->usingName((string) ($item['name'] ?? pathinfo($fileName, PATHINFO_FILENAME)))
                    ->toMediaCollection($item['collection_name'] ?? 'default');

                $existingSet[strtolower($fileName)] = true;
                $imported++;
            }
        } finally {
            $zip->close();
            $this->cleanupDir($tmpDir);
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function cleanupDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach ((array) glob($dir.'/*') as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }
}
