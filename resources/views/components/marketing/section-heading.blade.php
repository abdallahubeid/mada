@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
    'align' => 'center',
    'level' => 'h2',
])

@php
    /*
     * Andromeda section heading: a plain line of body text above the title,
     * the title itself with the short orange bar beneath it, and the
     * description 40px further down so it clears the bar.
     *
     * Section titles share the hero's `**phrase**` CMS convention; the marked
     * words get the orange double underline. A title without the delimiter
     * renders plain, so every existing CMS value keeps working.
     */
    $isCenter = $align === 'center';
    $titleParts = preg_split('/\*\*(.+?)\*\*/u', (string) $title, -1, PREG_SPLIT_DELIM_CAPTURE);
    $renderedTitle = collect($titleParts)
        ->map(fn (string $part, int $i): string => $i % 2 === 1
            ? '<span class="mada-underline-double">'.e($part).'</span>'
            : e($part))
        ->implode('');
    $tag = in_array($level, ['h1', 'h2', 'h3'], true) ? $level : 'h2';
@endphp

<div {{ $attributes->class([$isCenter ? 'mx-auto max-w-3xl text-center' : 'max-w-2xl text-start']) }}>
    @if ($eyebrow)
        <p>{{ $eyebrow }}</p>
    @endif

    <{{ $tag }} @class([
        'site-section-title site-h2',
        'site-section-title--start' => ! $isCenter,
        'mt-4' => $eyebrow,
    ])>{!! $renderedTitle !!}</{{ $tag }}>

    @if ($subtitle)
        <p @class(['mt-10', 'mx-auto max-w-2xl' => $isCenter])>{{ $subtitle }}</p>
    @endif
</div>
