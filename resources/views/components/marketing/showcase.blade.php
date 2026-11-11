@props([
    'stats' => null,
])

@php
    $stats ??= [
        'tenants' => ['value' => 1284, 'decimals' => 0, 'prefix' => '', 'suffix' => '', 'separator' => true],
        'employees' => ['value' => 18420, 'decimals' => 0, 'prefix' => '', 'suffix' => '', 'separator' => true],
        'revenue' => ['value' => 458, 'decimals' => 0, 'prefix' => '', 'suffix' => 'K', 'separator' => false],
        'uptime' => ['value' => 99.9, 'decimals' => 1, 'prefix' => '%', 'suffix' => '', 'separator' => false],
    ];

    $tiles = [
        'tenants' => 'المستأجرون',
        'employees' => 'الموظفون',
        'revenue' => 'الإيرادات',
        'uptime' => 'الجاهزية',
    ];

    $tabs = [
        ['key' => 'dashboard', 'label' => 'لوحة التحكم'],
        ['key' => 'recruitment', 'label' => 'التوظيف'],
        ['key' => 'payroll', 'label' => 'الرواتب'],
    ];
@endphp

{{--
    Product tour, drawn in the same light idiom as the template's banner
    screenshot: a white frame with a soft shadow, cream inner panels, orange
    data marks, sitting on a scatter of circles. The tabs are real buttons
    (tablist semantics); the panels are illustrations of the product.
--}}
<section x-data="{ tab: 'dashboard' }" class="site-section">
    <div class="site-container">
        <x-marketing.section-heading
            :eyebrow="$settings['product_previews_badge_text'] ?? 'جولة في المنتج'"
            :title="$settings['product_previews_title'] ?? ''"
            :subtitle="$settings['product_previews_sub_title'] ?? ''"
        />

        <div class="mt-10 flex justify-center">
            <div class="inline-flex flex-wrap justify-center gap-2" role="tablist" aria-label="أقسام الجولة">
                @foreach ($tabs as $t)
                    <button
                        type="button"
                        role="tab"
                        id="showcase-tab-{{ $t['key'] }}"
                        aria-controls="showcase-panel-{{ $t['key'] }}"
                        :aria-selected="tab === '{{ $t['key'] }}' ? 'true' : 'false'"
                        @click="tab = '{{ $t['key'] }}'"
                        :class="tab === '{{ $t['key'] }}' ? 'site-btn-primary' : 'site-btn-outline'"
                        class="site-btn min-h-0 px-5 py-2"
                    >{{ $t['label'] }}</button>
                @endforeach
            </div>
        </div>

        <div class="relative isolate mx-auto mt-10 max-w-5xl p-4 sm:p-10">
            <x-marketing.circles set="figure" />

            <div class="site-card overflow-hidden shadow-[0_10px_50px_rgb(0_0_0/0.08)]">
                <div class="flex items-center gap-2 border-b border-site-border px-4 py-3">
                    <span class="h-2.5 w-2.5 rounded-full bg-site-primary"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-site-circle"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-site-border"></span>
                    <span class="ms-3 text-sm" dir="ltr" x-text="'app.mada.com/' + tab">app.mada.com/dashboard</span>
                </div>

                <div id="showcase-panel-dashboard" role="tabpanel" aria-labelledby="showcase-tab-dashboard" x-show="tab === 'dashboard'" class="grid gap-4 p-5 sm:grid-cols-4 sm:p-6">
                    @foreach ($tiles as $key => $label)
                        <div class="rounded-md bg-site-cream p-4 text-center">
                            <p>{{ $label }}</p>
                            <p @class(['mt-1 site-h4', 'text-site-primary-ink' => $key === 'revenue'])>
                                <x-marketing.stat-counter
                                    :value="$stats[$key]['value']"
                                    :prefix="$stats[$key]['prefix'] ?? ''"
                                    :suffix="$stats[$key]['suffix'] ?? ''"
                                    :decimals="$stats[$key]['decimals'] ?? 0"
                                    :separator="$stats[$key]['separator'] ?? in_array($key, ['tenants', 'employees'], true)"
                                />
                            </p>
                        </div>
                    @endforeach

                    <div class="rounded-md bg-site-cream p-4 sm:col-span-4" aria-hidden="true">
                        <svg class="h-36 w-full text-site-primary" viewBox="0 0 400 120" preserveAspectRatio="none" fill="none">
                            <defs>
                                <linearGradient id="showcase-fill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="currentColor" stop-opacity="0.25" />
                                    <stop offset="1" stop-color="currentColor" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="M0 95 C 30 60, 50 55, 80 70 S 130 30, 160 45 S 210 90, 240 70 S 290 20, 320 40 S 370 75, 400 50 L 400 120 L 0 120 Z" fill="url(#showcase-fill)" />
                            <path d="M0 95 C 30 60, 50 55, 80 70 S 130 30, 160 45 S 210 90, 240 70 S 290 20, 320 40 S 370 75, 400 50" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        </svg>
                    </div>
                </div>

                <div id="showcase-panel-recruitment" role="tabpanel" aria-labelledby="showcase-tab-recruitment" x-show="tab === 'recruitment'" x-cloak class="grid gap-4 p-5 sm:grid-cols-3 sm:p-6">
                    @foreach (['طلبات جديدة', 'قيد المراجعة', 'تمت المقابلة'] as $col)
                        <div class="rounded-md bg-site-cream p-4">
                            <p class="mb-3 font-medium text-site-dark">{{ $col }}</p>
                            <div class="space-y-2" aria-hidden="true">
                                <div class="rounded-md bg-white p-3"><div class="h-2 w-3/4 rounded bg-site-primary/40"></div><div class="mt-2 h-2 w-1/2 rounded bg-site-border"></div></div>
                                <div class="rounded-md bg-white p-3"><div class="h-2 w-2/3 rounded bg-site-primary/25"></div><div class="mt-2 h-2 w-1/3 rounded bg-site-border"></div></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div id="showcase-panel-payroll" role="tabpanel" aria-labelledby="showcase-tab-payroll" x-show="tab === 'payroll'" x-cloak class="p-5 sm:p-6">
                    <div class="overflow-hidden rounded-md bg-site-cream">
                        @foreach (['أحمد السالم' => '12,500', 'نورة القحطاني' => '9,800', 'خالد العتيبي' => '15,200'] as $name => $amount)
                            <div class="flex items-center justify-between border-b border-white px-4 py-3 last:border-0">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white font-semibold text-site-primary-ink">{{ mb_substr($name, 0, 1) }}</span>
                                    <span class="text-site-dark">{{ $name }}</span>
                                </div>
                                <span class="font-semibold text-site-primary-ink" dir="ltr">{{ $amount }} SAR</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
