@php
    $headerConfig = $settings['header_config'] ?? [];
    $logoText = $headerConfig['logo_text'] ?? $settings['site_name'] ?? 'PromptCMS';
    $bgClass = $headerConfig['bg_class'] ?? 'bg-white border-b border-gray-200';
    $textClass = $headerConfig['text_class'] ?? 'text-gray-900';
    $linkClass = $headerConfig['link_class'] ?? 'text-gray-700 hover:text-gray-900';
    $ctaBgClass = $headerConfig['cta_bg_class'] ?? 'bg-indigo-600 hover:bg-indigo-500 text-white';
    $style = $headerConfig['style'] ?? '';
@endphp

<header class="{{ $bgClass }}" @if($style) style="{{ $style }}" @endif>
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex-shrink-0">
                <a href="/" class="text-xl font-bold {{ $textClass }}">
                    {{ $logoText }}
                </a>
            </div>

            @if(isset($headerMenu) && $headerMenu?->menuItems->count())
                <div class="hidden md:flex md:items-center md:space-x-8">
                    @foreach($headerMenu->menuItems as $item)
                        <a href="{{ $item->resolveUrl() }}"
                           target="{{ $item->target }}"
                           class="text-sm font-medium {{ $linkClass }} transition-colors">
                            {{ $item->label }}
                        </a>
                    @endforeach
                </div>
            @endif

            @if(!empty($headerConfig['cta_text']))
                <div class="hidden md:block">
                    <a href="{{ $headerConfig['cta_url'] ?? '#' }}"
                       class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm transition-colors {{ $ctaBgClass }}">
                        {{ $headerConfig['cta_text'] }}
                    </a>
                </div>
            @endif
        </div>
    </nav>
</header>
