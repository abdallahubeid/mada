@props([
    'problems' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Problem>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Problem> $problems */
    $problems ??= collect();

    /*
     * ─────────────────────────────────────────────────────────────────────
     * BENTO SPANS ARE DERIVED, NOT HARDCODED
     *
     * The old layout was `lg:grid-cols-4` with every card identical — four
     * equal tiles, one icon each, which is the single most template-looking
     * arrangement available and gave the reader no idea which problem mattered
     * most.
     *
     * The grid is now 6 columns on `lg` and each card claims a span by
     * POSITION, so the asymmetry survives the CMS: an editor adding a fifth
     * problem gets a sensible tile instead of breaking the composition.
     *
     *   index 0 → 4 cols (the lead: full micro-UI, largest type)
     *   index 1 → 2 cols (narrow companion)
     *   index 2 → 2 cols
     *   index 3 → 4 cols (second anchor, mirrors the first row inverted)
     *   4+      → 3 cols (balanced pairs for anything beyond the designed four)
     *
     * Only indices 0 and 3 are "featured" — they get the embedded preview and
     * the larger heading. Rationing that to two cards per section is what
     * keeps the emphasis meaningful rather than decorative.
     * ─────────────────────────────────────────────────────────────────────
     */
    $spans = [
        0 => 'lg:col-span-4',
        1 => 'lg:col-span-2',
        2 => 'lg:col-span-2',
        3 => 'lg:col-span-4',
    ];
@endphp

<section class="bg-mist-50 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['problems_badge_text'] ?? 'التحديات'"
            :title="$settings['problems_title'] ?? ''"
            :subtitle="$settings['problems_sub_title'] ?? ''"
        />

        <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
            @foreach ($problems as $i => $problem)
                @php
                    $span = $spans[$i] ?? 'lg:col-span-3';
                    $featured = in_array($i, [0, 3], true);
                @endphp

                {{--
                    `mada-surface` rather than a hand-rolled ring + inline
                    box-shadow. This card was the last one on the landing page
                    still inventing its own surface treatment, which is what made
                    the sections read as eight slightly different card systems
                    instead of one.
                --}}
                <article @class([
                    'mada-surface group relative flex flex-col overflow-hidden p-6 sm:p-7',
                    $span,
                ])>
                    {{-- A single soft bloom in the corner. Warm, low opacity, and only on the featured tiles so it reads as emphasis. --}}
                    @if ($featured)
                        <div class="pointer-events-none absolute -top-16 -end-16 h-40 w-40 rounded-full bg-critical-500/6 blur-2xl" aria-hidden="true"></div>
                    @endif

                    <div class="relative flex items-start gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-critical-50 text-critical-500 ring-1 ring-critical-500/10">
                            @if ($problem->icon_key)
                                <x-ui.icon :name="$problem->icon_key" class="h-5 w-5" />
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <h3 @class([
                                'font-display font-bold tracking-tight text-ink-900',
                                'text-xl' => $featured,
                                'text-lg' => ! $featured,
                            ])>{{ $problem->title }}</h3>
                        </div>
                    </div>

                    <p class="relative mt-4 text-base leading-[1.7] text-mist-600">{{ $problem->description }}</p>

                    @if ($featured)
                        {{--
                            The micro-UI sits at the BOTTOM of the featured
                            cards and `mt-auto` pins it there, so the two
                            featured tiles line their previews up with each
                            other regardless of how long their copy runs.
                        --}}
                        <x-marketing.problem-preview :key="$problem->icon_key" class="relative mt-auto border-t border-mist-200 pt-6" />
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
