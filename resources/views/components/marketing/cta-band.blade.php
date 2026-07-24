@props([
    'title' => 'جاهز لتحويل مؤسستك؟',
    'subtitle' => 'ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.',
    'primaryLabel' => 'ابدأ التجربة المجانية',
    'primaryHref' => null,
    'secondaryLabel' => 'تواصل مع المبيعات',
    'secondaryHref' => '/contact',
])

@php
    /* Final high-impact CTA (docs/MARKETING.md §4.13). */
    $primaryHref ??= route('register');
@endphp

<section class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border border-emerald-400/30 bg-ink-950 px-6 py-16 text-center sm:px-16">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-24 start-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-emerald-500/20 blur-3xl"></div>
                <div class="absolute -bottom-24 end-0 h-64 w-64 rounded-full bg-emerald-400/10 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-2xl">
                <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">{{ $title }}</h2>
                <p class="mt-4 text-lg text-mist-300">{{ $subtitle }}</p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ $primaryHref }}" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-6 py-3 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]">
                        {{ $primaryLabel }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                    </a>
                    <a href="{{ $secondaryHref }}" class="inline-flex items-center gap-2 rounded-full border border-ink-700 px-6 py-3 text-sm font-semibold text-mist-200 transition duration-200 ease-in-out hover:border-emerald-400 hover:text-emerald-400 active:scale-[0.98]">
                        {{ $secondaryLabel }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
