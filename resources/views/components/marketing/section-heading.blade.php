@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
    'align' => 'center',
])

@php
    $isCenter = $align === 'center';
@endphp

<div class="{{ $isCenter ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl text-start' }}">
    @if ($eyebrow)
        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
            {{ $eyebrow }}
        </span>
    @endif

    <h2 class="mt-4 font-display text-3xl font-bold text-ink-900 dark:text-ink-50 sm:text-4xl">{{ $title }}</h2>
    <div class="mt-4 h-1 w-16 rounded-full bg-emerald-400 {{ $isCenter ? 'mx-auto' : '' }}"></div>

    @if ($subtitle)
        <p class="mt-4 text-mist-500 dark:text-mist-400">{{ $subtitle }}</p>
    @endif
</div>
