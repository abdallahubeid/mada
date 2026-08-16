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

<section class="bg-mist-50 py-24 ">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border border-brand-600 bg-brand-500 px-6 py-16 text-center sm:px-16">
            {{--
                Warm blooms, not plum ones: the panel is now brand-500 itself,
                so a plum-on-plum glow is invisible. The marker orange is the
                one accent that reads against it.
            --}}
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-24 start-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-marker-500/20 blur-3xl"></div>
                <div class="absolute -bottom-24 end-0 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-2xl">
                <h2 class="font-display text-3xl font-medium text-white sm:text-4xl">{{ $title }}</h2>
                <p class="mt-4 text-lg text-brand-100">{{ $subtitle }}</p>

                {{--
                    Buttons INVERT on this block. The primary was `bg-brand-500
                    text-white` sitting on a `bg-brand-500` panel — the same
                    colour on the same colour, i.e. an invisible CTA. On a
                    saturated brand block the solid button is white.
                --}}
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                    <a href="{{ $primaryHref }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-white px-7 py-3.5 text-base font-semibold text-brand-600 transition duration-150 ease-in-out hover:bg-brand-50 active:translate-y-px sm:w-auto">
                        {{ $primaryLabel }}
                    </a>
                    <a href="{{ $secondaryHref }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/40 px-7 py-3.5 text-base font-semibold text-white transition duration-150 ease-in-out hover:border-white hover:bg-white/10 active:translate-y-px sm:w-auto">
                        {{ $secondaryLabel }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
