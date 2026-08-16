@props([
    'hero' => null,
])

@php
    /*
     * Hero, built to Odoo's structure.
     *
     * ─────────────────────────────────────────────────────────────────────
     * THE HEADLINE IS SET IN A HANDWRITTEN FACE, NOT A SANS
     *
     * Odoo runs its entire hero headline in Caveat — a handwriting face — at
     * 88px, against an otherwise plain Inter page. That single choice is what
     * makes the page read as human rather than as a template, and it applies
     * to the WHOLE line, not to a highlighted fragment of it.
     *
     * Caveat has no Arabic glyphs, so the equivalent here is Marhey (loaded via
     * `--font-hand`): a rounded, informal Arabic display face that still sets
     * real Arabic letterforms and holds up at 700 weight. Cairo — a geometric
     * corporate sans — is explicitly NOT used for this headline.
     *
     * ─────────────────────────────────────────────────────────────────────
     * THREE DECORATION MARKERS, PARSED FROM THE CMS STRING
     *
     *   **phrase**   orange marker swash behind the words
     *   ((phrase))   teal hand-drawn circle around the words
     *   __phrase__   blue double swash underline
     *
     * All three are optional; a plain title renders plain, so every existing
     * CMS value keeps working. Splitting in one pass with alternation keeps
     * the fragments in source order, which matters in RTL — a per-marker
     * str_replace would reorder them.
     * ─────────────────────────────────────────────────────────────────────
     */
    $hero ??= [];
    $metrics = $hero['resolved_metrics'] ?? [];

    $rawTitle = $settings['hero_title'] ?? 'كل ما تحتاجه لإدارة فريقك، **في مكان واحد**';

    $titleParts = preg_split(
        '/(\*\*.+?\*\*|\(\(.+?\)\)|__.+?__)/u',
        $rawTitle,
        -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
    );

    /*
     * The headline is assembled here and echoed as ONE string rather than
     * looped over in the template.
     *
     * A `@foreach` emitting `{{ $part }}` puts the template's own newlines and
     * indentation between fragments, and HTML collapses each run to a real
     * space. With `((فريقك))،` in the source that rendered as «فريقك ،» — a
     * space wedged between the word and its comma, which in Arabic is a plain
     * typographic error rather than a subtle spacing issue.
     *
     * Concatenating with no separator keeps the punctuation attached to the
     * word it belongs to. Every fragment is escaped individually, so CMS text
     * is still treated as untrusted.
     */
    $renderedTitle = collect($titleParts)
        ->map(function (string $part): string {
            $classes = match (true) {
                str_starts_with($part, '**') => 'mada-marker sm:whitespace-nowrap',
                str_starts_with($part, '((') => 'mada-circle sm:whitespace-nowrap',
                str_starts_with($part, '__') => 'mada-underline-double sm:whitespace-nowrap',
                default => null,
            };

            if ($classes === null) {
                return e($part);
            }

            return '<span class="'.$classes.'">'.e(trim($part, '*()_')).'</span>';
        })
        ->implode('');
@endphp

