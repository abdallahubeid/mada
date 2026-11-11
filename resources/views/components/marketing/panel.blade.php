@props([
    'variant' => null,
    'circles' => 'section',
])

{{--
    The template's `.bg-theme`: a cream or peach panel with dashed waves and
    scattered circles, laid BEHIND a section's content. The parent must carry
    `site-panel-host` so the panel's negative z-index stays inside it.

    The default and `wave` variants grow in from 30% the first time they enter
    the viewport, as the template does; `banner` and `flat` are static.
--}}
@php
    $animates = in_array($variant, [null, 'wave', 'plain'], true);
@endphp

<div
    {{ $attributes->class([
        'site-panel',
        'site-panel--'.$variant => $variant !== null,
    ]) }}
    @if ($animates) x-data x-intersect.once="$el.classList.add('is-in')" @endif
    aria-hidden="true"
>
    @if ($circles)
        <x-marketing.circles :set="$circles" />
    @endif
</div>
