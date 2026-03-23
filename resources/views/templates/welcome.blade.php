<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>promptCMS – What you prompt is what you get</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif
</head>
<body style="margin:0;padding:0">
    <div style="min-height:100dvh" class="relative isolate flex items-center justify-center overflow-hidden bg-gray-950">
        {{-- Gradient background --}}
        <div class="absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-purple-900/40 via-gray-950 to-gray-950"></div>
            <div class="absolute left-1/2 top-0 -z-10 -translate-x-1/2 blur-3xl" aria-hidden="true">
                <div class="aspect-[1155/678] w-[72rem] bg-gradient-to-tr from-violet-600 to-fuchsia-500 opacity-20"></div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 -z-10 blur-3xl" aria-hidden="true">
                <div class="mx-auto aspect-[1155/678] w-[60rem] bg-gradient-to-tr from-fuchsia-500 to-pink-500 opacity-10"></div>
            </div>
        </div>

        {{-- Grid pattern overlay --}}
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,_rgba(255,255,255,0.03)_1px,_transparent_1px),linear-gradient(to_bottom,_rgba(255,255,255,0.03)_1px,_transparent_1px)] bg-[size:4rem_4rem]"></div>

        <div class="mx-auto max-w-5xl px-6 text-center">
            {{-- Logo --}}
            <div class="mb-8 flex items-center justify-center gap-3">
                <img src="{{ asset('img/logo.png') }}" alt="PromptCMS" class="h-14 w-auto">
                <span class="text-2xl font-bold text-white">promptCMS</span>
            </div>

            {{-- Hero heading --}}
            <h1 class="text-5xl font-extrabold tracking-tight text-white sm:text-7xl lg:text-8xl">
                What you
                <span class="bg-gradient-to-r from-violet-400 via-purple-400 to-fuchsia-400 bg-clip-text text-transparent">prompt</span>
                is what you get
            </h1>

            <p class="mx-auto mt-8 max-w-2xl text-lg leading-relaxed text-gray-400 sm:text-xl">
                Beschreibe deine Website in natürlicher Sprache. Die KI erstellt Seiten, Menüs und Inhalte – in Sekunden.
            </p>

            {{-- CTA buttons --}}
            <div class="mt-12 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="/admin"
                   class="group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 via-purple-500 to-fuchsia-500 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-600/25 transition-all hover:shadow-purple-500/40 hover:-translate-y-0.5">
                    Jetzt starten
                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <a href="/admin/ai-chat"
                   class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-4 text-sm font-semibold text-gray-300 backdrop-blur-sm transition-all hover:bg-white/10 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                    </svg>
                    KI-Chat öffnen
                </a>
            </div>

            {{-- Feature pills --}}
            <div class="mt-20 flex flex-wrap items-center justify-center gap-3">
                @foreach(['Seiten erstellen', 'Menüs konfigurieren', 'SEO optimieren', 'Header & Footer', 'Mehrsprachig'] as $feature)
                    <span class="rounded-full border border-white/5 bg-white/5 px-4 py-2 text-xs font-medium text-gray-500">
                        {{ $feature }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>
