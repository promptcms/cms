<?php

namespace App\Filament\Pages;

use App\Models\CmsSnapshot;
use App\Models\NodeRevision;
use App\Services\CmsSnapshotService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CmsVersions extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Versionen';

    protected static ?string $title = 'Versionen & Rollback';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.cms-versions';

    public string $activeTab = 'snapshots';

    /** @var Collection<int, CmsSnapshot> */
    public Collection $snapshots;

    /** @var Collection<int, array<string, mixed>> */
    public Collection $nodeRevisions;

    public string $snapshotLabel = '';

    public string $snapshotDescription = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->snapshots = CmsSnapshot::latest()->limit(20)->get();

        $this->nodeRevisions = NodeRevision::with('node')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (NodeRevision $r) => [
                'id' => $r->id,
                'node_id' => $r->node_id,
                'node_title' => $r->node?->title ?? '(gelöscht)',
                'node_slug' => $r->node?->slug ?? '—',
                'node_type' => $r->node?->type?->value ?? '—',
                'prompt' => $r->prompt,
                'created_at' => $r->created_at,
            ]);
    }

    public function createSnapshot(): void
    {
        if (empty($this->snapshotLabel)) {
            Notification::make()
                ->title('Bitte einen Namen für den Snapshot eingeben')
                ->warning()
                ->send();

            return;
        }

        $user = auth()->user();

        app(CmsSnapshotService::class)->createSnapshot(
            label: $this->snapshotLabel,
            description: $this->snapshotDescription ?: null,
            createdBy: $user?->email ?? 'admin',
        );

        $this->snapshotLabel = '';
        $this->snapshotDescription = '';
        $this->loadData();

        Notification::make()
            ->title('Snapshot erstellt')
            ->success()
            ->send();
    }

    public function restoreSnapshot(string $snapshotId): void
    {
        $snapshot = CmsSnapshot::find($snapshotId);

        if (! $snapshot) {
            Notification::make()->title('Snapshot nicht gefunden')->danger()->send();

            return;
        }

        // Create a backup snapshot before restoring
        app(CmsSnapshotService::class)->createSnapshot(
            label: 'Auto-Backup vor Rollback',
            description: 'Automatisch erstellt vor Wiederherstellung von: '.$snapshot->label,
            createdBy: 'system',
        );

        app(CmsSnapshotService::class)->restoreSnapshot($snapshot);
        $this->loadData();

        Notification::make()
            ->title("Snapshot '{$snapshot->label}' wiederhergestellt")
            ->body('Ein Auto-Backup wurde vorher erstellt.')
            ->success()
            ->send();
    }

    public function restoreNodeRevision(string $revisionId): void
    {
        $revision = NodeRevision::with('node')->find($revisionId);

        if (! $revision || ! $revision->node) {
            Notification::make()->title('Revision oder Node nicht gefunden')->danger()->send();

            return;
        }

        $revision->node->restoreRevision($revision);
        $this->loadData();

        Notification::make()
            ->title("'{$revision->node->title}' zurückgesetzt")
            ->body('Revision vom '.($revision->created_at?->format('d.m.Y H:i') ?? '—'))
            ->success()
            ->send();
    }

    public function deleteSnapshot(string $snapshotId): void
    {
        CmsSnapshot::destroy($snapshotId);
        $this->loadData();

        Notification::make()->title('Snapshot gelöscht')->success()->send();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
