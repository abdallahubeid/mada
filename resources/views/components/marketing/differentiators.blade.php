@props([
    'features' => null,
])

@php
    $features ??= collect();

    /*
     * Each reason is paired with a small product vignette that shows the
     * claim rather than restating it (audit trail, RTL form, export, speed).
     * Keyed by the CMS icon so an editor's choice of icon picks the picture.
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

{{--
    Template "speciality" rows: copy and figure side by side, swapping sides
    on every row, each title carrying the start-aligned orange bar. The
    figure rests on scattered circles as the template's images do.
--}}
<section class="site-section">
    <div class="site-container">
        <x-marketing.section-heading
            :eyebrow="$settings['why_us_badge_text'] ?? 'لماذا مدى'"
            :title="$settings['why_us_title'] ?? ''"
            :subtitle="$settings['why_us_sub_title'] ?? ''"
        />

        <div class="mt-10 space-y-6 lg:space-y-0">
            @foreach ($features as $i => $feature)
                @php
                    $flip = $i % 2 === 1;
                @endphp

                <article class="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
                    <div @class(['relative isolate p-6 sm:p-[60px]', 'lg:order-2' => ! $flip])>
                        <x-marketing.circles set="figure" />
                        <x-marketing.feature-anchor :variant="$anchors[$feature->icon] ?? null" class="relative" />
                    </div>

                    <div @class(['pb-6 lg:pb-0', 'lg:order-1' => ! $flip])>
                        <span class="flex h-12 w-12 items-center justify-center rounded-md border border-site-peach bg-site-cream text-site-primary">
                            @if ($feature->icon)
                                <x-ui.icon :name="$feature->icon" class="h-6 w-6" />
                            @endif
                        </span>
                        <h3 class="site-section-title site-section-title--start site-h3 mt-6">{{ $feature->title }}</h3>
                        <p class="mt-10 max-w-xl">{{ $feature->description }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
