@php
    $weekend = old('weekend_days', $calendar?->resolvedWeekendDays() ?? [5, 6]);
@endphp

<x-layouts.app title="جدول العمل والورديات">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">جدول العمل والورديات</h1>
            <p class="mt-1 text-sm text-mist-500">حدد ساعات الدوام وفترة السماح للتأخير وأيام عطلة نهاية الأسبوع.</p>
        </div>

        <form
            method="POST"
            action="{{ route('settings.work-schedule.update') }}"
            class="ui-card space-y-5 p-4 sm:p-6"
        >
            @csrf
            @method('PUT')

            <fieldset @disabled(! $canUpdate) class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-work-schedule-work-start-time-1" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">بداية الدوام</label>
                        <input id="f-work-schedule-work-start-time-1"
                            type="time"
                            name="work_start_time"
                            required
                            dir="ltr"
                            value="{{ old('work_start_time', $calendar?->workStartTimeLabel() ?? '08:30') }}"
                            class="ui-control w-full"
                        >
                    </div>
                    <div>
                        <label for="f-work-schedule-work-end-time-2" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">نهاية الدوام</label>
                        <input id="f-work-schedule-work-end-time-2"
                            type="time"
                            name="work_end_time"
                            required
                            dir="ltr"
                            value="{{ old('work_end_time', $calendar?->workEndTimeLabel() ?? '16:30') }}"
                            class="ui-control w-full"
                        >
                    </div>
                </div>

                <div>
                    <label for="f-work-schedule-grace-period-minutes-3" class="mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200">فترة السماح للتأخير (بالدقائق)</label>
                    <input id="f-work-schedule-grace-period-minutes-3"
                        type="number"
                        name="grace_period_minutes"
                        min="0"
                        max="180"
                        required
                        value="{{ old('grace_period_minutes', $calendar?->grace_period_minutes ?? 15) }}"
                        class="ui-control w-full sm:max-w-xs"
                    >
                    <p class="mt-1 text-xs text-mist-500">يُحتسب التأخير بعد بداية الدوام + فترة السماح (مثلاً 08:30 + 15 = 08:45).</p>
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium text-ink-700 dark:text-mist-200">أيام عطلة نهاية الأسبوع</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ($weekdayLabels as $value => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600">
                                <input
                                    type="checkbox"
                                    name="weekend_days[]"
                                    value="{{ $value }}"
                                    class="rounded border-mist-300 text-brand-500"
                                    @checked(in_array($value, $weekend, true))
                                >
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </fieldset>

            @if ($canUpdate)
                <button type="submit" class="ui-btn ui-btn-primary">
                    حفظ جدول العمل
                </button>
            @else
                <p class="text-sm text-mist-500">عرض فقط — ليس لديك صلاحية التعديل.</p>
            @endif
        </form>
    </div>
</x-layouts.app>
