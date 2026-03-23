<x-filament-panels::page>
    @if($registryError)
        <div class="rounded-xl border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-700 dark:bg-yellow-900/20">
            <div class="flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Registry nicht erreichbar: {{ $registryErrorMessage }}
                </span>
            </div>
            <p class="mt-1 text-xs text-yellow-700 dark:text-yellow-300">Es werden nur lokal installierte Plugins angezeigt.</p>
        </div>
    @endif

    @if($availablePlugins->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-xl bg-white p-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <x-heroicon-o-puzzle-piece class="h-12 w-12 text-gray-400" />
            <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Keine Plugins verfügbar</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Die Plugin-Registry enthält aktuell keine Plugins.
            </p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($availablePlugins as $plugin)
                <div class="flex flex-col rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    {{-- Header --}}
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/30">
                                <x-heroicon-o-puzzle-piece class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $plugin['name'] }}
                                </h3>
                                <span class="text-xs text-gray-500 dark:text-gray-400">v{{ $plugin['version'] }}</span>
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        @if($plugin['is_active'])
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30">
                                Aktiv
                            </span>
                        @elseif($plugin['installed'])
                            <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                                Inaktiv
                            </span>
                        @endif
                    </div>

                    {{-- Description --}}
                    <p class="mt-3 flex-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $plugin['description'] }}
                    </p>

                    {{-- Shortcodes --}}
                    @if(!empty($plugin['shortcodes']))
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach($plugin['shortcodes'] as $shortcode)
                                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    [{{ $shortcode['tag'] }}]
                                </code>
                            @endforeach
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                        @if(!$plugin['installed'])
                            <button
                                wire:click="installPlugin('{{ $plugin['slug'] }}')"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 transition-colors"
                            >
                                <x-heroicon-m-arrow-down-tray class="h-3.5 w-3.5" />
                                Installieren
                            </button>
                        @elseif(!$plugin['is_active'])
                            <button
                                wire:click="activatePlugin('{{ $plugin['slug'] }}')"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-green-500 disabled:opacity-50 transition-colors"
                            >
                                <x-heroicon-m-play class="h-3.5 w-3.5" />
                                Aktivieren
                            </button>
                            <button
                                wire:click="uninstallPlugin('{{ $plugin['slug'] }}')"
                                wire:loading.attr="disabled"
                                wire:confirm="Plugin '{{ $plugin['name'] }}' wirklich deinstallieren?"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                            >
                                <x-heroicon-m-trash class="h-3.5 w-3.5" />
                                Entfernen
                            </button>
                        @else
                            <button
                                wire:click="deactivatePlugin('{{ $plugin['slug'] }}')"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                            >
                                <x-heroicon-m-pause class="h-3.5 w-3.5" />
                                Deaktivieren
                            </button>
                        @endif

                        @if($plugin['update_available'] ?? false)
                            <span class="ml-auto text-xs font-medium text-amber-600 dark:text-amber-400">
                                Update verfügbar
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
