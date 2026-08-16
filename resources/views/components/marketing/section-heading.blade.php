@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
    'align' => 'center',
])

@php
    /*
     * Section headings share the hero's `**...**` convention, so one CMS habit
     * covers both. The DECORATION differs on purpose: the hero gets the marker
     * swash, section headings get the curved underline. Two devices used
     * consistently reads as a system; the same device everywhere reads as a
     * template, and one device per section reads as neither.
     *
     * A title without the delimiter renders plain, so every existing CMS value
     * keeps working untouched.
     */
    $isCenter = $align === 'center';
    $titleParts = preg_split('/\*\*(.+?)\*\*/u', $title, -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp

<div class="{{ $isCenter ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl text-start' }}">
    @if ($eyebrow)
        {{--
            The eyebrow was a bordered plum pill. Odoo sets its section eyebrows
            as small caps-weight label text with no container at all — the extra
            chrome is what made these read as badges competing with the heading
            they introduce.
        --}}
        <span class="inline-block text-xs font-bold tracking-[0.12em] text-brand-500">
            {{ $eyebrow }}
        </span>
    @endif

    {{--
        The `h-1 w-16` plum bar that used to sit under every heading is gone.
        A fixed rule under every title is decoration that encodes nothing, and
        it appeared on all eleven landing sections identically.
    --}}
    <h2 class="mt-3 font-display text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">
        @foreach ($titleParts as $i => $part)
            @if ($i % 2 === 1)
                {{--
                    The BLUE double swash, not the hero's orange marker. Three
                    decorations split by role — orange marker and teal circle
                    for the hero headline, blue double underline for section
                    headings — is what keeps four accent hues on one page
                    reading as a deliberate set of pen marks rather than as
                    decoration applied at random.
                --}}
                <span class="mada-underline-double">{{ $part }}</span>
            @else
                {{ $part }}
            @endif
        @endforeach
    </h2>

    @if ($subtitle)
        {{--
            `font-medium`, not `font-normal`. At 18px on a light canvas the
            subtitle was sitting at the same visual weight as the card body
            copy below it and reading as a stray paragraph rather than as part
            of the heading block — the "lost on the screen" problem. Weight is
            what re-attaches it to the h2, not size.
        --}}
        <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed font-medium text-mist-500">{{ $subtitle }}</p>
    @endif
</div>
