@props([
    'hero' => null,
])

@php
    /*
     * Home hero, built to the Andromeda `HomeBanner`: a cream panel with a
     * dashed wave, a dotted arc and scattered circles; a centred headline and
     * call to action; and the product footage framed like the template's app
     * screenshot, overlapping the panel's lower edge.
     *
     * The CMS headline keeps its three optional decoration markers, painted
     * in the template's palette (peach swash, orange ring, orange underline):
     *
     *   **phrase**   peach marker swash behind the words
     *   ((phrase))   hand-drawn ring around the words
     *   __phrase__   double underline
     *
     * The headline is assembled as ONE string rather than looped in the
     * template: a `@foreach` would put whitespace between fragments, wedging
     * a space between a word and its comma («فريقك ،»). Every fragment is
     * escaped individually, so CMS text stays untrusted.
     */
    $hero ??= [];
    $metrics = $hero['resolved_metrics'] ?? [];

    $rawTitle = $settings['hero_title'] ?? 'كل ما تحتاجه لإدارة فريقك، **في مكان واحد**';

    $title = collect(preg_split(
        '/(\*\*.+?\*\*|\(\(.+?\)\)|__.+?__)/u',
        (string) $rawTitle,
        -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
    ))
        ->map(function (string $part): string {
            $class = match (true) {
                str_starts_with($part, '**') => 'mada-marker',
                str_starts_with($part, '((') => 'mada-circle',
                str_starts_with($part, '__') => 'mada-underline-double',
                default => null,
            };

            return $class === null
                ? e($part)
                : '<span class="'.$class.' sm:whitespace-nowrap">'.e(trim($part, '*()_')).'</span>';
        })
        ->implode('');

    // Same two independent switches the product-video section honours.
    $hasVideo = \App\Models\Setting::isVideoSectionActive() && filled(\App\Models\Setting::videoSource());
@endphp

<section class="pb-16">
    <div class="site-container-xl">
        <div class="site-panel-host">
            {{-- With footage below the buttons the panel stops halfway down it, as in the template. --}}
            <x-marketing.panel
                variant="banner"
                circles="banner"
                @class(['h-[calc(50%+160px)]' => $hasVideo])
            />

            <div class="mx-auto max-w-5xl px-4 pt-10 pb-8 text-center sm:pt-14">
                @if (! empty($settings['hero_badge_text']))
                    <p class="mb-4">{{ $settings['hero_badge_text'] }}</p>
                @endif

                <h1 class="mx-auto max-w-4xl leading-[1.4]">{!! $title !!}</h1>

                @if (filled($settings['hero_description'] ?? null))
                    <p class="mx-auto mt-5 max-w-2xl text-base">{{ $settings['hero_description'] }}</p>
                @endif

                <div class="mt-7 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    <a href="{{ $settings['hero_btn1_link'] ?? route('register') }}" class="site-btn site-btn-primary">
                        {{ $settings['hero_btn1_text'] ?? 'ابدأ الآن — مجاناً' }}
                    </a>
                    <a href="{{ $settings['hero_btn2_link'] ?? route('marketing.contact') }}" class="site-btn site-btn-outline">
                        {{ $settings['hero_btn2_text'] ?? 'تواصل مع مستشار' }}
                    </a>
                </div>

                <p class="mt-4">مجاني بالكامل بدون بطاقة إلكترونية</p>

                @if (! empty($metrics))
                    <div class="mx-auto mt-8 grid max-w-2xl grid-cols-3 gap-3 sm:gap-4">
                        @foreach ($metrics as $metric)
                            <div class="site-card px-2 py-4 sm:px-4">
                                <p class="text-xl leading-tight font-semibold text-site-dark tabular-nums sm:text-2xl">
                                    <x-marketing.stat-counter
                                        :value="$metric['value']"
                                        :prefix="$metric['prefix'] ?? ''"
                                        :suffix="$metric['suffix'] ?? ''"
                                        :decimals="$metric['decimals'] ?? 0"
                                    />
                                </p>
                                <p class="mt-1.5 text-sm leading-snug">{{ $metric['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($hasVideo)
                <div class="mx-auto w-full max-w-[1170px] px-4">
                    <x-marketing.product-video />
                </div>
            @endif
        </div>
    </div>
</section>
