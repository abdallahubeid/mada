@props([
    'aiFeatures' => null,
])

@php
    $aiFeatures ??= collect();
@endphp

{{--
    Roadmap capabilities, in the same feature cards as the live pillars so
    the two read as one product — each carries a plain "قريباً" tag so
    nothing here can be mistaken for something available today
    (docs/MARKETING.md §4, "honesty over hype").
--}}
<section class="site-section">
    <div class="site-container text-center">
        <x-marketing.section-heading
            :eyebrow="$settings['ai_badge_text'] ?? 'قريباً · خارطة الطريق'"
            :title="$settings['ai_title'] ?? ''"
            :subtitle="$settings['ai_sub_title'] ?? ''"
        />

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($aiFeatures as $feature)
                <article class="site-feature-card relative">
                    <span class="absolute end-4 top-4 rounded-md bg-site-border-soft px-2.5 py-0.5 font-medium text-site-dark">قريباً</span>
                    <span class="site-feature-card-icon">
                        @if ($feature->icon)
                            <x-ui.icon :name="$feature->icon" class="h-8 w-8" />
                        @endif
                    </span>
                    <h3 class="site-h4 mt-6 mb-5">{{ $feature->title }}</h3>
                    <p>{{ $feature->description }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
