@props([
    'features' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Feature>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\Feature> $features */
    $features ??= collect();

    /*
     * ─────────────────────────────────────────────────────────────────────
     * ALTERNATING SPLIT ROWS, NOT A FOUR-ACROSS GRID
     *
     * This section makes four ARGUMENTS about engineering decisions. A row of
     * four equal tiles gives each of them two lines of space and no visual
     * weight, so the reader skims all four and retains none — the format was
     * fighting the content.
     *
     * Each claim now gets a full row: copy on one side, a visual anchor on the
     * other, sides swapping every row so the eye zig-zags down the page
     * instead of scanning a wall.
     *
     * The anchor is chosen by the feature's own stored icon, so it needs no new
     * column and an unknown icon simply falls back to the plain glyph.
     * ─────────────────────────────────────────────────────────────────────
     */
    $anchors = [
        'ph:shield-check-bold' => 'audit',
        'ph:translate-bold' => 'rtl',
        'ph:arrow-down-bold' => 'export',
        'ph:rocket-launch-bold' => 'speed',
        'ph:file-text-bold' => 'audit',
        'ph:chat-dots-bold' => 'rtl',
    ];
@endphp

<section class="bg-mist-50 py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :eyebrow="$settings['why_us_badge_text'] ?? 'لماذا مدى'"
            :title="$settings['why_us_title'] ?? ''"
            :subtitle="$settings['why_us_sub_title'] ?? ''"
        />

        <div class="mt-16 space-y-5">
            @foreach ($features as $i => $feature)
                @php
                    $anchor = $anchors[$feature->icon] ?? null;
                    $flip = $i % 2 === 1;
                @endphp

                <article @class([
                    'mada-surface grid items-center gap-8 p-7 sm:p-9 lg:grid-cols-2 lg:gap-12',
                    'mada-surface-feature' => $i === 0,
                ])>
                    {{--
                        `lg:order-2` on odd rows flips the visual to the other
                        side. Order, not direction — using `order` keeps this
                        working identically in RTL and LTR, where a
                        float/`flex-row-reverse` approach would double-flip.
                    --}}
                    <div @class(['min-w-0', 'lg:order-2' => $flip])>
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500/8 text-brand-600 ring-1 ring-brand-500/10">
                                @if ($feature->icon)
                                    <x-ui.icon :name="$feature->icon" class="h-5 w-5" />
                                @endif
                            </span>
                            <span class="font-display text-sm font-bold tabular text-mist-400">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        </div>

                        <h3 class="mt-5 font-display text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">{{ $feature->title }}</h3>
                        <p class="mt-4 max-w-xl text-base leading-[1.75] text-mist-600">{{ $feature->description }}</p>
                    </div>

                    <div @class(['min-w-0', 'lg:order-1' => $flip])>
                        <x-marketing.feature-anchor :variant="$anchor" />
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
