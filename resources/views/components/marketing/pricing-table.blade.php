@props([
    'compact' => false,
    'plans' => null,
    'currency' => null,
])

@php
    $plans ??= config('plans.tiers');
    $currency ??= config('plans.currency', '$');
    $checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
@endphp

{{--
    The template has no pricing block, so this one is assembled from its
    parts: the feature card's white surface and faint shadow, the peach
    hairline for the recommended plan, the orange title bar, and the
    template's two button styles.
--}}
<section id="pricing" x-data="{ yearly: false }" class="site-section">
    <div class="site-container text-center">
        <x-marketing.section-heading
            :title="$settings['pricing_title'] ?? ''"
            :subtitle="$settings['pricing_sub_title'] ?? ''"
        />

        <div class="mt-12 inline-flex items-center gap-1 rounded-md border border-site-border bg-white p-1" role="group" aria-label="دورة الفوترة">
            <button
                type="button"
                @click="yearly = false"
                :aria-pressed="! yearly ? 'true' : 'false'"
                :class="! yearly ? 'bg-site-primary-action text-white' : 'text-site-dark hover:text-site-primary-ink'"
                class="rounded px-5 py-2 font-medium transition"
            >شهري</button>
            <button
                type="button"
                @click="yearly = true"
                :aria-pressed="yearly ? 'true' : 'false'"
                :class="yearly ? 'bg-site-primary-action text-white' : 'text-site-dark hover:text-site-primary-ink'"
                class="flex items-center gap-2 rounded px-5 py-2 font-medium transition"
            >
                سنوي
                <span class="rounded bg-site-border-soft px-2 py-0.5 text-site-dark">خصم 20%</span>
            </button>
        </div>

        <div class="mt-12 grid gap-8 lg:grid-cols-3 lg:items-start">
            @foreach ($plans as $plan)
                <article @class([
                    'site-feature-card relative px-8 py-10 text-start',
                    'border-site-primary lg:-translate-y-4' => $plan['highlighted'],
                ])>
                    @if ($plan['highlighted'])
                        <span class="absolute inset-x-0 -top-4 mx-auto w-fit whitespace-nowrap rounded-md bg-site-primary-action px-4 py-1 font-medium text-white">الأكثر طلباً</span>
                    @endif

                    <h3 class="site-h4">{{ $plan['name'] }}</h3>
                    <p class="mt-1">{{ $plan['tagline'] }}</p>

                    <p class="site-h2 mt-6 font-semibold text-site-dark">
                        @if ($plan['monthly'] === null)
                            تواصل معنا
                        @else
                            <span x-text="yearly ? {{ $plan['yearly'] }} : {{ $plan['monthly'] }}">{{ $plan['monthly'] }}</span>
                            <span class="text-base font-normal text-site-text">{{ $currency }} / شهرياً</span>
                        @endif
                    </p>

                    <a
                        href="{{ $plan['href'] }}"
                        @class([
                            'site-btn mt-6 w-full',
                            'site-btn-primary' => $plan['highlighted'],
                            'site-btn-outline' => ! $plan['highlighted'],
                        ])
                    >{{ $plan['cta'] }}</a>

                    <ul class="mt-8 space-y-3">
                        @foreach ($plan['features'] as $item)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 text-site-primary">{!! $checkIcon !!}</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>

        @if ($compact)
            <a href="{{ $settings['pricing_btn_link'] ?? '/pricing' }}" class="site-btn site-btn-outline mt-12">
                {{ $settings['pricing_btn_text'] ?? 'قارن جميع المزايا بالتفصيل' }}
            </a>
        @endif
    </div>
</section>
