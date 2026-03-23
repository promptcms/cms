@extends('layouts.cms')

@section('title')
    @if($page->slug === 'home')
        {{ $settings['site_name'] ?? 'PromptCMS' }} – {{ $settings['site_tagline'] ?? $page->title }}
    @else
        {{ $page->title }} – {{ $settings['site_name'] ?? 'PromptCMS' }}
    @endif
@endsection

@if($page->getMeta('meta_description'))
    @section('meta_description', $page->getMeta('meta_description'))
@endif

@section('content')
    <div>
        {!! $renderedContent ?? $page->getMeta('content') !!}
    </div>
@endsection
