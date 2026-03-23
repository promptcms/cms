<x-filament-panels::page>
    <div class="relative space-y-4"
         x-data="{ isDragging: false }"
         x-on:dragover.prevent="isDragging = true"
         x-on:dragleave.self="isDragging = false"
         x-on:drop.prevent="isDragging = false; $refs.dropInput.files = $event.dataTransfer.files; $refs.dropInput.dispatchEvent(new Event('change'))">

        {{-- Drag overlay --}}
        <div x-show="isDragging" x-cloak
             class="pointer-events-none absolute inset-0 z-50 flex items-center justify-center rounded-xl border-2 border-dashed border-primary-500 bg-primary-50/80 dark:bg-primary-900/30">
            <div class="text-center">
                <svg class="mx-auto h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                </svg>
                <p class="mt-2 text-sm font-medium text-primary-600 dark:text-primary-400">Dateien hier ablegen</p>
            </div>
        </div>

        {{-- Hidden input for drag & drop --}}
        <input type="file" x-ref="dropInput" wire:model="uploads" multiple accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.zip" class="hidden">

        {{-- Upload & Search Bar --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-500 transition-colors">
                    <x-heroicon-m-arrow-up-tray class="h-4 w-4" />
                    Hochladen
                    <input
                        type="file"
                        wire:model="uploads"
                        multiple
                        accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.zip"
                        class="hidden"
                    >
                </label>
                <div wire:loading wire:target="uploads" class="text-sm text-gray-500 dark:text-gray-400">
                    Wird hochgeladen...
                </div>
            </div>

            <div class="relative">
                <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Medien durchsuchen..."
                    class="rounded-lg border-gray-300 bg-white pl-9 pr-4 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                >
            </div>
        </div>

        <div class="flex gap-4">
            {{-- Media Grid --}}
            <div class="flex-1">
                @if($mediaItems->isEmpty())
                    <div class="flex flex-col items-center justify-center rounded-xl bg-white p-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <x-heroicon-o-photo class="h-12 w-12 text-gray-400" />
                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $search ? 'Keine Treffer' : 'Noch keine Medien' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $search ? 'Versuche einen anderen Suchbegriff.' : 'Lade Bilder und Dateien über den Button oben hoch.' }}
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        @foreach($mediaItems as $media)
                            <button
                                wire:click="selectMedia('{{ $media->id }}')"
                                class="group relative aspect-square overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-950/5 transition-all hover:ring-primary-500 dark:bg-gray-800 dark:ring-white/10
                                    {{ $selectedMediaId == $media->id ? 'ring-2 ring-primary-500' : '' }}"
                            >
                                @if(str_starts_with($media->mime_type, 'image/'))
                                    <img
                                        src="{{ $media->hasGeneratedConversion('small') ? $media->getUrl('small') : ($media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl()) }}"
                                        alt="{{ $media->name }}"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex h-full flex-col items-center justify-center p-3">
                                        <x-heroicon-o-document class="h-8 w-8 text-gray-400" />
                                        <span class="mt-2 truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION)) }}
                                        </span>
                                    </div>
                                @endif

                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent px-2 pb-2 pt-6 opacity-0 transition-opacity group-hover:opacity-100">
                                    <span class="block truncate text-xs text-white">{{ $media->file_name }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Detail Sidebar --}}
            @if($selectedMedia)
                <div class="hidden w-72 flex-shrink-0 space-y-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:block">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Details</h3>
                        <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-heroicon-m-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    {{-- Preview --}}
                    @if(str_starts_with($selectedMedia['mime_type'], 'image/'))
                        <img src="{{ $selectedMedia['url'] }}" alt="{{ $selectedMedia['name'] }}" class="w-full rounded-lg">
                    @else
                        <div class="flex aspect-square items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                            <x-heroicon-o-document class="h-12 w-12 text-gray-400" />
                        </div>
                    @endif

                    {{-- Info --}}
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Dateiname</dt>
                            <dd class="mt-0.5 break-all text-gray-900 dark:text-white">{{ $selectedMedia['file_name'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Typ</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ $selectedMedia['mime_type'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Größe</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ $selectedMedia['size'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Hochgeladen</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-white">{{ $selectedMedia['created_at'] }}</dd>
                        </div>
                    </dl>

                    {{-- URLs by size --}}
                    <div>
                        <dt class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">URLs</dt>
                        <div class="space-y-1.5">
                            @foreach($selectedMedia['urls'] ?? ['original' => $selectedMedia['url']] as $sizeName => $sizeUrl)
                                <div class="flex items-center gap-1">
                                    <span class="w-14 flex-shrink-0 text-[10px] font-medium uppercase text-gray-400 dark:text-gray-500">{{ $sizeName }}</span>
                                    <input
                                        type="text"
                                        value="{{ $sizeUrl }}"
                                        readonly
                                        class="min-w-0 flex-1 rounded border-gray-300 bg-gray-50 px-1.5 py-1 text-[11px] text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                        onclick="this.select()"
                                    >
                                    <button
                                        x-data
                                        x-on:click="navigator.clipboard.writeText('{{ $sizeUrl }}')"
                                        class="flex-shrink-0 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                                        title="Kopieren"
                                    >
                                        <x-heroicon-m-clipboard class="h-3 w-3" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 border-t border-gray-100 pt-3 dark:border-gray-800">
                        <a href="{{ $selectedMedia['url'] }}" target="_blank"
                           class="inline-flex flex-1 items-center justify-center gap-1 rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                            <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                            Öffnen
                        </a>
                        <button
                            wire:click="deleteMedia('{{ $selectedMedia['id'] }}')"
                            wire:confirm="Datei '{{ $selectedMedia['file_name'] }}' wirklich löschen?"
                            class="inline-flex items-center justify-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                        >
                            <x-heroicon-m-trash class="h-3.5 w-3.5" />
                            Löschen
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div> {{-- /x-data wrapper --}}
</x-filament-panels::page>
