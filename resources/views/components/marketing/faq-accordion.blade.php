@props([
    'limit' => null,
    'items' => null,
    'title' => 'الأسئلة الشائعة',
    'subtitle' => 'إجابات سريعة عن أكثر ما يسأل عنه عملاؤنا.',
    'framed' => true,
])

@php
    /* FAQ accordion (docs/MARKETING.md §4.12) — single source: config/faq.php.
       `limit` powers the landing preview (top N); /faq passes the full set.
       `framed=false` renders only the accordion list (for category sections on /faq). */
    $items ??= config('faq.items', []);
    if ($limit) {
        $items = array_slice($items, 0, $limit);
    }
@endphp

@if ($framed)
<section class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        @if ($title !== '' && $title !== null)
            <x-marketing.section-heading :title="$title" :subtitle="$subtitle" />
        @endif

        <div @class(['mt-12' => $title !== '' && $title !== null, 'space-y-3' => true]) x-data="{ open: 0 }">
            @include('components.marketing.partials.faq-items', ['items' => $items])
        </div>

        @if ($limit)
            <div class="mt-8 text-center">
                <a href="{{ route('marketing.faq') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 transition hover:gap-3 dark:text-emerald-400">
                    عرض جميع الأسئلة
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                </a>
            </div>
        @endif
    </div>
</section>
@else
<div class="space-y-3" x-data="{ open: 0 }">
    @include('components.marketing.partials.faq-items', ['items' => $items])
</div>
@endif
