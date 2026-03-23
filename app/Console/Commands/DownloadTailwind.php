<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('cms:download-tailwind')]
#[Description('Download the Tailwind CSS v4 standalone CLI binary for the current platform')]
class DownloadTailwind extends Command
{
    private const BASE_URL = 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download';

    public function handle(): int
    {
        $binary = $this->getBinaryName();

        if (! $binary) {
            $this->error('Unsupported platform: '.PHP_OS.' / '.php_uname('m'));

            return self::FAILURE;
        }

        $url = self::BASE_URL.'/'.$binary;
        $destination = storage_path('cms/tailwindcss');

        if (! is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }

        $this->info("Downloading {$binary}...");

        try {
            $response = Http::timeout(60)->withOptions(['sink' => $destination])->get($url);

            if (! $response->successful()) {
                $this->error("Download failed: HTTP {$response->status()}");

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Download failed: '.$e->getMessage());

            return self::FAILURE;
        }

        chmod($destination, 0755);

        $size = round(filesize($destination) / 1024 / 1024, 1);
        $this->info("Tailwind CLI downloaded: {$size} MB → storage/cms/tailwindcss");

        return self::SUCCESS;
    }

    private function getBinaryName(): ?string
    {
        $os = strtolower(PHP_OS_FAMILY);
        $arch = php_uname('m');

        $map = [
            'darwin' => [
                'arm64' => 'tailwindcss-macos-arm64',
                'x86_64' => 'tailwindcss-macos-x64',
            ],
            'linux' => [
                'aarch64' => 'tailwindcss-linux-arm64',
                'arm64' => 'tailwindcss-linux-arm64',
                'x86_64' => 'tailwindcss-linux-x64',
            ],
            'windows' => [
                'AMD64' => 'tailwindcss-windows-x64.exe',
                'x86_64' => 'tailwindcss-windows-x64.exe',
            ],
        ];

        return $map[$os][$arch] ?? null;
    }
}
