@props([
    'faqs' => null,
    'items' => null,
    'limit' => null,
    'title' => null,
    'subtitle' => null,
    'framed' => true,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Faq|array<string, string>> $faqs */
    $faqs = collect($faqs ?? $items ?? []);

    if ($faqs->isEmpty()) {
        $faqs = collect(config('faq.items', []));
    }

    if ($limit) {
        $faqs = $faqs->take($limit);
    }

    $sectionTitle = $title ?? ($settings['faq_title'] ?? 'الأسئلة الشائعة');
    $sectionSubtitle = $subtitle ?? ($settings['faq_sub_title'] ?? '');
@endphp

@if ($framed)
<section class="site-section">
    <div class="site-container max-w-4xl">
        @if ($sectionTitle !== '')
            <x-marketing.section-heading :title="$sectionTitle" :subtitle="$sectionSubtitle" />
        @endif

        <div @class(['mt-10' => $sectionTitle !== '', 'space-y-2' => true]) x-data="{ open: 0 }">
            @include('components.marketing.partials.faq-items', ['items' => $faqs])
        </div>

        @if ($limit)
            <div class="mt-10 text-center">
                <a href="{{ route('marketing.faq') }}" class="site-btn site-btn-outline">عرض جميع الأسئلة</a>
            </div>
        @endif
    </div>
</section>
@else
<div class="space-y-2" x-data="{ open: 0 }">
    @include('components.marketing.partials.faq-items', ['items' => $faqs])
</div>
@endif