{{--
    Bottom padding is deliberately small: the product-video section that
    follows pulls up into this one so the app preview reads as part of the hero
    fold rather than as the next section down.
--}}
<section class="relative overflow-hidden bg-mist-50 pt-14 pb-10 sm:pt-20 sm:pb-14">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-56 start-1/2 h-[34rem] w-[34rem] -translate-x-1/2 rounded-full bg-marker-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
        @if (! empty($settings['hero_badge_text']))
            <div class="flex items-center justify-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-mist-200 bg-white px-4 py-1.5 text-xs font-semibold text-mist-600 shadow-xs">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-success-500 opacity-75"></span>
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-success-500"></span>
                    </span>
                    {{ $settings['hero_badge_text'] }}
                </span>
            </div>
        @endif

        {{--
            `leading-[1.3]` — noticeably looser than a sans headline would take.
            Marhey has tall ascenders and deep descenders, and the circle and
            underline decorations both draw OUTSIDE the text box, so a tight
            line-height would have line two clipping line one's doodles.
        --}}
        <h1 class="mt-7 font-hand text-[2.6rem] leading-[1.3] font-bold tracking-normal text-ink-900 sm:text-6xl lg:text-[4.5rem]">{!! $renderedTitle !!}</h1>

        <p class="mx-auto mt-8 max-w-2xl text-lg leading-relaxed text-mist-600 sm:text-xl">
            {{ $settings['hero_description'] ?? '' }}
        </p>

        {{--
            CTA block. `relative` here is what anchors the arrow and the
            annotation: both are positioned against THIS container, not against
            the section, so they track the buttons at every breakpoint instead
            of drifting as the headline above them rewraps.
        --}}
        {{--
            `w-full` on mobile, `inline-flex` only from `sm`. As a bare
            `inline-flex` this wrapper is shrink-to-fit, so it sized itself to
            the button labels and the `w-full` on the buttons resolved to that
            same narrow width — full-bleed mobile CTAs that were not actually
            full bleed. It has to be block-level before its children can fill it.
        --}}
        <div class="relative mt-10 flex w-full flex-col items-center sm:inline-flex sm:w-auto">
            <div class="flex w-full flex-col items-center justify-center gap-3 sm:w-auto sm:flex-row sm:gap-4">
                <a
                    href="{{ $settings['hero_btn1_link'] ?? route('register') }}"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-8 py-4 text-base font-semibold text-white transition duration-150 ease-in-out hover:bg-brand-600 active:translate-y-px sm:w-auto"
                >
                    {{ $settings['hero_btn1_text'] ?? 'ابدأ الآن — مجاناً' }}
                </a>

                <a
                    href="{{ $settings['hero_btn2_link'] ?? route('marketing.contact') }}"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-mist-100 px-8 py-4 text-base font-semibold text-brand-600 transition duration-150 ease-in-out hover:bg-mist-200 active:translate-y-px sm:w-auto"
                >
                    {{ $settings['hero_btn2_text'] ?? 'تواصل مع مستشار' }}
                </a>
            </div>

            {{--
                Handwritten annotation + arrow, anchored to the CTA row above.

                Positioned with `start-full` so it sits past the row's inline
                start edge — which is the RIGHT in Arabic and the LEFT in
                English, correct in both without a `rtl:` override. The arrow
                itself is mirrored inside its own component.

                Hidden below `lg`: at narrow widths the buttons go full-width
                and stack, leaving no gutter for an annotation to live in
                without overlapping them.
            --}}
            <div class="pointer-events-none absolute top-1/2 start-full ms-4 hidden -translate-y-1/2 items-center gap-1 lg:flex" aria-hidden="true">
                {{--
                    `:mirror="false"` — the wrapper above is placed with
                    `start-full`, so it already swaps sides under RTL. Letting
                    the arrow flip itself as well double-mirrors it and points
                    the head away from the button.
                --}}
                <x-marketing.doodle-arrow variant="curve" :mirror="false" class="h-12 w-20 -rotate-6" />
                <span class="font-hand max-w-[9rem] text-start text-base leading-snug font-bold text-marker-700 -rotate-3">
                    مجاني بالكامل بدون بطاقة إلكترونية
                </span>
            </div>
        </div>

        {{-- Same reassurance, readable and non-decorative, for the widths where the annotation is hidden. --}}
        <p class="mt-6 text-base text-mist-500 lg:hidden">
            مجاني بالكامل بدون بطاقة إلكترونية
        </p>

        @if (! empty($metrics))
            <div class="mt-14 grid grid-cols-1 gap-8 sm:grid-cols-3">
                @foreach ($metrics as $metric)
                    <div class="text-center">
                        <p class="font-hand text-4xl font-bold text-ink-900 tabular">
                            <x-marketing.stat-counter
                                :value="$metric['value']"
                                :prefix="$metric['prefix'] ?? ''"
                                :suffix="$metric['suffix'] ?? ''"
                                :decimals="$metric['decimals'] ?? 0"
                            />
                        </p>
                        <p class="mt-1.5 text-base text-mist-600">{{ $metric['label'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
