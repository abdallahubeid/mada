@props([
    'compact' => false,
    'plans' => null,
    'currency' => null,
])

@php
    $plans ??= config('plans.tiers');
    $currency ??= config('plans.currency', '$');
    $checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
@endphp

<section id="pricing" x-data="{ yearly: false }" class="bg-mist-50 py-24">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :title="$settings['pricing_title'] ?? ''"
            :subtitle="$settings['pricing_sub_title'] ?? ''"
        />

        <div class="mt-8 inline-flex items-center gap-1.5 rounded-full border border-mist-200 bg-white p-1.5 dark:border-ink-800 dark:bg-ink-800">
            <button
                type="button"
                @click="yearly = false"
                :class="!yearly ? 'bg-brand-500 text-white shadow-sm' : 'text-mist-500 hover:text-white dark:hover:text-mist-200'"
                class="rounded-md px-4 py-1.5 text-sm font-medium transition duration-200"
            >شهري</button>
            <button
                type="button"
                @click="yearly = true"
                :class="yearly ? 'bg-brand-500 text-white shadow-sm' : 'text-mist-500 hover:text-white dark:hover:text-mist-200'"
                class="flex items-center gap-1.5 rounded-md px-4 py-1.5 text-sm font-medium transition duration-200"
            >
                سنوي
                <span class="rounded-md bg-brand-500/20 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:text-brand-300">خصم 20%</span>
            </button>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-3 lg:items-start">
            @foreach ($plans as $plan)
                <div
                    @class([
                        'relative rounded-3xl p-8 text-start transition duration-200 ease-out',
                        'border-2 border-brand-500 bg-white shadow-glow lg:-translate-y-4 dark:bg-ink-800' => $plan['highlighted'],
                        'border border-mist-200 bg-white hover:border-brand-500/40 dark:border-ink-800 dark:bg-ink-800/40' => ! $plan['highlighted'],
                    ])
                >
                    @if ($plan['highlighted'])
                        <span class="absolute -top-4 start-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-brand-500 px-4 py-1 text-xs font-bold text-white">الأكثر طلباً</span>
                    @endif

                    <h3 class="font-display text-lg font-bold text-ink-900 dark:text-ink-50">{{ $plan['name'] }}</h3>
                    <p class="mt-1 text-base text-mist-500 dark:text-mist-400">{{ $plan['tagline'] }}</p>

                    <p class="mt-6 font-display text-4xl font-medium text-ink-900 dark:text-ink-50">
                        @if ($plan['monthly'] === null)
                            تواصل معنا
                        @else
                            <span x-text="yearly ? {{ $plan['yearly'] }} : {{ $plan['monthly'] }}"></span>
                            <span class="text-base font-medium text-mist-500">{{ $currency }} / شهرياً</span>
                        @endif
                    </p>

                    <a
                        href="{{ $plan['href'] }}"
                        @class([
                            'mt-6 block rounded-full py-3 text-center text-sm font-semibold transition duration-200 ease-in-out active:scale-[0.98]',
                            'bg-brand-500 text-white hover:bg-brand-600' => $plan['highlighted'],
                            'border border-mist-300 text-ink-700 hover:border-brand-500 hover:text-brand-600 dark:border-ink-700 dark:text-mist-200 dark:hover:border-brand-500 dark:hover:text-brand-300' => ! $plan['highlighted'],
                        ])
                    >{{ $plan['cta'] }}</a>

                    <ul class="mt-8 space-y-3 text-sm text-mist-600 dark:text-mist-300">
                        @foreach ($plan['features'] as $item)
                            <li class="flex items-center gap-3">
                                <span class="text-brand-600 dark:text-brand-300">{!! $checkIcon !!}</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        @if ($compact)
            <a href="{{ $settings['pricing_btn_link'] ?? '/pricing' }}" class="mt-12 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:gap-3 dark:text-brand-300">
                {{ $settings['pricing_btn_text'] ?? 'قارن جميع المزايا بالتفصيل' }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
            </a>
        @endif
    </div>
</section>
