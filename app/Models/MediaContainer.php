<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaContainer extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'media_containers';

    protected $fillable = ['name'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->performOnCollections('default')
            ->nonQueued();

        $this->addMediaConversion('small')
            ->width(480)
            ->height(480)
            ->quality(80)
            ->performOnCollections('default')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(960)
            ->height(960)
            ->quality(80)
            ->performOnCollections('default')
            ->nonOptimized()
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1920)
            ->height(1920)
            ->quality(80)
            ->performOnCollections('default')
            ->nonOptimized()
            ->nonQueued();
    }

    /**
     * Only generate conversions for actual images.
     */
    public function shouldGenerateConversions(Media $media): bool
    {
        return str_starts_with($media->mime_type, 'image/')
            && ! str_contains($media->mime_type, 'svg');
    }
}
