@php
    /* Dashboard preview / product showcase (docs/MARKETING.md §4.7). Tabbed UI
       mock (Alpine) standing in for real screenshots until assets are ready. */
    $tabs = [
        ['key' => 'dashboard', 'label' => 'لوحة التحكم'],
        ['key' => 'projects', 'label' => 'المشاريع'],
        ['key' => 'payroll', 'label' => 'الرواتب'],
    ];
@endphp

<section x-data="{ tab: 'dashboard' }" class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            eyebrow="جولة في المنتج"
            title="واجهة أنيقة تجعل العمل متعة"
            subtitle="تصميم عصري يركّز على الوضوح والسرعة، بدعم كامل للعربية والوضعين الفاتح والداكن."
        />

        <div class="mt-10 flex justify-center">
            <div class="inline-flex items-center gap-1.5 rounded-full border border-mist-200 bg-ink-100 p-1.5 dark:border-ink-800 dark:bg-ink-800">
                @foreach ($tabs as $t)
                    <button
                        type="button"
                        @click="tab = '{{ $t['key'] }}'"
                        :class="tab === '{{ $t['key'] }}' ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-white' : 'text-mist-500 hover:text-ink-700 dark:hover:text-mist-200'"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition duration-200"
                    >{{ $t['label'] }}</button>
                @endforeach
            </div>
        </div>

        <div class="relative mx-auto mt-10 max-w-5xl">
            <div class="pointer-events-none absolute -inset-6 -z-10 rounded-[2.5rem] bg-emerald-400/10 blur-3xl" aria-hidden="true"></div>
            <div class="rounded-3xl border border-mist-200 bg-white/60 p-2 shadow-2xl backdrop-blur-xl dark:border-ink-800 dark:bg-ink-800/60">
                <div class="overflow-hidden rounded-2xl bg-ink-900">
                    <div class="flex items-center gap-2 border-b border-ink-800 px-4 py-3">
                        <span class="h-2.5 w-2.5 rounded-full bg-danger-solid"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        <span class="ms-3 text-xs text-mist-500" x-text="'app.veyra.com/' + tab"></span>
                    </div>

                    {{-- Dashboard --}}
                    <div x-show="tab === 'dashboard'" class="grid gap-4 p-6 sm:grid-cols-4">
                        <div class="rounded-xl bg-ink-800 p-4 text-center">
                            <p class="text-xs text-mist-400">المستأجرون</p>
                            <p class="mt-1 font-display text-xl font-bold text-ink-50">
                                <x-marketing.stat-counter :value="1284" />
                            </p>
                        </div>
                        <div class="rounded-xl bg-ink-800 p-4 text-center">
                            <p class="text-xs text-mist-400">الموظفون</p>
                            <p class="mt-1 font-display text-xl font-bold text-ink-50">
                                <x-marketing.stat-counter :value="18420" />
                            </p>
                        </div>
                        <div class="rounded-xl bg-ink-800 p-4 text-center">
                            <p class="text-xs text-mist-400">الإيرادات</p>
                            <p class="mt-1 font-display text-xl font-bold text-emerald-400">
                                <x-marketing.stat-counter :value="458" suffix="K" :separator="false" />
                            </p>
                        </div>
                        <div class="rounded-xl bg-ink-800 p-4 text-center">
                            <p class="text-xs text-mist-400">الجاهزية</p>
                            <p class="mt-1 font-display text-xl font-bold text-ink-50">
                                <x-marketing.stat-counter :value="99.9" prefix="%" :decimals="1" />
                            </p>
                        </div>
                        <div class="sm:col-span-4 rounded-xl bg-ink-800 p-4">
                            <div class="flex h-32 items-end gap-2">
                                @foreach ([40, 65, 45, 80, 60, 95, 70, 88] as $h)
                                    <span class="w-full rounded-t-md bg-emerald-400/60" style="height: {{ $h }}%"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Projects --}}
                    <div x-show="tab === 'projects'" x-cloak class="grid gap-4 p-6 sm:grid-cols-3">
                        @foreach (['قيد الإعداد', 'قيد التنفيذ', 'مكتمل'] as $col)
                            <div class="rounded-xl bg-ink-800 p-4">
                                <p class="mb-3 text-xs font-semibold text-mist-300">{{ $col }}</p>
                                <div class="space-y-2">
                                    <div class="rounded-lg bg-ink-900 p-3"><div class="h-2 w-3/4 rounded bg-emerald-400/40"></div><div class="mt-2 h-2 w-1/2 rounded bg-ink-700"></div></div>
                                    <div class="rounded-lg bg-ink-900 p-3"><div class="h-2 w-2/3 rounded bg-emerald-400/30"></div><div class="mt-2 h-2 w-1/3 rounded bg-ink-700"></div></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Payroll --}}
                    <div x-show="tab === 'payroll'" x-cloak class="p-6">
                        <div class="overflow-hidden rounded-xl bg-ink-800">
                            @foreach (['أحمد السالم' => '12,500', 'نورة القحطاني' => '9,800', 'خالد العتيبي' => '15,200'] as $name => $amount)
                                <div class="flex items-center justify-between border-b border-ink-900 px-4 py-3 last:border-0">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-400/15 text-xs font-bold text-emerald-400">{{ mb_substr($name, 0, 1) }}</span>
                                        <span class="text-sm text-mist-200">{{ $name }}</span>
                                    </div>
                                    <span class="font-display text-sm font-bold text-emerald-400" dir="ltr">{{ $amount }} SAR</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
