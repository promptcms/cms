<?php

namespace App\Filament\Pages;

use App\Models\MediaContainer;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Medien';

    protected static ?string $title = 'Medienbibliothek';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.media-library';

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var Collection<int, Media> */
    public Collection $mediaItems;

    public ?string $selectedMediaId = null;

    public ?array $selectedMedia = null;

    public string $search = '';

    public function mount(): void
    {
        $this->loadMedia();
    }

    public function loadMedia(): void
    {
        $query = Media::query()
            ->where('model_type', MediaContainer::class)
            ->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('file_name', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            });
        }

        $this->mediaItems = $query->limit(100)->get();
    }

    public function updatedSearch(): void
    {
        $this->loadMedia();
    }

    public function updatedUploads(): void
    {
        $container = MediaContainer::firstOrCreate(['name' => 'global']);

        foreach ($this->uploads as $file) {
            $container->addMedia($file->getRealPath())
                ->usingFileName($file->getClientOriginalName())
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('default');
        }

        $this->uploads = [];
        $this->loadMedia();

        Notification::make()
            ->title('Dateien hochgeladen')
            ->success()
            ->send();
    }

    public function selectMedia(string $mediaId): void
    {
        $media = Media::find($mediaId);

        if (! $media) {
            return;
        }

        $this->selectedMediaId = $mediaId;
        $urls = ['original' => $media->getUrl()];

        foreach (['thumb', 'small', 'medium', 'large'] as $conversion) {
            if ($media->hasGeneratedConversion($conversion)) {
                $urls[$conversion] = $media->getUrl($conversion);
            }
        }

        $this->selectedMedia = [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->human_readable_size,
            'url' => $media->getUrl(),
            'urls' => $urls,
            'thumb_url' => $urls['thumb'] ?? $media->getUrl(),
            'created_at' => $media->created_at->format('d.m.Y H:i'),
        ];
    }

    public function closeDetail(): void
    {
        $this->selectedMediaId = null;
        $this->selectedMedia = null;
    }

    public function deleteMedia(string $mediaId): void
    {
        $media = Media::find($mediaId);

        if ($media) {
            $media->delete();
        }

        $this->closeDetail();
        $this->loadMedia();

        Notification::make()
            ->title('Datei gelöscht')
            ->success()
            ->send();
    }

    public function copyUrl(string $url): void
    {
        $this->dispatch('copy-to-clipboard', url: $url);
    }
}
