@props([
    'modules' => null,
    'solutions' => null,
])

@php
    $modules ??= collect();
    $solutions ??= collect();

    // Fill levels for the decorative progress bars in the figure.
    $progressWidths = [90, 75, 85, 70];
@endphp

{{--
    Template "speciality" row: copy on one side with a start-aligned title
    bar, and on the other a figure resting on scattered circles (the About
    page's image treatment). The figure is a small product vignette — the
    connected modules, each one filled — rather than a stock picture.
--}}
<section class="site-section">
    <div class="site-container">
        <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
            <div class="relative isolate order-last p-6 sm:p-[60px] lg:order-first">
                <x-marketing.circles set="figure" />

                <div class="site-card relative p-6 sm:p-7" aria-hidden="true">
                    <ul class="space-y-4">
                        @foreach ($modules as $index => $module)
                            <li class="flex items-center gap-4 rounded-md bg-site-cream p-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white font-semibold text-site-primary-ink">{{ $index + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-site-dark">{{ $module->title }}</p>
                                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-site-border-soft">
                                        <div class="h-full rounded-full bg-site-primary" style="width: {{ $progressWidths[$index] ?? 70 }}%"></div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div>
                <x-marketing.section-heading
                    :eyebrow="$settings['solutions_badge_text'] ?? 'الحل'"
                    :title="$settings['solutions_title'] ?? ''"
                    :subtitle="$settings['solutions_sub_title'] ?? ''"
                    align="start"
                />

                <ul class="mt-8 space-y-4">
                    @foreach ($solutions as $solution)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-site-border-soft text-site-primary">
                                @if ($solution->icon)
                                    <x-ui.icon :name="$solution->icon" class="h-4 w-4" />
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                @endif
                            </span>
                            <span>{{ $solution->title }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($settings['solutions_btn_text'] ?? null)
                    <a href="{{ $settings['solutions_btn_link'] ?? '#' }}" class="site-btn site-btn-primary mt-10">
                        {{ $settings['solutions_btn_text'] }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
