@php
    /** @var \App\Services\UpdateChecker $checker */
    $checker = app(\App\Services\UpdateChecker::class);

    if (! $checker->hasUpdate()) {
        return;
    }

    $latest = $checker->getLatestRelease();
@endphp

<div class="bg-primary-600 text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2 text-sm">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>
                Eine neue PromptCMS-Version ist verfügbar:
                <strong>{{ $latest['tag'] }}</strong>
                (installiert: {{ $checker->getCurrentVersion() }})
            </span>
        </div>
        <a
            href="{{ $latest['url'] }}"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-1 rounded-md bg-white/15 px-3 py-1 text-xs font-medium hover:bg-white/25 transition-colors"
        >
            Release ansehen
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </a>
    </div>
</div>
