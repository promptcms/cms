<x-filament-panels::page>
    @if($registryError)
        <div class="rounded-xl border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-700 dark:bg-yellow-900/20">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    {{ $registryErrorMessage }}
                </span>
            </div>
            <p class="mt-1 text-xs text-yellow-700 dark:text-yellow-300">Es werden nur lokal verfügbare Themes angezeigt.</p>
        </div>
    @endif

    @if($themes->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl bg-white p-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <x-heroicon-o-paint-brush class="h-12 w-12 text-gray-400" />
            <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Keine Themes verfügbar</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Die Theme-Registry konnte nicht geladen werden.
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($themes as $theme)
                @php
                    $isActive = $activeTheme === $theme['slug'];
                @endphp
                <div class="flex flex-col rounded-xl bg-white shadow-sm ring-1 transition-all
                    {{ $isActive
                        ? 'ring-2 ring-primary-500 dark:ring-primary-400'
                        : 'ring-gray-950/5 dark:ring-white/10' }}
                    dark:bg-gray-900 overflow-hidden">

                    {{-- Color Preview Bar --}}
                    <div class="flex h-16">
                        @foreach($theme['preview_colors'] ?? [] as $color)
                            <div class="flex-1" style="background: {{ $color }}"></div>
                        @endforeach
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        {{-- Header --}}
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $theme['name'] }}
                                </h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400">v{{ $theme['version'] ?? '1.0' }}</span>
                            </div>

                            @if($isActive)
                                <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 dark:bg-primary-900/30 dark:text-primary-400 dark:ring-primary-500/30">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Aktiv
                                </span>
                            @endif
                        </div>

                        {{-- Description --}}
                        <p class="mt-3 flex-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $theme['description'] ?? '' }}
                        </p>

                        {{-- Action --}}
                        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                            @if($isActive)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 dark:text-primary-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Aktuell aktives Theme
                                </span>
                            @else
                                <button
                                    wire:click="activateTheme('{{ $theme['slug'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="activateTheme('{{ $theme['slug'] }}')"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition-colors"
                                >
                                    <span wire:loading.remove wire:target="activateTheme('{{ $theme['slug'] }}')">
                                        <x-heroicon-m-paint-brush class="h-3.5 w-3.5" />
                                    </span>
                                    <span wire:loading wire:target="activateTheme('{{ $theme['slug'] }}')">
                                        <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                    <span wire:loading.remove wire:target="activateTheme('{{ $theme['slug'] }}')">Aktivieren</span>
                                    <span wire:loading wire:target="activateTheme('{{ $theme['slug'] }}')">Wird geladen...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
