@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
])

{{-- Compact page hero for interior marketing pages (Features, Solutions, Pricing, Security). --}}
<section class="relative overflow-hidden bg-mist-50 py-24 sm:py-24">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 start-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-brand-500/15 blur-3xl dark:bg-brand-500/20"></div>
    </div>

    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        @if ($eyebrow)
            <span class="inline-flex items-center gap-2 rounded-md border border-brand-500/30 bg-brand-500/10 px-4 py-1.5 text-xs font-semibold text-brand-600 dark:text-brand-300">
                {{ $eyebrow }}
            </span>
        @endif

        <h1 class="mt-4 font-display text-3xl font-medium leading-tight text-ink-900 sm:text-4xl lg:text-5xl dark:text-ink-50">
            {{ $title }}
        </h1>

        @if ($subtitle)
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-mist-600 sm:text-lg dark:text-mist-300">
                {{ $subtitle }}
            </p>
        @endif

    </div>
</section>
