@props([
    'title' => null,
    'subtitle' => null,
    'primaryLabel' => null,
    'primaryHref' => null,
    'secondaryLabel' => null,
    'secondaryHref' => null,
])

@php
    $title ??= $settings['cta_title'] ?? '';
    $subtitle ??= $settings['cta_sub_title'] ?? '';
    $primaryLabel ??= $settings['cta_btn1_text'] ?? 'ابدأ التجربة المجانية';
    $primaryHref ??= $settings['cta_btn1_link'] ?? route('register');
    $secondaryLabel ??= $settings['cta_btn2_text'] ?? 'تواصل مع المبيعات';
    $secondaryHref ??= $settings['cta_btn2_link'] ?? '/contact';
@endphp

{{--
    Template `Cta`: the peach panel grows in behind a centred title (with the
    orange bar), a line of copy and the buttons, with the dashed wave running
    along its foot and a few circles at the edges.
--}}
<section class="pb-16">
    <div class="site-container-xl">
        <div class="site-panel-host site-section px-4 text-center">
            <x-marketing.panel variant="wave" circles="cta" />

            <div class="mx-auto max-w-2xl">
                <h2 class="site-section-title">{{ $title }}</h2>
                @if (filled($subtitle))
                    <p class="mt-10">{{ $subtitle }}</p>
                @endif
                <div class="mt-10 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    <a href="{{ $primaryHref }}" class="site-btn site-btn-primary">{{ $primaryLabel }}</a>
                    @if (filled($secondaryLabel))
                        <a href="{{ $secondaryHref }}" class="site-btn site-btn-outline">{{ $secondaryLabel }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
