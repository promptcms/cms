<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
        <button
            wire:click="setActiveTab('snapshots')"
            class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors
                {{ $activeTab === 'snapshots'
                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
        >
            Snapshots (Komplette Sicherungen)
        </button>
        <button
            wire:click="setActiveTab('revisions')"
            class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors
                {{ $activeTab === 'revisions'
                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-white'
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
        >
            Einzelne Revisionen
        </button>
    </div>

    {{-- Snapshots Tab --}}
    @if($activeTab === 'snapshots')
        {{-- Create Snapshot --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Neuen Snapshot erstellen</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Speichert den kompletten aktuellen Zustand aller Seiten, Menüs und Einstellungen.
            </p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <div class="flex-1">
                    <input
                        type="text"
                        wire:model="snapshotLabel"
                        placeholder="Name, z.B. 'Vor Relaunch' oder 'v1.0'"
                        class="fi-input block w-full rounded-lg border-none bg-white py-1.5 pe-3 ps-3 text-sm text-gray-950 shadow-sm outline-none ring-1 ring-gray-950/10 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500 dark:focus:ring-primary-500"
                    >
                </div>
                <div class="flex-1">
                    <input
                        type="text"
                        wire:model="snapshotDescription"
                        placeholder="Beschreibung (optional)"
                        class="fi-input block w-full rounded-lg border-none bg-white py-1.5 pe-3 ps-3 text-sm text-gray-950 shadow-sm outline-none ring-1 ring-gray-950/10 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500 dark:focus:ring-primary-500"
                    >
                </div>
                <button
                    wire:click="createSnapshot"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition-colors"
                >
                    <x-heroicon-m-camera class="h-4 w-4" />
                    Snapshot erstellen
                </button>
            </div>
        </div>

        {{-- Snapshot List --}}
        @if($snapshots->isEmpty())
            <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <x-heroicon-o-clock class="mx-auto h-10 w-10 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Noch keine Snapshots vorhanden.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($snapshots as $snapshot)
                    <div class="flex items-center justify-between rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $snapshot->label }}
                                </h4>
                                @if(str_starts_with($snapshot->label, 'Auto-Backup'))
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-400">
                                        Auto
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $snapshot->created_at->format('d.m.Y H:i') }}</span>
                                <span>von {{ $snapshot->created_by }}</span>
                                @if($snapshot->description)
                                    <span class="truncate">– {{ $snapshot->description }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="ml-4 flex items-center gap-2">
                            <button
                                wire:click="restoreSnapshot('{{ $snapshot->id }}')"
                                wire:loading.attr="disabled"
                                wire:confirm="Wirklich auf diesen Snapshot zurücksetzen? Der aktuelle Zustand wird vorher automatisch gesichert."
                                class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50 transition-colors"
                            >
                                <x-heroicon-m-arrow-uturn-left class="h-3.5 w-3.5" />
                                Wiederherstellen
                            </button>
                            <button
                                wire:click="deleteSnapshot('{{ $snapshot->id }}')"
                                wire:loading.attr="disabled"
                                wire:confirm="Snapshot wirklich löschen?"
                                class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400 transition-colors"
                            >
                                <x-heroicon-m-trash class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Revisions Tab --}}
    @if($activeTab === 'revisions')
        @if($nodeRevisions->isEmpty())
            <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <x-heroicon-o-document-text class="mx-auto h-10 w-10 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Noch keine Revisionen vorhanden.</p>
            </div>
        @else
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Seite</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Typ</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Aktion</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Zeitpunkt</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($nodeRevisions as $rev)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $rev['node_title'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">/{{ $rev['node_slug'] }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $rev['node_type'] === 'page'
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                            : 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' }}">
                                        {{ $rev['node_type'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                    {{ \Illuminate\Support\Str::limit($rev['prompt'] ?? '—', 60) }}
                                </td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $rev['created_at']?->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($rev['node_slug'] !== '—')
                                        <button
                                            wire:click="restoreNodeRevision('{{ $rev['id'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:confirm="Diese Revision für '{{ $rev['node_title'] }}' wiederherstellen?"
                                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20 transition-colors"
                                        >
                                            <x-heroicon-m-arrow-uturn-left class="h-3 w-3" />
                                            Rollback
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</x-filament-panels::page>
