@php
    $footerConfig = $settings['footer_config'] ?? [];
    $copyright = $footerConfig['copyright'] ?? '© ' . date('Y') . ' ' . ($settings['site_name'] ?? 'PromptCMS');
    $bgClass = $footerConfig['bg_class'] ?? 'bg-gray-900 text-gray-300';
    $style = $footerConfig['style'] ?? '';
@endphp

<footer class="{{ $bgClass }}" @if($style) style="{{ $style }}" @endif>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if(!empty($footerConfig['columns']))
            <div class="grid grid-cols-2 gap-8 md:grid-cols-{{ count($footerConfig['columns']) }}">
                @foreach($footerConfig['columns'] as $column)
                    <div>
                        <h3 class="text-sm font-semibold text-white">{{ $column['title'] ?? '' }}</h3>
                        @if(!empty($column['links']))
                            <ul class="mt-4 space-y-2">
                                @foreach($column['links'] as $link)
                                    <li>
                                        <a href="{{ $link['url'] ?? '#' }}" class="text-sm text-gray-400 hover:text-white transition-colors">
                                            {{ $link['label'] ?? '' }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if(isset($footerMenu) && $footerMenu?->menuItems->count())
            <div class="mt-8 flex flex-wrap gap-6 border-t border-gray-800 pt-8">
                @foreach($footerMenu->menuItems as $item)
                    <a href="{{ $item->resolveUrl() }}"
                       target="{{ $item->target }}"
                       class="text-sm text-gray-400 hover:text-white transition-colors">
                        {{ $item->label }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-8 border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
            {{ $copyright }}
        </div>
    </div>
</footer>
