<x-layouts.app title="إعدادات المؤسسة">
    @php
        $inputClasses = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50 dark:placeholder:text-mist-500 disabled:cursor-not-allowed disabled:opacity-60';
        $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $errorClasses = 'mt-1.5 text-xs text-danger-solid';
        $selectedWorkingDays = collect(old('working_days', $calendar?->working_days ?? [0, 1, 2, 3, 4]))
            ->map(fn ($day): int => (int) $day)
            ->all();
        $holidayRows = old('holidays', $calendar?->holidays ?? [['date' => '', 'name' => '']]);
        if ($holidayRows === []) {
            $holidayRows = [['date' => '', 'name' => '']];
        }
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إعدادات المؤسسة</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                العملة، المنطقة الزمنية، وتقويم العمل لـ
                <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant?->name }}</span>
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('settings.company.update') }}"
            x-data="{ holidays: @js($holidayRows) }"
            class="space-y-6 rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:p-6"
        >
            @csrf
            @method('PUT')

            <fieldset @disabled(! $canUpdate) class="space-y-4">
                <legend class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">العملة والمنطقة الزمنية</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="currency" class="{{ $labelClasses }}">العملة الافتراضية</label>
                        <select id="currency" name="currency" required class="{{ $inputClasses }}">
                            @foreach ($currencies as $code => $label)
                                <option value="{{ $code }}" @selected(old('currency', $settings?->currency ?? 'SAR') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('currency')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="timezone" class="{{ $labelClasses }}">المنطقة الزمنية</label>
                        <select id="timezone" name="timezone" required class="{{ $inputClasses }}">
                            @foreach ($timezones as $tz => $label)
                                <option value="{{ $tz }}" @selected(old('timezone', $settings?->timezone ?? 'Asia/Riyadh') === $tz)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="evaluation_periodicity" class="{{ $labelClasses }}">دورية تقييم الأداء الافتراضية</label>
                    <select id="evaluation_periodicity" name="evaluation_periodicity" required class="{{ $inputClasses }}">
                        @foreach ($evaluationPeriodTypes as $type)
                            <option
                                value="{{ $type->value }}"
                                @selected(old('evaluation_periodicity', $settings?->evaluation_periodicity?->value ?? 'quarterly') === $type->value)
                            >{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-mist-500">يُستخدم كافتراضي في صفحة تقييمات الأداء ويمكن تغييره لكل عرض.</p>
                    @error('evaluation_periodicity')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>
            </fieldset>

            <fieldset @disabled(! $canUpdate) class="space-y-4 border-t border-mist-200 pt-6 dark:border-ink-600">
                <legend class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">تقويم العمل</legend>

                <div>
                    <p class="{{ $labelClasses }}">أيام العمل</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($weekdayLabels as $value => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-sm text-ink-700 transition hover:border-emerald-400/50 dark:border-ink-600 dark:text-mist-200">
                                <input
                                    type="checkbox"
                                    name="working_days[]"
                                    value="{{ $value }}"
                                    class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400"
                                    @checked(in_array($value, $selectedWorkingDays, true))
                                    @disabled(! $canUpdate)
                                >
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('working_days')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="{{ $labelClasses }} mb-0">العطل الرسمية</p>
                        @if ($canUpdate)
                            <button type="button" @click="holidays.push({ date: '', name: '' })" class="text-sm font-semibold text-emerald-600 transition hover:text-emerald-500 dark:text-emerald-400">
                                إضافة عطلة
                            </button>
                        @endif
                    </div>

                    <template x-for="(holiday, index) in holidays" :key="index">
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            <input type="date" :name="`holidays[${index}][date]`" x-model="holiday.date" class="{{ $inputClasses }}" @disabled(! $canUpdate)>
                            <input type="text" :name="`holidays[${index}][name]`" x-model="holiday.name" placeholder="اسم العطلة" class="{{ $inputClasses }}" @disabled(! $canUpdate)>
                            @if ($canUpdate)
                                <button
                                    type="button"
                                    @click="holidays.length === 1 ? holidays[0] = { date: '', name: '' } : holidays.splice(index, 1)"
                                    class="rounded-xl border border-mist-200 px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-mist-300 hover:bg-mist-50 dark:border-ink-600 dark:text-mist-200 dark:hover:bg-ink-900"
                                >
                                    حذف
                                </button>
                            @endif
                        </div>
                    </template>
                </div>
            </fieldset>

            @can('tenant.settings.update')
                <div class="flex justify-end border-t border-mist-200 pt-4 dark:border-ink-600">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300 active:scale-[0.98]"
                    >
                        حفظ الإعدادات
                    </button>
                </div>
            @else
                <p class="border-t border-mist-200 pt-4 text-sm text-mist-500 dark:border-ink-600 dark:text-mist-400">عرض فقط — ليس لديك صلاحية التعديل.</p>
            @endcan
        </form>
    </div>
</x-layouts.app>
