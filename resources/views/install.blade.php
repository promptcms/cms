<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PromptCMS — Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">PromptCMS</h1>
            <p class="mt-2 text-slate-400">Installation &amp; Ersteinrichtung</p>
        </div>

        {{-- System Checks --}}
        <div class="bg-slate-800/50 backdrop-blur rounded-2xl border border-slate-700/50 p-6 mb-6">
            <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider mb-4">Systemanforderungen</h2>
            <div class="space-y-2">
                @php $allOk = true; @endphp
                @foreach($checks as $check)
                    @if(!$check['ok']) @php $allOk = false; @endphp @endif
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-300">{{ $check['label'] }}</span>
                        <span class="flex items-center gap-1.5 {{ $check['ok'] ? 'text-emerald-400' : 'text-red-400' }}">
                            @if($check['ok'])
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            @endif
                            {{ $check['detail'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Install Form --}}
        <form method="POST" action="/install" class="bg-slate-800/50 backdrop-blur rounded-2xl border border-slate-700/50 p-6 space-y-5">
            @csrf

            @if($errors->any())
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="text-red-400 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Administrator --}}
            <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Administrator-Konto</h2>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-400 mb-1.5">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                    class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-400 mb-1.5">E-Mail</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
                <p class="mt-1 text-xs text-slate-500">Wird auch als Kontakt-E-Mail der Website verwendet.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-400 mb-1.5">Passwort</label>
                    <input type="password" name="password" id="password" required minlength="8"
                        class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-400 mb-1.5">Wiederholen</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                        class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
                </div>
            </div>

            <hr class="border-slate-700/50">

            {{-- Website --}}
            <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Website</h2>

            <div>
                <label for="site_name" class="block text-sm font-medium text-slate-400 mb-1.5">Name der Website</label>
                <input type="text" name="site_name" id="site_name" value="{{ old('site_name') }}" required placeholder="z.B. Meine Firma GmbH"
                    class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
            </div>

            <div>
                <label for="site_title" class="block text-sm font-medium text-slate-400 mb-1.5">Seitentitel <span class="text-slate-500">(optional)</span></label>
                <input type="text" name="site_title" id="site_title" value="{{ old('site_title') }}" placeholder="Erscheint im Browser-Tab, z.B. Firma | Ihr Partner für X"
                    class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-400 mb-3">Design-Preset</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach(app(\App\Services\PresetService::class)->all() as $preset)
                        <label class="cursor-pointer">
                            <input type="radio" name="preset" value="{{ $preset['slug'] }}" class="peer hidden"
                                {{ old('preset', 'elegant') === $preset['slug'] ? 'checked' : '' }}>
                            <div class="rounded-xl border border-slate-600/50 bg-slate-900/50 p-3 text-center transition-all
                                        peer-checked:border-indigo-500 peer-checked:bg-indigo-500/10 peer-checked:ring-1 peer-checked:ring-indigo-500">
                                <div class="flex justify-center gap-1 mb-2">
                                    @foreach($preset['preview_colors'] ?? [] as $color)
                                        <span class="h-4 w-4 rounded-full" style="background: {{ $color }}"></span>
                                    @endforeach
                                </div>
                                <span class="text-sm font-medium text-white">{{ $preset['name'] }}</span>
                                <p class="mt-0.5 text-xs text-slate-500 leading-tight">{{ \Illuminate\Support\Str::limit($preset['description'] ?? '', 60) }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <hr class="border-slate-700/50">

            {{-- API Key --}}
            <h2 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">KI-Anbindung</h2>

            <div>
                <label for="openai_api_key" class="block text-sm font-medium text-slate-400 mb-1.5">OpenAI API-Key</label>
                <input type="password" name="openai_api_key" id="openai_api_key" value="{{ old('openai_api_key') }}" required
                    placeholder="sk-..."
                    class="w-full rounded-xl bg-slate-900/50 border border-slate-600/50 px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition font-mono text-sm">
                <p class="mt-1 text-xs text-slate-500">
                    Wird bei der Installation validiert. Du findest deinen Key unter
                    <a href="https://platform.openai.com/api-keys" target="_blank" class="text-indigo-400 hover:text-indigo-300 underline">platform.openai.com/api-keys</a>
                </p>
            </div>

            <button type="submit" @if(!$allOk) disabled @endif
                class="w-full rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 hover:bg-indigo-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                PromptCMS installieren
            </button>
        </form>

        <p class="text-center text-slate-500 text-xs mt-6">
            PromptCMS &mdash; What you prompt is what you get.
        </p>
    </div>
</body>
</html>
