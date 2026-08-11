<x-layouts.app title="إعدادات المالية ونهاية الخدمة">
    @php
        $inputClasses = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50 dark:placeholder:text-mist-500';
        $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $hintClasses = 'mt-1.5 text-xs text-mist-500 dark:text-mist-400';
        $errorClasses = 'mt-1.5 text-xs text-danger-solid';

        // Basis points are what the domain stores; the form talks percent,
        // because that is how the underlying statute is written.
        $bpsToPercent = fn (int $bps): string => rtrim(rtrim(number_format($bps / 100, 2, '.', ''), '0'), '.') ?: '0';

        $taperRows = old('eosb_resignation_taper') ?? collect($policy->resignationTaper)
            ->map(fn (array $band): array => [
                'months' => $band['months'],
                'percent' => $bpsToPercent($band['bps']),
            ])
            ->all();
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إعدادات المالية ونهاية الخدمة</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                القواعد التي تُحتسب بها مكافأة نهاية الخدمة لكل تسوية جديدة.
            </p>
        </div>

        {{--
            The single most important thing on this page. EOSB is a statutory
            entitlement and Veyra ships a default, not a legal position — a
            tenant that never reads this banner is the failure mode the whole
            screen exists to prevent.
        --}}
        <div class="rounded-2xl border border-amber-300/60 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <p class="font-semibold">⚠️ هذه القيم ليست استشارة قانونية</p>
            <p class="mt-1 leading-relaxed">
                القيم الافتراضية تتبع النمط الخليجي/السعودي الشائع: نصف راتب شهر عن كل سنة في السنوات الخمس الأولى، وراتب شهر كامل عن كل سنة بعدها، مع تدرّج في حالة الاستقالة.
                يجب اعتمادها من مختص قبل أول تسوية فعلية، لأنها تحدّد أكبر دفعة مالية مفردة يستلمها الموظف.
            </p>
            @unless ($isConfigured)
                <p class="mt-2 font-medium">لم تُضبط هذه القواعد بعد لهذه المؤسسة — الحساب يجري حالياً على القيم الافتراضية.</p>
            @endunless
        </div>

        @if ($settledCount > 0)
            <div class="rounded-2xl border border-sky-300/60 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
                يوجد <x-ui.ltr class="font-semibold">{{ $settledCount }}</x-ui.ltr> تسوية محتسبة بالقواعد السارية وقت إنشائها. أي تعديل هنا يسري على التسويات الجديدة فقط ولا يُعيد احتساب أي تسوية قائمة.
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('finance.settings.update') }}"
            x-data="{ taper: @js(array_values($taperRows)) }"
            data-swal-confirm
            data-swal-variant="warning"
            data-swal-title="حفظ قواعد نهاية الخدمة؟"
            data-swal-text="ستُحتسب كل تسوية جديدة بهذه القواعد. التسويات القائمة لن تتغيّر."
            data-swal-confirm-button="نعم، احفظ القواعد"
            class="space-y-6 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:p-6"
        >
            @csrf
            @method('PUT')

            <fieldset class="space-y-4">
                <legend class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الاستحقاق الأساسي</legend>

                <label class="flex items-start gap-3 rounded-xl border border-mist-200 p-3 dark:border-ink-600">
                    <input type="checkbox" name="eosb_enabled" value="1" class="mt-1" @checked(old('eosb_enabled', $policy->enabled))>
                    <span>
                        <span class="block text-sm font-medium text-ink-700 dark:text-mist-200">تفعيل احتساب مكافأة نهاية الخدمة</span>
                        <span class="{{ $hintClasses }}">عند التعطيل تُحتسب المكافأة بصفر، وتبقى بقية بنود التسوية (رصيد الإجازات والراتب النسبي) كما هي.</span>
                    </span>
                </label>

                <div>
                    <label for="eosb_tier_boundary_months" class="{{ $labelClasses }}">حد الشريحة (بالأشهر)</label>
                    <input id="eosb_tier_boundary_months" type="number" min="0" max="600" dir="ltr" required
                           name="eosb_tier_boundary_months"
                           value="{{ old('eosb_tier_boundary_months', $policy->tierBoundaryMonths) }}"
                           class="{{ $inputClasses }} text-end tabular-nums">
                    <p class="{{ $hintClasses }}">عدد أشهر الخدمة التي تُطبَّق بعدها النسبة الأعلى. الافتراضي ٦٠ شهراً (خمس سنوات).</p>
                    @error('eosb_tier_boundary_months')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="eosb_lower_tier_percent" class="{{ $labelClasses }}">نسبة الشريحة الأولى (٪)</label>
                        <input id="eosb_lower_tier_percent" type="number" step="0.01" min="0" max="300" dir="ltr" required
                               name="eosb_lower_tier_percent"
                               value="{{ old('eosb_lower_tier_percent', $bpsToPercent($policy->lowerTierBps)) }}"
                               class="{{ $inputClasses }} text-end tabular-nums">
                        <p class="{{ $hintClasses }}">من راتب شهر عن كل سنة خدمة، قبل بلوغ حد الشريحة. الافتراضي ٥٠٪.</p>
                        @error('eosb_lower_tier_percent')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="eosb_upper_tier_percent" class="{{ $labelClasses }}">نسبة الشريحة الثانية (٪)</label>
                        <input id="eosb_upper_tier_percent" type="number" step="0.01" min="0" max="300" dir="ltr" required
                               name="eosb_upper_tier_percent"
                               value="{{ old('eosb_upper_tier_percent', $bpsToPercent($policy->upperTierBps)) }}"
                               class="{{ $inputClasses }} text-end tabular-nums">
                        <p class="{{ $hintClasses }}">عن كل سنة خدمة بعد حد الشريحة. الافتراضي ١٠٠٪.</p>
                        @error('eosb_upper_tier_percent')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-4 border-t border-mist-100 pt-5 dark:border-ink-700">
                <legend class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">تدرّج الاستقالة</legend>
                <p class="text-sm text-mist-500 dark:text-mist-400">
                    النسبة المستحقة من المكافأة الكاملة عند الاستقالة، حسب مدة الخدمة. تُطبَّق الشريحة الأعلى التي بلغها الموظف.
                    حالات إنهاء العقد والتقاعد تُصرف كاملة دائماً.
                </p>

                <div class="space-y-2">
                    <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                        <span class="text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">من (أشهر الخدمة)</span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">النسبة المستحقة (٪)</span>
                        <span></span>
                    </div>

                    <template x-for="(band, index) in taper" :key="index">
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            <input type="number" min="0" max="600" step="1" dir="ltr" required
                                   :name="`eosb_resignation_taper[${index}][months]`" x-model="band.months"
                                   class="{{ $inputClasses }} text-end tabular-nums">
                            <input type="number" min="0" max="100" step="0.01" dir="ltr" required
                                   :name="`eosb_resignation_taper[${index}][percent]`" x-model="band.percent"
                                   class="{{ $inputClasses }} text-end tabular-nums">
                            <button type="button"
                                    @click="taper.length === 1 ? taper[0] = { months: 0, percent: '0' } : taper.splice(index, 1)"
                                    class="rounded-xl border border-mist-200 px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-mist-300 hover:bg-mist-50 dark:border-ink-600 dark:text-mist-200 dark:hover:bg-ink-900">
                                حذف
                            </button>
                        </div>
                    </template>

                    <button type="button"
                            @click="taper.push({ months: 0, percent: '0' })"
                            class="rounded-xl border border-dashed border-mist-300 px-4 py-2 text-sm font-semibold text-mist-600 transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:text-mist-300">
                        + إضافة شريحة
                    </button>
                </div>

                @error('eosb_resignation_taper')
                    <p class="{{ $errorClasses }}">{{ $message }}</p>
                @enderror
                @foreach ($errors->get('eosb_resignation_taper.*') as $messages)
                    @foreach ($messages as $message)
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @endforeach
                @endforeach
            </fieldset>

            <fieldset class="space-y-4 border-t border-mist-100 pt-5 dark:border-ink-700">
                <legend class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الشهر المعياري</legend>
                <p class="text-sm text-mist-500 dark:text-mist-400">
                    يُستخدم لاشتقاق الأجر الشهري للموظف بالساعة، ولاحتساب بدل رصيد الإجازات عندما لا يتضمن شهر المغادرة أيام عمل مجدولة.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nominal_month_days" class="{{ $labelClasses }}">أيام العمل في الشهر</label>
                        <input id="nominal_month_days" type="number" min="1" max="31" dir="ltr" required
                               name="nominal_month_days"
                               value="{{ old('nominal_month_days', $policy->nominalMonthDays) }}"
                               class="{{ $inputClasses }} text-end tabular-nums">
                        @error('nominal_month_days')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nominal_day_hours" class="{{ $labelClasses }}">ساعات العمل في اليوم</label>
                        <input id="nominal_day_hours" type="number" min="1" max="24" dir="ltr" required
                               name="nominal_day_hours"
                               value="{{ old('nominal_day_hours', $policy->nominalDayHours) }}"
                               class="{{ $inputClasses }} text-end tabular-nums">
                        @error('nominal_day_hours')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end gap-3 border-t border-mist-100 pt-5 dark:border-ink-700">
                <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                    حفظ القواعد
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('finance.settings.reset') }}"
              data-swal-confirm
              data-swal-variant="warning"
              data-swal-title="استعادة القيم الافتراضية؟"
              data-swal-text="سيتم استبدال القواعد المضبوطة حالياً بالنمط الخليجي/السعودي الافتراضي."
              data-swal-confirm-button="نعم، استعد الافتراضي"
              class="flex justify-end">
            @csrf
            <button type="submit" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-600 transition hover:border-amber-400 hover:text-amber-600 dark:border-ink-600 dark:text-mist-300">
                استعادة القيم الافتراضية
            </button>
        </form>
    </div>
</x-layouts.app>
