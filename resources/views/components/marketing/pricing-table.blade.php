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

<section id="pricing" x-data="{ yearly: false }" class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <x-marketing.section-heading
            :title="$settings['pricing_title'] ?? ''"
            :subtitle="$settings['pricing_sub_title'] ?? ''"
        />

        <div class="mt-8 inline-flex items-center gap-1.5 rounded-full border border-mist-200 bg-white p-1.5 dark:border-ink-800 dark:bg-ink-800">
            <button
                type="button"
                @click="yearly = false"
                :class="!yearly ? 'bg-emerald-500 text-ink-950 shadow-sm' : 'text-mist-500 hover:text-ink-700 dark:hover:text-mist-200'"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition duration-200"
            >شهري</button>
            <button
                type="button"
                @click="yearly = true"
                :class="yearly ? 'bg-emerald-500 text-ink-950 shadow-sm' : 'text-mist-500 hover:text-ink-700 dark:hover:text-mist-200'"
                class="flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium transition duration-200"
            >
                سنوي
                <span class="rounded-full bg-emerald-400/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">خصم 20%</span>
            </button>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-3 lg:items-start">
            @foreach ($plans as $plan)
                <div
                    @class([
                        'relative rounded-3xl p-8 text-start transition duration-200 ease-out',
                        'border-2 border-emerald-400 bg-white shadow-glow lg:-translate-y-4 dark:bg-ink-800' => $plan['highlighted'],
                        'border border-mist-200 bg-white hover:border-emerald-400/40 dark:border-ink-800 dark:bg-ink-800/40' => ! $plan['highlighted'],
                    ])
                >
                    @if ($plan['highlighted'])
                        <span class="absolute -top-4 start-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-emerald-400 px-4 py-1 text-xs font-bold text-ink-950">الأكثر طلباً</span>
                    @endif

                    <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $plan['name'] }}</h3>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $plan['tagline'] }}</p>

                    <p class="mt-6 font-display text-4xl font-bold text-ink-900 dark:text-ink-50">
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
                            'bg-emerald-500 text-ink-950 hover:bg-emerald-400' => $plan['highlighted'],
                            'border border-mist-300 text-ink-700 hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-700 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400' => ! $plan['highlighted'],
                        ])
                    >{{ $plan['cta'] }}</a>

                    <ul class="mt-8 space-y-3 text-sm text-mist-600 dark:text-mist-300">
                        @foreach ($plan['features'] as $item)
                            <li class="flex items-center gap-3">
                                <span class="text-emerald-600 dark:text-emerald-400">{!! $checkIcon !!}</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        @if ($compact)
            <a href="{{ $settings['pricing_btn_link'] ?? '/pricing' }}" class="mt-12 inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 transition hover:gap-3 dark:text-emerald-400">
                {{ $settings['pricing_btn_text'] ?? 'قارن جميع المزايا بالتفصيل' }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
            </a>
        @endif
    </div>
</section>
