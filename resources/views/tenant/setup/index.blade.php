@php
    $initialStep = 1;

    if ($errors->hasAny(['logo'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['currency', 'timezone'])) {
        $initialStep = 3;
    } elseif ($errors->hasAny(['working_days', 'holidays'])) {
        $initialStep = 4;
    } elseif ($errors->hasAny(['password', 'password_confirmation'])) {
        $initialStep = 1;
    }

    $steps = ['كلمة المرور', 'شعار الشركة', 'العملة والمنطقة', 'تقويم العمل'];

    $selectedWorkingDays = collect(old('working_days', $calendar?->working_days ?? [0, 1, 2, 3, 4]))
        ->map(fn ($day): int => (int) $day)
        ->all();

    $holidayRows = old('holidays', $calendar?->holidays ?? [['date' => '', 'name' => '']]);
    if ($holidayRows === []) {
        $holidayRows = [['date' => '', 'name' => '']];
    }

    $logoUrl = $tenant->images()->where('collection', 'logo')->first()?->url();

    $inputClasses = 'block w-full rounded-xl border border-mist-300 bg-white px-3 py-2 text-sm text-ink-900 shadow-sm transition duration-150 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50 dark:placeholder:text-mist-500';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClasses = 'mt-1.5 text-xs text-danger-solid';
    $primaryBtn = 'inline-flex items-center justify-center gap-2 rounded-md bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-glow transition duration-200 ease-in-out hover:bg-brand-600 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50';
    $secondaryBtn = 'inline-flex items-center justify-center gap-2 rounded-md border border-mist-300 px-6 py-3 text-sm font-semibold text-ink-700 transition duration-200 ease-in-out hover:border-brand-500 hover:text-brand-600 active:scale-[0.98] dark:border-ink-600 dark:text-mist-200 dark:hover:border-brand-500 dark:hover:text-brand-300';
@endphp

<x-layouts.guest max-width="max-w-3xl" title="إعداد المؤسسة — مدى">
    <div class="mb-8 text-center">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="font-display text-2xl font-medium text-brand-600 dark:text-brand-300">مدى</span>
            <span class="text-sm text-mist-500">ERP</span>
        </a>
        <h1 class="mt-6 font-display text-2xl font-medium text-ink-900 dark:text-ink-50 sm:text-3xl">إعداد مؤسستك</h1>
        <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">
            أكمل خطوات الإعداد لـ
            <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant->name }}</span>
            — حسابك قيد مراجعة فريق مدى.
        </p>
    </div>

    <div
        x-data="{
            step: {{ $initialStep }},
            holidays: @js($holidayRows),
            addHoliday() {
                this.holidays.push({ date: '', name: '' });
            },
            removeHoliday(index) {
                if (this.holidays.length === 1) {
                    this.holidays[0] = { date: '', name: '' };
                    return;
                }
                this.holidays.splice(index, 1);
            },
        }"
        class="rounded-3xl border border-mist-200 bg-white p-6 shadow-sm dark:border-ink-700 dark:bg-ink-800/60 sm:p-8"
    >
        <div class="mb-8 flex items-center" role="list" aria-label="خطوات إعداد المؤسسة">
            @foreach ($steps as $index => $label)
                @php $n = $index + 1; @endphp
                <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center gap-2">
                        <span
                            :class="step > {{ $n }}
                                ? 'bg-brand-500 text-white'
                                : (step === {{ $n }}
                                    ? 'border-2 border-brand-500 bg-brand-500/10 text-brand-600 dark:text-brand-300'
                                    : 'border border-mist-300 text-mist-400 dark:border-ink-600 dark:text-mist-500')"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition duration-300 ease-out"
                        >
                            <template x-if="step > {{ $n }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                            </template>
                            <template x-if="step <= {{ $n }}">
                                <span>{{ $n }}</span>
                            </template>
                        </span>
                        <span
                            :class="step === {{ $n }} ? 'text-brand-600 dark:text-brand-300' : 'text-mist-500 dark:text-mist-400'"
                            class="hidden text-xs font-medium sm:block"
                        >
                            {{ $label }}
                        </span>
                    </div>

                    @if ($index < count($steps) - 1)
                        <div
                            :class="step > {{ $n }} ? 'bg-brand-500' : 'bg-mist-200 dark:bg-ink-600'"
                            class="mx-2 h-0.5 flex-1 rounded-full transition duration-300 ease-out sm:mx-3"
                        ></div>
                    @endif
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('dashboard.setup.update') }}" enctype="multipart/form-data" class="space-y-6" novalidate>
            @csrf
            @method('PUT')

            <div x-show="step === 1" x-cloak class="space-y-4">
                <div>
                    <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">تحديث كلمة المرور</h2>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اختر كلمة مرور قوية لحساب المالك قبل متابعة الإعداد.</p>
                </div>

                <div>
                    <label for="password" class="{{ $labelClasses }}">كلمة المرور الجديدة</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="{{ $inputClasses }}">
                    @error('password')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $labelClasses }}">تأكيد كلمة المرور</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $inputClasses }}">
                </div>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-4">
                <div>
                    <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">شعار الشركة</h2>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">ارفع شعار مؤسستك (اختياري — يمكن تخطيه الآن).</p>
                </div>

                @if ($logoUrl)
                    <div class="flex items-center gap-3 rounded-xl border border-mist-200 bg-mist-50 p-3 dark:border-ink-600 dark:bg-ink-900/40">
                        <img src="{{ $logoUrl }}" alt="شعار {{ $tenant->name }}" class="h-14 w-14 rounded-lg object-contain">
                        <p class="text-sm text-mist-500 dark:text-mist-400">الشعار الحالي — رفع ملف جديد يستبدله.</p>
                    </div>
                @endif

                <div>
                    <label for="logo" class="{{ $labelClasses }}">ملف الشعار</label>
                    <input id="logo" name="logo" type="file" accept="image/*" class="{{ $inputClasses }} file:me-3 file:rounded-md file:border-0 file:bg-brand-500/15 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 dark:file:text-brand-300">
                    @error('logo')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div x-show="step === 3" x-cloak class="space-y-4">
                <div>
                    <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">العملة والمنطقة الزمنية</h2>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تُستخدم هذه القيم كافتراضيات للرواتب والتقارير والحضور.</p>
                </div>

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

            <div x-show="step === 4" x-cloak class="space-y-4">
                <div>
                    <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">تقويم العمل</h2>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">حدد أيام العمل الرسمية وأيام العطل الرسمية للمؤسسة.</p>
                </div>

                <fieldset>
                    <legend class="{{ $labelClasses }}">أيام العمل</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($weekdayLabels as $value => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-sm text-ink-700 dark:border-ink-600 dark:text-mist-200">
                                <input
                                    type="checkbox"
                                    name="working_days[]"
                                    value="{{ $value }}"
                                    class="rounded border-mist-300 text-brand-500 focus:ring-brand-500"
                                    @checked(in_array($value, $selectedWorkingDays, true))
                                >
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('working_days')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="{{ $labelClasses }} mb-0">العطل الرسمية</p>
                        <button type="button" @click="addHoliday()" class="text-sm font-medium text-brand-600 hover:text-brand-500 dark:text-brand-300">
                            إضافة عطلة
                        </button>
                    </div>

                    <template x-for="(holiday, index) in holidays" :key="index">
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            <input type="date" :name="`holidays[${index}][date]`" x-model="holiday.date" class="{{ $inputClasses }}">
                            <input type="text" :name="`holidays[${index}][name]`" x-model="holiday.name" placeholder="اسم العطلة" class="{{ $inputClasses }}">
                            <button type="button" @click="removeHoliday(index)" class="{{ $secondaryBtn }} px-3 py-2">حذف</button>
                        </div>
                    </template>
                    @error('holidays')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-mist-200 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-ink-600">
                <button
                    type="button"
                    x-show="step > 1"
                    x-cloak
                    @click="step--"
                    class="{{ $secondaryBtn }}"
                >
                    السابق
                </button>

                <div class="flex flex-col gap-3 sm:ms-auto sm:flex-row">
                    <button
                        type="button"
                        x-show="step < 4"
                        @click="step++"
                        class="{{ $primaryBtn }}"
                    >
                        التالي
                    </button>

                    <button
                        type="submit"
                        x-show="step === 4"
                        x-cloak
                        class="{{ $primaryBtn }}"
                    >
                        حفظ الإعدادات
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-6 border-t border-mist-200 pt-4 text-center dark:border-ink-600">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-mist-500 underline-offset-2 hover:text-brand-600 hover:underline dark:text-mist-400 dark:hover:text-brand-300">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</x-layouts.guest>
