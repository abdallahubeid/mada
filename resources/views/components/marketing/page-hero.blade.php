@props([
    'eyebrow' => null,
    'title' => '',
    'subtitle' => null,
])

{{-- Compact page hero for interior marketing pages (Features, Solutions, Pricing, Security). --}}
<section class="relative overflow-hidden bg-ink-100 py-16 sm:py-20 dark:bg-ink-950">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 start-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-emerald-400/15 blur-3xl dark:bg-emerald-400/20"></div>
    </div>

    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        @if ($eyebrow)
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                {{ $eyebrow }}
            </span>
        @endif

        <h1 class="mt-4 font-display text-3xl font-bold leading-tight text-ink-900 sm:text-4xl lg:text-5xl dark:text-ink-50">
            {{ $title }}
        </h1>

        @if ($subtitle)
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-mist-600 sm:text-lg dark:text-mist-300">
                {{ $subtitle }}
            </p>
        @endif

        <div class="mx-auto mt-6 h-1 w-16 rounded-full bg-emerald-400"></div>
    </div>
</section>
