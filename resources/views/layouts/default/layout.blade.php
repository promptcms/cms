@php
    $layoutHeader = $settings['layout_header'] ?? null;
    $layoutFooter = $settings['layout_footer'] ?? null;
    $bodyClass = $settings['layout_body_class'] ?? 'min-h-screen flex flex-col bg-white text-gray-900 antialiased';
    $bodyStyle = $settings['layout_body_style'] ?? '';
    $headCss = $settings['layout_head_css'] ?? '';
    $headJs = $settings['layout_head_js'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ config('cms.default_locale', 'de') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $settings['site_name'] ?? 'PromptCMS')</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    @if(!empty($settings['favicon_url']))
        <link rel="icon" href="{{ $settings['favicon_url'] }}">
        <link rel="apple-touch-icon" href="{{ $settings['favicon_url'] }}">
    @else
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @endif
    @if(!empty($settings['google_fonts_url']))
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="{{ $settings['google_fonts_url'] }}">
    @endif
    @if(file_exists(public_path('css/cms.css')) && filesize(public_path('css/cms.css')) > 100)
        {{-- Compiled Tailwind CSS (includes resolved @apply from design system) --}}
        <link rel="stylesheet" href="{{ asset('css/cms.css') }}?v={{ \App\Models\Setting::get('css_version', '0') }}">
        @if($headCss)
            {{-- Pure CSS rules only (no @apply — those are compiled into cms.css) --}}
            <style>{!! preg_replace('/[^{}\/\n]+\{[^}]*@apply[^}]*\}/s', '', $headCss) !!}</style>
        @endif
    @else
        {{-- Fallback: Tailwind v4 CDN when no compiled CSS available (no Node.js on host) --}}
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
        @if($headCss)
            {{-- Full head_css including @apply — the CDN processes it in-browser --}}
            <style type="text/tailwindcss">{!! $headCss !!}</style>
        @endif
    @endif
    @vite(['resources/js/app.js'])
</head>
<body class="{{ $bodyClass }}" @if($bodyStyle) style="{{ $bodyStyle }}" @endif>
    @if($layoutHeader)
        {!! $layoutHeader !!}
    @else
        @include('layouts.default.header')
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    @if($layoutFooter)
        {!! $layoutFooter !!}
    @else
        @include('layouts.default.footer')
    @endif

    @if($headJs)
        <script>{!! $headJs !!}</script>
    @endif
</body>
</html>
