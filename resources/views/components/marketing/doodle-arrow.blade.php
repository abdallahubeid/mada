@props([
    'variant' => 'curve',
    'mirror' => true,
])

{{--
    Hand-drawn directional arrow, in the register of Odoo's hero doodles.

    Two variants because the two placements need genuinely different shapes:
    `curve` sweeps from a headline down toward a CTA, `hook` is a short stub
    that tucks beside a badge. Both are drawn with `stroke-linecap="round"` and
    a deliberately uneven path so they read as pen strokes rather than as
    geometry.

    RTL: an arrow is the one decoration whose meaning depends on direction, so
    it is mirrored with `rtl:-scale-x-100` rather than left to the background
    engine. The stroke is symmetric enough that mirroring does not expose the
    drawing order.

    `:mirror="false"` turns that off, and is required whenever the arrow sits
    inside a container that is ITSELF placed with logical properties
    (`start-*`/`end-*`). That container already swaps sides under RTL, so an
    arrow that also flips itself gets mirrored twice and ends up pointing away
    from the thing it is meant to indicate — which is exactly what happened to
    the hero CTA annotation. Auto-mirror is right for an arrow whose position
    is fixed; explicit `false` is right for one that travels with its parent.

    Decorative only — `aria-hidden`, and it carries no text. Anything it points
    at must make sense without it.
--}}
@php
    $paths = [
        // Long sweep: starts flat, dips, then hooks up into the arrowhead.
        'curve' => [
            'box' => '0 0 120 64',
            'line' => 'M4 10c14 26 42 42 78 44',
            'head' => 'M64 46c8 4 14 6 18 8-6 3-11 7-15 13',
        ],
        // Short hook for tight spaces beside a badge or eyebrow.
        'hook' => [
            'box' => '0 0 72 40',
            'line' => 'M4 6c8 16 24 25 62 26',
            'head' => 'M50 24c7 3 12 5 16 8-5 2-9 5-12 10',
        ],
    ];

    $shape = $paths[$variant] ?? $paths['curve'];
@endphp

<svg
    {{ $attributes->class(['pointer-events-none select-none text-marker-500', 'rtl:-scale-x-100' => $mirror]) }}
    viewBox="{{ $shape['box'] }}"
    fill="none"
    stroke="currentColor"
    stroke-width="3.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
>
    <path d="{{ $shape['line'] }}" />
    <path d="{{ $shape['head'] }}" />
</svg>
